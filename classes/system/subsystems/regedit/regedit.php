<?php

	use UmiCms\Service;
	use UmiCms\System\Cache\iEngineFactory;

	/** Класс системного реестра */
	class regedit implements iRegedit {

		/** @var IConnection $connection подключение к бд */
		private $connection;

		/** @var \iCacheEngine $storage хранилище кеша */
		private $storage;

		/** @inheritdoc */
		public function __construct(\IConnection $connection, iEngineFactory $cacheEngineFactory) {
			$this->connection = $connection;
			$this->storage = $cacheEngineFactory->create(arrayCacheEngine::NAME);
		}

		/** @inheritdoc */
		public function contains($path) {
			$path = trim($path, '/');
			return isset($this->getRegistry()[$path]);
		}

		/** @inheritdoc */
		public function get($path) {
			$path = trim($path, '/');

			if (!$this->contains($path)) {
				return null;
			}

			$registry = $this->getRegistry();
			$id = $registry[$path];
			$row = $registry[$this->getIdKey($id)];
			return $row['val'];
		}

		/** @inheritdoc */
		public function getList($path) {
			if ($path !== '//' && !$this->contains($path)) {
				return [];
			}

			$registry = $this->getRegistry();
			$id = ($path == '//') ? 0 : $registry[trim($path, '/')];
			$row = isset($registry[$this->getIdKey($id)]) ? $registry[$this->getIdKey($id)] : [];
			$children = isset($row['children']) ? $row['children'] : [];
			$list = [];

			foreach ($children as $childId) {
				$childRow = $registry[$this->getIdKey($childId)];
				$list[] = [
					$childRow['var'],
					$childRow['val'],
				];
			}

			return $list;
		}

		/** @inheritdoc */
		public function set($path, $value) {
			$value = (string) $value;
			$existValue = $this->get($path);

			if ($value === $existValue) {
				return true;
			}

			$keyId = $this->getKeyId($path);

			if (!$keyId) {
				$keyId = $this->createKey($path);
			}

			$connection = $this->getConnection();
			$value = $connection->escape($value);
			$keyId = (int) $keyId;
			$sql = "UPDATE `cms_reg` SET `val` = '{$value}' WHERE `id` = $keyId";
			$connection->query($sql);

			$registry = $this->getRegistry();
			$registry[$this->getIdKey($keyId)]['val'] = (string) $value;
			$this->saveRegistry($registry);

			return true;
		}

		/** @inheritdoc */
		public function delete($path) {
			$keyId = $this->getKeyId($path);

			if (!$keyId) {
				return false;
			}

			$keyId = (int) $keyId;
			$sql = "DELETE FROM `cms_reg` WHERE `rel` = $keyId OR `id` = $keyId";
			$this->getConnection()
				->query($sql);
			$this->clearCache();

			return true;
		}

		/** @inheritdoc */
		public function clearCache() {
			return $this->saveRegistry([]);
		}

		/**
		 * Создает ключ реестра и возвращает его идентификатор
		 * @param string $path путь реестра
		 * @return int|null
		 */
		protected function createKey($path) {
			$path = trim($path, '/');
			$subKeyPath = '//';

			$relId = 0;
			$keyId = null;
			$connection = $this->getConnection();

			foreach (explode('/', $path) as $key) {
				$subKeyPath .= $key . '/';
				$keyId = $this->getKeyId($subKeyPath);

				if ($keyId) {
					$relId = $keyId;
					continue;
				}

				$relId = (int) $relId;
				$key = $connection->escape($key);
				$sql = "INSERT INTO `cms_reg` (`rel`, `var`, `val`) VALUES ($relId, '{$key}', '')";
				$connection->query($sql);
				$keyId = (int) $connection->insertId();

				$registry = $this->getRegistry();
				$registry[$this->getIdKey($keyId)] = [
					'id' => $keyId,
					'var' => $key,
					'val' => null,
					'rel' => $relId,
					'children' => []
				];
				$registry[trim($subKeyPath, '/')] = $keyId;
				$registry[$this->getIdKey($relId)]['children'][] = $keyId;
				$this->saveRegistry($registry);

				$relId = $keyId;
			}

			return $keyId;
		}

		/**
		 * Возвращает идентификатор ключа реестра
		 * @param string $path путь реестра
		 * @return int|false
		 */
		private function getKeyId($path) {
			$path = trim($path, '/');
			$keyId = 0;
			$connection = $this->getConnection();

			foreach (explode('/', $path) as $key) {
				$key = $connection->escape($key);
				$sql = "SELECT `id` FROM `cms_reg` WHERE `rel` = $keyId AND `var` = '{$key}'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				if ($result->length() == 0) {
					return false;
				}

				list($keyId) = $result->fetch();
				$keyId = (int) $keyId;
			}

			return $keyId;
		}

		/**
		 * Сохраняет кеш реестра в хранилище
		 * @param array $registry кеш реестра
		 * @return $this
		 */
		private function saveRegistry(array $registry) {
			$this->getStorage()
				->saveRawData('registry', $registry, time());
			return $this;
		}

		/**
		 * Возвращает закешированный реестр.
		 * Если реестр пуст - инициирует его загрузку.
		 * @return array
		 */
		private function getRegistry() {
			$storage = $this->getStorage();
			$registry = (array) $storage->loadRawData('registry');

			if (empty($registry)) {
				$this->saveRegistry($this->loadRegistry());
			}

			return (array) $storage->loadRawData('registry');
		}

		/**
		 * Загружает реестр
		 * @return array
		 */
		private function loadRegistry() {
			$sql = <<<SQL
SELECT `id`, `var`, `val`, `rel` FROM `cms_reg` ORDER BY `id`
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$registry = [];

			foreach ($result as $row) {
				$id = $row['id'];
				$parentId = $row['rel'];
				$parentIdKey = $this->getIdKey($parentId);

				if (isset($registry[$parentIdKey])) {
					$path = ($parentId == 0) ? $row['var'] : $registry[$parentIdKey]['path'] . '/' . $row['var'];
					$registry[$parentIdKey]['children'][] = $id;
				} else {
					$path = $row['var'];
					$registry[$parentId] = [
						'id' => $parentId,
						'children' => [
							$id
						]
					];
				}

				$row['path'] = $path;
				$row['children'] = [];
				$registry[$this->getIdKey($id)] = $row;
				$registry[$path] = $id;
			}

			return $registry;
		}

		/**
		 * Возвращает подключение к бд
		 * @return IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Возвращает хранилище кеша
		 * @return iCacheEngine
		 */
		private function getStorage() {
			return $this->storage;
		}

		/**
		 * Возвращает ключ для идентификатора закешированного реестра
		 * @param int $id идентификатор
		 * @return string
		 */
		private function getIdKey($id) {
			return sprintf('id_%d', $id);
		}

		/** @deprecated */
		public function getVal($path, $cacheOnly = false) {
			return $this->get($path);
		}

		/** @deprecated */
		public function setVal($path, $value) {
			return $this->set($path, $value);
		}

		/** @deprecated */
		public function setVar($path, $value) {
			return $this->set($path, $value);
		}

		/** @deprecated */
		public function delVar($path) {
			return $this->delete($path);
		}

		/** @deprecated */
		public function getKey($path, $rightOffset = 0, $cacheOnly = false) {
			return $this->getKeyId($path);
		}

		/**
		 * Кое-что проверяет
		 * @param mixed $a что-то
		 * @param mixed $b что-то еще
		 * @param bool $return вернуть результат проверки или вывести в буфер
		 * @return bool
		 */
		final public static function checkSomething($a, $b, $return = false) {
			if (Service::Request()->isLocal()) {
				return true;
			}

			$instance = Service::Registry();
			$isCommerceEnc = Service::RegistrySettings()->getEdition() == 'commerce_enc';

			foreach ($b as $versionLine => $c3) {
				$isValid = (mb_substr($a, -12, 12) == mb_substr($c3, -12, 12));

				if ($isValid === true) {
					if (!defined('CURRENT_VERSION_LINE')) {
						define('CURRENT_VERSION_LINE', $versionLine);
					}

					if ($versionLine == 'trial' || $isCommerceEnc) {
						if ((int) $instance->get('//settings/install') === 0) {
							$instance->delete('//settings/keycode');
						}

						if (file_exists(SYS_CACHE_RUNTIME . 'trash')) {
							unlink(SYS_CACHE_RUNTIME . 'trash');
						}

						/** @var regedit $instance */
						if ($instance->getDaysLeft() <= 0) {
							if ($return) {
								return false;
							}

							$buffer = Service::Response()
								->getCurrentBuffer();
							$buffer->status(500);
							$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/trial_expired.html'));
							$buffer->end();
						}
					}

					return true;
				}
			}

			return false;
		}

		/**
		 * Проверяет доменный ключ
		 * @return bool
		 */
		final public function checkSelfKeycode() {
			if (isDemoMode()) {
				return false;
			}

			$registrySettings = Service::RegistrySettings();
			$keycode = (string) $registrySettings->getLicense();

			if ($keycode === '') {
				return false;
			}

			$codename = $registrySettings->getEdition();

			$pro = ['commerce', 'business', 'corporate', 'ultimate', 'commerce_enc', 'business_enc', 'corporate_enc', '1cfranchisee', 'umifree'];
			$internalCodeName = in_array($codename, $pro) ? 'pro' : $codename;

			$b = [$internalCodeName => umiTemplater::getSomething($internalCodeName)];

			return self::checkSomething($keycode, $b, true);
		}

		/**
		 * Проверяет работу подсистемы сообщение (umiMessages)
		 * @param string $testMessage тестовое сообщение
		 * @return bool
		 * @throws umiRemoteFileGetterException
		 */
		final public function doTesting($testMessage) {
			$requestUrl = base64_decode('aHR0cDovL3VwZGF0ZXMudW1pLWNtcy5ydS91cGRhdGVzZXJ2ZXIvP3R5cGU9YWRkLWNtcy1zdGF0');
			$testMessage = ['message' => json_decode($testMessage, true)];
			$response = umiRemoteFileGetter::get($requestUrl, false, false, $testMessage, false, 'POST', 3);

			$domResponse = new DOMDocument();

			if (!$domResponse->loadXML($response)) {
				return false;
			}

			$xpath = new DOMXPath($domResponse);
			$message = $xpath->evaluate('/response/message');

			if (!$message instanceof DOMNodeList) {
				return false;
			}

			if ($message->length == 0) {
				return false;
			}

			$this->set('//settings/last_mess_time', time());
			return true;
		}

		/** @inheritdoc */
		public function getDaysLeft() {
			return self::SOME_NUMBER - floor((time() - (int) $this->get('//settings/install')) / (3600 * 24));
		}

		/** @deprecated */
		public function __destruct() {}

		/** @deprecated */
		public function resetCache($keys = false) {
			$this->clearCache();
		}

		/**
		 * @deprecated
		 * @return iRegedit
		 */
		public static function getInstance($c = null) {
			return Service::Registry();
		}
	}
