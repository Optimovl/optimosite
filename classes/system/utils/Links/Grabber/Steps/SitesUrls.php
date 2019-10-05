<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber\Steps;

	/**
	 * Class SitesUrls шаг сбора ссылок из страниц сайта.
	 * Для работы классы необходима библиотека cURL.
	 * @package UmiCms\Classes\System\Utils\Links\Grabber\Steps
	 */
	class SitesUrls extends ObjectsNames implements \iUmiPagesInjector {

		use \tUmiPagesInjector;

		/** @const string STEP_NAME имя шага */
		const STEP_NAME = 'SitesUrls';

		/** @const string USER_AGENT user agent, который используется в http запросах */
		const USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:48.0) Gecko/20100101 Firefox/48.0';

		/** @const string HEAD_HTTP_REQUEST_TYPE тип HTTP запроса "GET" */
		const GET_HTTP_REQUEST_TYPE = 'GET';

		/**
		 * @const string ITERATION_ITEMS_NUMBER_DEFAULT_LIMIT ограничение на количество обрабатываемых адресов
		 * за одну итерациию сбора шага по умолчанию
		 */
		const ITERATION_ITEMS_NUMBER_DEFAULT_LIMIT = 3;

		/** @const string CURL_TIMEOUT таймаут ожидания ответа на запрос в секундах */
		const CURL_TIMEOUT = 9;

		/** @inheritdoc */
		public function getName() {
			return self::STEP_NAME;
		}

		/** @inheritdoc */
		public function getStartStateStructure() {
			return [
				self::OFFSET_KEY => 0,
				self::LIMIT_KEY => self::ITERATION_ITEMS_NUMBER_DEFAULT_LIMIT,
				self::COMPLETE_KEY => false,
			];
		}

		/** @inheritdoc */
		public function grab() {
			if ($this->isComplete()) {
				return $this;
			}

			$limit = (int) $this->getLimit();
			$offset = (int) $this->getOffset();

			$connection = $this->getConnection();
			$sql = <<<SQL
SELECT
	`cms3_hierarchy`.`id`
FROM
	`cms3_hierarchy`
WHERE
	`cms3_hierarchy`.is_active = 1
AND
	`cms3_hierarchy`.is_deleted = 0
LIMIT
	$offset, $limit;
SQL;
			$result = $connection->queryResult($sql);
			$pagesLinks = [];

			if ($result->length() == 0) {
				$this->setResult($pagesLinks)
					->setOffset(0)
					->setCompleteStatus(true);
			}

			$pageCollection = $this->getPagesCollection();
			$oldStatus = $pageCollection->forceAbsolutePath();

			foreach ($result as $row) {
				$pageId = array_shift($row);
				$pageUrl = $pageCollection->getPathById($pageId);
				$pageContent = $this->getUrlContent($pageUrl);
				$pagesLinks[$pageUrl] = $this->getLinksFromHtmlText($pageContent);
				$pageCollection->unloadElement($pageId);
			}

			$pageCollection->forceAbsolutePath($oldStatus);

			return $this->setResult($pagesLinks)
				->setOffset($offset + $limit);
		}

		/**
		 * Возвращает ответ веб сервера по адресу страницы сайта
		 * @param string $url адрес страницы сайта
		 * @return string
		 * @throws \RequiredFunctionIsNotExistsException
		 */
		private function getUrlContent($url) {
			if (!function_exists('curl_init')) {
				throw new \RequiredFunctionIsNotExistsException('cURL library should be installed');
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::GET_HTTP_REQUEST_TYPE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
			$result = curl_exec($ch);
			curl_close($ch);
			return (string) $result;
		}
	}
