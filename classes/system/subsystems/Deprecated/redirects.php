<?php

	/**
	 * Class redirects
	 * @deprecated
	 */
	class redirects implements iRedirects {

		/* @var string $urlSuffix суффикс адресов страниц сайта */
		private $urlSuffix = '/';

		/**
		 * Получить экземпляр коллекции
		 * @return iRedirects экземпляр коллекции
		 */
		public static function getInstance() {
			static $instance;
			if (is_null($instance)) {
				$instance = new redirects;
			}
			return $instance;
		}

		/** Конструктор */
		private function __construct() {
			$config = mainConfiguration::getInstance();

			if ((bool) $config->get('seo', 'url-suffix.add')) {
				$this->urlSuffix = (string) $config->get('seo', 'url-suffix');
			}
		}

		/**
		 * Добавить новое перенаправление
		 * @param String $source адрес страницы, с которой осуществляется перенаправление
		 * @param String $target адрес целевой страницы
		 * @param Integer $status = 301 статус перенаправления
		 * @param bool $madeByUser = false, редирект сделан пользователем
		 */
		public function add($source, $target, $status = 301, $madeByUser = false) {
			if ($source == $target) {
				return;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$source = $connection->escape($this->parseUri($source));
			$target = $connection->escape($this->parseUri($target));
			$status = (int) $status;
			$madeByUser = (int) $madeByUser;
			$connection->startTransaction('Adding new redirect records');

			try {
				//Создать новые записи на тот случай, если у нас уже есть перенаправление на $target
				$sql = <<<SQL
INSERT INTO `cms3_redirects`
	(`source`, `target`, `status`, `made_by_user`)
	SELECT `source`, '{$target}', '{$status}', $madeByUser FROM `cms3_redirects`
		WHERE `target` = '{$source}'
SQL;
				$connection->query($sql);

				//Удалить старые записи
				$sql = <<<SQL
DELETE FROM `cms3_redirects` WHERE `target` = '{$source}'
SQL;
				$connection->query($sql);

				$result = $connection->queryResult(
					"SELECT * FROM `cms3_redirects` WHERE `source` = '{$source}' AND `target` = '{$target}'"
				);

				if ($result->length() > 0) {
					$connection->rollbackTransaction();
					return;
				}

				//Добавляем новую запись для перенаправления
				$sql = <<<SQL
INSERT INTO `cms3_redirects`
	(`source`, `target`, `status`, `made_by_user`)
	VALUES
	('{$source}', '{$target}', '{$status}', $madeByUser)
SQL;
				$connection->query($sql);
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
		}

		/**
		 * Получить список перенаправлений со страницы $source
		 * @param String $source адрес страницы, с которой осуществляется перенаправление
		 * @param bool $madeByUser = false, редирект сделан пользователем
		 * @return array массив перенаправлений
		 */
		public function getRedirectsIdBySource($source, $madeByUser = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sourceSQL = $this->parseUri($source);
			$madeByUser = (int) $madeByUser;
			$redirects = [];

			$sql =
				"SELECT `id`, `target`, `status` FROM `cms3_redirects` WHERE `source` = '{$sourceSQL}' AND `made_by_user` = $madeByUser";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				list($id, $target, $status) = $row;
				$redirects[$id] = [$source, $target, (int) $status];
			}

			return $redirects;
		}

		/**
		 * Получить перенаправление по целевому адресу
		 * @param String $target адрес целевой страницы
		 * @param bool $madeByUser = false, редирект сделан пользователем
		 * @return array массив перенаправления
		 */
		public function getRedirectIdByTarget($target, $madeByUser = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$targetSQL = $connection->escape($this->parseUri($target));
			$madeByUser = (int) $madeByUser;

			$sql =
				"SELECT `id`, `source`, `status` FROM `cms3_redirects` WHERE `target` = '{$targetSQL}' AND `made_by_user` = $madeByUser";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			list($id, $source, $status) = $result->fetch();
			return [$source, $target, (int) $status];
		}

		/**
		 * Удалить перенаправление
		 * @param Integer $id id перенаправления
		 */
		public function del($id) {
			$id = (int) $id;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
DELETE FROM `cms3_redirects` WHERE `id` = '{$id}'
SQL;
			$connection->query($sql);
		}

		/**
		 * Сделать перенаправление, если url есть в таблице перенаправлений
		 * @param String $currentUri url для поиска
		 * @param bool $madeByUser = false, редирект сделан пользователем
		 * @return void
		 */
		public function redirectIfRequired($currentUri, $madeByUser = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$currentUri = $connection->escape($this->parseUri($currentUri));
			$madeByUser = (int) $madeByUser;

			$sql = <<<SQL
SELECT `target`, `status` FROM `cms3_redirects`
	WHERE `source` = '{$currentUri}' AND `made_by_user` = $madeByUser
	ORDER BY `id` DESC LIMIT 1
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() > 0) {
				list($target, $status) = $result->fetch();

				if (strpos($target, 'http') !== 0) {
					$target = '/' . $target;
				}

				return $this->redirect($target, (int) $status);
			}

			//Попробуем найти в перенаправление в подстраницах
			$uriParts = explode("/", trim($currentUri, "/"));
			do {
				array_pop($uriParts);
				$subUri = implode("/", $uriParts) . "/";
				$subUriSQL = $connection->escape($this->parseUri($subUri));

				if (!strlen($subUriSQL)) {
					if (umiCount($uriParts)) {
						continue;
					}
					break;
				}

				$sql = <<<SQL
SELECT `source`, `target`, `status` FROM `cms3_redirects`
	WHERE `source` = '{$subUriSQL}' AND `made_by_user` = $madeByUser
	ORDER BY `id` DESC LIMIT 1
SQL;

				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				if ($result->length() > 0) {
					list($source, $target, $status) = $result->fetch();

					$sourceUriSuffix = substr($currentUri, strlen($source));
					$target .= $sourceUriSuffix;

					if (strpos($target, 'http') !== 0) {
						$target = '/' . $target;
					}

					$this->redirect($target, $status);
				}
			} while (umiCount($uriParts) > 1);
		}

		/** Инициализировать события */
		public function init() {
			$config = mainConfiguration::getInstance();

			if ($config->get('seo', 'watch-redirects-history')) {
				$listener = new umiEventListener("systemModifyElement", "content", "onModifyPageWatchRedirects");
				$listener = new umiEventListener("systemMoveElement", "content", "onModifyPageWatchRedirects");
			}
		}

		/** Удаляет все редиректы */
		public function deleteAllRedirects() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('TRUNCATE TABLE `cms3_redirects`;');
		}

		protected function redirect($target, $status) {
			$statuses = [
				300 => 'Multiple Choices',
				'Moved Permanently',
				'Found',
				'See Other',
				'Not Modified',
				'Use Proxy',
				'Switch Proxy',
				'Temporary Redirect'
			];

			if (!isset($statuses[$status])) {
				return false;
			}
			$statusMessage = $statuses[$status];

			/** @var HTTPOutputBuffer $buffer */
			$buffer = outputBuffer::current();

			if ($referrer = getServer('HTTP_REFERER')) {
				$buffer->setHeader('Referrer', (string) $referrer);
			}

			$buffer->status($status . ' ' . $statusMessage);
			$buffer->redirect($target);
			$buffer->end();
		}

		protected function parseUri($uri) {
			$uri = ltrim($uri, '/');

			if ($this->urlSuffix == '/') {
				return rtrim($uri, '/');
			}

			$suffix = addcslashes($this->urlSuffix, '\^.$|()[]*+?{},');
			$pattern = '/(' . $suffix . ')/';
			$cleanUri = preg_replace($pattern, '', $uri);
			return (is_null($cleanUri)) ? $uri : $cleanUri;
		}
	}

	;
?>
