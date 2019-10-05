<?php

	use UmiCms\Service;
	use UmiCms\System\Cache\iEngineFactory;
	use UmiCms\System\Cache\Key\iGenerator;
	use UmiCms\System\Cache\Key\iValidator;
	use UmiCms\System\Cache\Key\Validator\iFactory;
	use UmiCms\System\Request\Mode\iDetector;

	/**
	 * Фасад для работы с кешем.
	 * Проксирует работу с кешем хранилищам, умеет выбирать подходящее хранилище и хранить этот выбор.
	 */
	class cacheFrontend implements iCacheFrontend {

		/** @var iCacheEngine $cacheEngine реализация текущего кеширующего механизма */
		private $cacheEngine;

		/** @var bool $disabled отключено ли кеширование */
		private $disabled = false;

		/** @var iEngineFactory $engineFactory фабрика хранилищ */
		private $engineFactory;

		/** @var iGenerator $keyGenerator генератор ключей */
		private $keyGenerator;

		/** @var iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var iFactory $keyValidatorFactory фабрика валидаторов ключей */
		private $keyValidatorFactory;

		/** @var iValidator $keyValidator валидатор ключей */
		private $keyValidator;

		/** @var iDetector $modeDetector */
		private $modeDetector;

		/** @inheritdoc */
		public function __construct(
			iEngineFactory $engineFactory,
			iGenerator $keyGenerator,
			iConfiguration $configuration,
			iFactory $keyValidatorFactory,
			iDetector $modeDetector
		) {
			$this->engineFactory = $engineFactory;
			$this->keyGenerator = $keyGenerator;
			$this->configuration = $configuration;
			$this->keyValidatorFactory = $keyValidatorFactory;
			$this->modeDetector = $modeDetector;
			$this->init();
		}

		/** @inheritdoc */
		public function isCacheEnabled() {
			if ($this->getCacheEngine() instanceof nullCacheEngine) {
				return false;
			}

			return $this->getCacheEngine()
				->getIsConnected();
		}

		/** @inheritdoc */
		public function save(iUmiEntinty $entity, $storeType = null, $expire = self::DEFAULT_TIME_TO_LIVE) {
			$key = $this->getKeyGenerator()
				->createKey($entity->getId(), $storeType);
			return $this->set($key, $entity, $expire);
		}

		/** @inheritdoc */
		public function load($entityId, $storeType = null) {
			$key = $this->getKeyGenerator()
				->createKey($entityId, $storeType);
			return $this->get($key);
		}

		/** @inheritdoc */
		public function saveSql($query, $result, $expire = self::DEFAULT_TIME_TO_LIVE) {
			$key = $this->getKeyGenerator()
				->createKeyForQuery($query);
			return $this->set($key, $result, $expire);
		}

		/** @inheritdoc */
		public function loadSql($query) {
			$key = $this->getKeyGenerator()
				->createKeyForQuery($query);
			return $this->get($key);
		}

		/** @inheritdoc */
		public function saveData($key, $value, $expire = self::DEFAULT_TIME_TO_LIVE) {
			if (!$this->isValidKey($key)) {
				return false;
			}

			$key = $this->getKeyGenerator()
				->createKey($key);
			return $this->set($key, $value, $expire);
		}

		/** @inheritdoc */
		public function loadData($key) {
			if (!$this->isValidKey($key)) {
				return false;
			}

			$key = $this->getKeyGenerator()
				->createKey($key);
			return $this->get($key);
		}

		/** @inheritdoc */
		public function setDisabled($flag = true) {
			$this->disabled = (bool) $flag;
			return $this;
		}

		/** @inheritdoc */
		public function del($key, $storeType = null) {
			$key = $this->getKeyGenerator()
				->createKey($key, $storeType);
			return $this->delete($key);
		}

		/** @inheritdoc */
		public function deleteSql($query) {
			$key = $this->getKeyGenerator()
				->createKeyForQuery($query);
			return $this->delete($key);
		}

		/** @inheritdoc */
		public function flush() {
			return $this->getCacheEngine()
				->flush();
		}

		/** @inheritdoc */
		public function getCacheEngineList() {
			$list = [
				databaseCacheEngine::NAME,
				redisCacheEngine::NAME,
				memcacheCacheEngine::NAME,
				memcachedCacheEngine::NAME,
				fsCacheEngine::NAME
			];

			return array_filter($list, [$this, 'checkEngine']);
		}

		/** @inheritdoc */
		public function getCacheEngineName() {
			return $this->getCacheEngine()
				->getName();
		}

		/** @inheritdoc */
		public function switchCacheEngine($name) {
			if (!$name) {
				return $this->saveCacheEngineName('');
			}

			if ($this->checkEngine($name)) {
				$this->flush();
				$this->saveCacheEngineName($name);
			}

			return $this;
		}

		/** Определяет корректен ли ip адрес */
		protected function isCorrectIp() {
			$filterIpList = (array) $this->getConfiguration()
				->get('cache', 'filter.ip');
			$request = Service::Request();

			return !in_array($request->remoteAddress(), $filterIpList);
		}

		/** Определяет корректен ли режим работы системы */
		protected function isCorrectMode() {
			return $this->getModeDetector()->isSite();
		}

		/**
		 * Определяет используемое хранилище кеша и возвращает его название
		 * @return string
		 */
		protected function detectCacheEngine() {
			$storedEngineName = $this->loadCacheEngineName();

			if ($this->checkEngine($storedEngineName)) {
				return $storedEngineName;
			}

			$storedEngineName = ($storedEngineName == 'auto') ? $this->autoDetectCacheEngine() : $storedEngineName;
			$storedEngineName = ($storedEngineName == 'none') ? nullCacheEngine::NAME : $storedEngineName;

			return $storedEngineName ?: nullCacheEngine::NAME;
		}

		/**
		 * Выбирает наиболее подходящее хранилище кеша и возвращает его название
		 * @return string
		 */
		protected function autoDetectCacheEngine() {
			foreach ($this->getCacheEngineList() as $cacheEngineName) {
				if ($cacheEngineName == fsCacheEngine::NAME) {
					continue;
				}

				return $cacheEngineName;
			}

			return nullCacheEngine::NAME;
		}

		/**
		 * Определяет доступно ли хранилище с заданным именем
		 * @param string $engineName название хранилища
		 * @return bool
		 */
		protected function checkEngine($engineName) {
			switch ($engineName) {
				case memcachedCacheEngine::NAME: {
					return class_exists('Memcached');
				}

				case memcacheCacheEngine::NAME: {
					return class_exists('Memcache');
				}

				case redisCacheEngine::NAME: {
					return class_exists('Redis');
				}

				case nullCacheEngine::NAME:
				case arrayCacheEngine::NAME:
				case fsCacheEngine::NAME:
				case databaseCacheEngine::NAME: {
					return true;
				}

				default: {
					return false;
				}
			}
		}

		/**
		 * Сохраняет название текущего хранилища
		 * @param string $name название хранилища
		 * @return $this
		 */
		protected function saveCacheEngineName($name) {
			$config = $this->getConfiguration();
			$config->set('cache', 'engine', $name);
			$config->save();
			return $this;
		}

		/**
		 * Загружает название текущего хранилища
		 * @return string название текущего cacheEngine
		 */
		protected function loadCacheEngineName() {
			return $this->getConfiguration()
				->get('cache', 'engine');
		}

		/**
		 * Сохраняет данные в кеш
		 * @param string $key ключ
		 * @param mixed $data данные
		 * @param int $expire время жизни
		 * @return bool
		 */
		protected function set($key, $data, $expire) {
			if (!$this->isCacheEnabled() || $this->isDisabled() || !$expire) {
				return false;
			}

			$expire = $this->getTimeToLive($expire);

			return $this->getCacheEngine()
				->saveRawData($key, $data, $expire);
		}

		/**
		 * Загружает данные из кеша
		 * @param string $key ключ
		 * @return mixed
		 */
		protected function get($key) {
			if (!$this->isCacheEnabled() || $this->isDisabled()) {
				return false;
			}

			return $this->getCacheEngine()
				->loadRawData($key);
		}

		/**
		 * Удаляет данные из кеша по ключу
		 * @param string $key ключ
		 * @return bool|null
		 */
		protected function delete($key) {
			if (!$this->isCacheEnabled() || $this->isDisabled()) {
				return false;
			}

			return $this->getCacheEngine()
				->delete($key);
		}

		/** Инициализирует класс */
		private function init() {
			$engineFactory = $this->getEngineFactory();

			try {
				$name = $this->detectCacheEngine();
				$cacheEngine = $engineFactory->create($name);
			} catch (coreException $exception) {
				umiExceptionHandler::report($exception);
				$cacheEngine = $engineFactory->create(nullCacheEngine::NAME);
			}

			$this->setCacheEngine($cacheEngine);

			if ($this->isCacheEnabled() && !($this->isCorrectIp() && $this->isCorrectMode())) {
				$this->setDisabled();
			}

			$validator = $this->getKeyValidatorFactory()
				->create();
			$this->setKeyValidator($validator);
		}

		/**
		 * Устанавливает текущую реализацию кеширования
		 * @param iCacheEngine $engine
		 * @return $this
		 */
		private function setCacheEngine(iCacheEngine $engine) {
			$this->cacheEngine = $engine;
			return $this;
		}

		/**
		 * Пытается вернуть время жизни кеша из конфигурации системы,
		 * в случае неудачи - возвращает переданное значение
		 * @param int $expire время жизни кеша
		 * @return int
		 */
		private function getTimeToLive($expire) {
			if ($expire !== self::DEFAULT_TIME_TO_LIVE) {
				return (int) $expire;
			}

			$config = $this->getConfiguration();

			if ($config->get('cache', 'streams.cache-enabled')) {
				$newExpire = (int) $config->get('cache', 'streams.cache-lifetime');

				if ($newExpire > 0) {
					$expire = $newExpire;
				}
			}

			return $expire;
		}

		/**
		 * Возвращает хранилище кеша
		 * @return iCacheEngine
		 */
		private function getCacheEngine() {
			return $this->cacheEngine;
		}

		/**
		 * Возвращает фабрику хранилищ кеша
		 * @return iEngineFactory
		 */
		private function getEngineFactory() {
			return $this->engineFactory;
		}

		/**
		 * Возвращает генератор ключей
		 * @return iGenerator
		 */
		private function getKeyGenerator() {
			return $this->keyGenerator;
		}

		/**
		 * Возвращает конфигурация
		 * @return iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает фабрику валидиторов ключей кеша
		 * @return iFactory
		 */
		private function getKeyValidatorFactory() {
			return $this->keyValidatorFactory;
		}

		/**
		 * Возвращает валидатор ключей
		 * @return iValidator
		 */
		private function getKeyValidator() {
			return $this->keyValidator;
		}

		/**
		 * Определяет валиден ли ключ.
		 * @param string $key ключа кеша
		 * @return bool
		 */
		private function isValidKey($key) {
			return $this->getKeyValidator()
				->isValid($key);
		}

		/**
		 * Устанавливает валидатор ключей
		 * @param iValidator $validator валидатор
		 * @return $this
		 */
		private function setKeyValidator(iValidator $validator) {
			$this->keyValidator = $validator;
			return $this;
		}

		/**
		 * Определяет приостановлена ли работа кеша
		 * @return bool
		 */
		private function isDisabled() {
			return $this->disabled;
		}

		/**
		 * Возвращает определителя режима работы системы
		 * @return iDetector
		 */
		private function getModeDetector() {
			return $this->modeDetector;
		}

		/**
		 * @deprecated
		 * @param string $key
		 * @param mixed $data
		 * @param int $expire
		 * @return mixed
		 */
		public function saveObject($key, $data, $expire = self::DEFAULT_TIME_TO_LIVE) {
			return $this->saveData($key, $data, $expire);
		}

		/**
		 * @deprecated
		 * @param string $key
		 * @param mixed $data
		 * @param int $expire
		 * @return mixed
		 */
		public function saveElement($key, $data, $expire = self::DEFAULT_TIME_TO_LIVE) {
			return $this->saveData($key, $data, $expire);
		}

		/**
		 * @deprecated
		 * @return mixed
		 */
		public function getIsConnected() {
			return $this->isCacheEnabled();
		}

		/**
		 * @deprecated
		 * @param array|null $engineList
		 * @return mixed
		 */
		public function chooseCacheEngine(array $engineList = null) {
			$engineList = $engineList ?: $this->getCacheEngineList();
			return array_shift($engineList);
		}

		/**
		 * @deprecated
		 * @param bool $enabledOnly
		 * @return mixed
		 */
		public function getPriorityEnginesList($enabledOnly = false) {
			return $this->getCacheEngineList();
		}

		/**
		 * @deprecated
		 * @return mixed
		 */
		public function getCurrentCacheEngineName() {
			return $this->getCacheEngineName();
		}

		/**
		 * @deprecated
		 * @param string $key
		 * @param bool $addSuffix
		 * @return mixed
		 */
		public function deleteKey($key, $addSuffix = false) {
			return $this->del($key);
		}

		/**
		 * @deprecated
		 * @param bool $flag
		 * @return mixed
		 */
		public function makeSleep($flag = false) {
			return $this->setDisabled($flag);
		}

		/**
		 * @deprecated
		 * @param string|null $name
		 * @return iCacheFrontend
		 */
		public static function getInstance($name = null) {
			return Service::CacheFrontend();
		}

		/**
		 * @deprecated
		 * @return int
		 */
		public function getCacheSize() {
			return 0;
		}

		/**
		 * @deprecated
		 * @return mixed
		 */
		public function doPeriodicOperations() {
			return null;
		}
	}
