<?php

	namespace UmiCms\System\Cache\Statical;

	use UmiCms\System\Cache\Statical\Key\iGenerator;
	use UmiCms\System\Cache\Key\iValidator as KeyValidator;
	use UmiCms\System\Cache\Statical\Key\Validator\iFactory;
	use UmiCms\System\Cache\State\iValidator as StateValidator;

	/**
	 * Класс фасада над статическим кешем
	 * @package UmiCms\System\Cache\Statical
	 */
	class Facade implements iFacade {

		/** @var \iConfiguration $config конфигурация */
		private $config;

		/** @var StateValidator $stateValidator валидатор состояния */
		private $stateValidator;

		/** @var KeyValidator iValidator валидатор ключей */
		private $keyValidator;

		/** @var iGenerator $keyGenerator генератор ключей */
		private $keyGenerator;

		/** @var iStorage $storage хранилище */
		private $storage;

		/**
		 * Конструктор
		 * @param \iConfiguration $config
		 * @param StateValidator $stateValidator
		 * @param iFactory $keyValidatorFactory
		 * @param iGenerator $keyGenerator
		 * @param iStorage $storage
		 */
		public function __construct(
			\iConfiguration $config,
			StateValidator $stateValidator,
			iFactory $keyValidatorFactory,
			iGenerator $keyGenerator,
			iStorage $storage
		) {
			$this->config = $config;
			$this->stateValidator = $stateValidator;
			$this->keyValidator = $keyValidatorFactory->create();
			$this->keyGenerator = $keyGenerator;
			$this->storage = $storage;
		}

		/** @inheritdoc */
		public function save($content) {
			if (!$this->isCacheWorking()) {
				return false;
			}

			$key = $this->getKeyGenerator()
				->getKey();

			if (!$this->getKeyValidator()->isValid($key)) {
				return false;
			}

			return $this->getStorage()->save($key, $content);
		}

		/** @inheritdoc */
		public function load() {
			if (!$this->isCacheWorking()) {
				return false;
			}

			$key = $this->getKeyGenerator()
				->getKey();

			if (!$this->getKeyValidator()->isValid($key)) {
				return false;
			}

			$cache = $this->getStorage()->load($key);

			if (!is_string($cache)) {
				return false;
			}

			if ($this->isDebug()) {
				$cache .= self::DEBUG_SIGNATURE;
			}

			return $cache;
		}

		/** @inheritdoc */
		public function getTimeToLive() {
			return $this->getStorage()->getTimeToLive();
		}

		/** @inheritdoc */
		public function isEnabled() {
			return (bool) $this->getConfig()
				->get('cache', 'static.enabled');
		}

		/**
		 * Включает статический кеш
		 * @return $this
		 */
		public function enable() {
			$config = $this->getConfig();
			$config->set('cache', 'static.enabled', true);
			$config->save();
			return $this;
		}

		/**
		 * Выключает статический кеш
		 * @return $this
		 */
		public function disable() {
			$config = $this->getConfig();
			$config->set('cache', 'static.enabled', false);
			$config->save();
			return $this;
		}

		/** @inheritdoc */
		public function deletePageListCache(array $idList) {
			if (!$this->isEnabled()) {
				return false;
			}

			$keyGenerator = $this->getKeyGenerator();
			$storage = $this->getStorage();

			foreach ($idList as $id) {
				$keyList = $keyGenerator->getKeyList($id);

				foreach ($keyList as $key) {
					$storage->deleteForEveryQuery($key);
				}
			}

			return true;
		}

		/**
		 * Определяет работает ли кеширование
		 * @return bool
		 */
		private function isCacheWorking() {
			return $this->isEnabled() && $this->getStateValidator()->isValid();
		}

		/**
		 * Определяет включен ли режим отладки
		 * @return bool
		 */
		private function isDebug() {
			return (bool) $this->getConfig()
				->get('cache', 'static.debug');
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfig() {
			return $this->config;
		}

		/**
		 * Возвращает валидатор состояния
		 * @return StateValidator
		 */
		private function getStateValidator() {
			return $this->stateValidator;
		}

		/**
		 * Возвращает валидатор ключей
		 * @return KeyValidator
		 */
		private function getKeyValidator() {
			return $this->keyValidator;
		}

		/**
		 * Возвращает генератор ключей
		 * @return iGenerator
		 */
		private function getKeyGenerator() {
			return $this->keyGenerator;
		}

		/**
		 * Возвращает хранилище
		 * @return iStorage
		 */
		private function getStorage() {
			return $this->storage;
		}
	}
