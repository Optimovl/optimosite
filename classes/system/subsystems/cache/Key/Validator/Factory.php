<?php

	namespace UmiCms\System\Cache\Key\Validator;

	use UmiCms\System\Cache\Key\iValidator;

	/**
	 * Фабрика валидаторов ключей кеша
	 * @package UmiCms\System\Cache\Key\Validator
	 */
	class Factory implements iFactory {

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @inheritdoc */
		public function __construct(\iConfiguration $configuration) {
			$this->configuration = $configuration;
		}

		/** @inheritdoc */
		public function create($name = null) {
			if ($name === null) {
				$name = $this->getDefaultKeyValidatorName();
			}

			try {
				$instance = $this->createByName($name);
			} catch (\coreException $exception) {
				\umiExceptionHandler::report($exception);
				$instance = $this->createByName();
			}

			return $instance;
		}

		/**
		 * Возвращает имя класса валидатора по умолчанию (BlackList/WhiteList/MixedList)
		 * @return string
		 */
		protected function getDefaultKeyValidatorName() {
			return (string) $this->getConfiguration()
				->get('cache', 'key-validator');
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		protected function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Создает валидатор ключей кеша
		 * @param string $name класс валидатора (BlackList/WhiteList/MixedList),
		 * @return iValidator
		 * @throws \coreException
		 */
		private function createByName($name = 'WhiteList') {
			$className = $this->buildClassName($name);

			if (!class_exists($className)) {
				throw new \coreException(sprintf('Cache key validator "%s" not found', $className));
			}

			$validator = new $className($this->getConfiguration());

			if (!$validator instanceof iValidator) {
				throw new \coreException(sprintf('Class "%s" is not cache key validator', $className));
			}

			return $validator;
		}

		/**
		 * Формирует имя класса валидатора
		 * @param string $class класс валидатора (BlackList/WhiteList/MixedList)
		 * @return string
		 */
		private function buildClassName($class) {
			return sprintf('UmiCms\System\Cache\Key\Validator\%s', $class);
		}
	}
