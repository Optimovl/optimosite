<?php

	namespace UmiCms\System\Cache\Browser\Engine;

	use UmiCms\System\Cache\Browser\iEngine;

	/**
	 * Класс фабрики реализаций браузерного кеширования
	 * @package UmiCms\System\Cache\Browser\Engine
	 */
	class Factory implements iFactory {

		/** @var \iServiceContainer контейнер сервисов */
		private $serviceContainer;

		/** @inheritdoc */
		public function __construct(\iServiceContainer $serviceContainer) {
			$this->serviceContainer = $serviceContainer;
		}

		/** @inheritdoc */
		public function create($name) {
			if (!is_string($name) || $name === '') {
				throw new \wrongParamException('Wrong browser cache engine name given');
			}

			$class = $this->getClass($name);

			if (!class_exists($class)) {
				throw new \wrongParamException(sprintf('Browser cache engine "%s" not exists', $name));
			}

			$request = $this->getService('Request');
			$response = $this->getService('Response');
			$configuration = $this->getService('Configuration');

			$instance = new $class($request, $response, $configuration);

			if (!$instance instanceof iEngine) {
				throw new \wrongParamException(sprintf('Browser cache engine "%s" must implement iEngine', $name));
			}

			return $instance;
		}

		/**
		 * Возвращает имя класса по имени буфера
		 * @param string $name имя буфера
		 * @return string
		 */
		private function getClass($name) {
			return sprintf('UmiCms\System\Cache\Browser\Engine\%s', $name);
		}

		/**
		 * Возвращает сервис по его имени
		 * @param string $name имя сервиса
		 * @return mixed
		 */
		private function getService($name) {
			return $this->getServiceContainer()
				->get($name);
		}

		/**
		 * Возвращает контейнер сервисов
		 * @return \iServiceContainer
		 */
		private function getServiceContainer() {
			return $this->serviceContainer;
		}
	}
