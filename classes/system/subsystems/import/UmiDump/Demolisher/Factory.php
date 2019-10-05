<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	/**
	 * Фабрика класса удаления группы однородных данных
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	class Factory implements iFactory {

		/** @var \iServiceContainer $serviceContainer контейнер сервисов */
		private $serviceContainer;

		/** @inheritdoc */
		public function __construct(\iServiceContainer $serviceContainer) {
			$this->serviceContainer = $serviceContainer;
		}

		/** @inheritdoc */
		public function create($name) {
			$serviceName = $this->getServiceName($name);
			return $this->createByServiceName($serviceName);
		}

		/** @inheritdoc */
		private function createByServiceName($serviceName) {
			return $this->getServiceContainer()
				->get($serviceName);
		}

		/**
		 * Возвращает имя сервиса, соответствующего имени группы удаляемых однородных данных
		 * @param string $partName имя группы удаляемых однородных данных (Directory, Domain, Template etc.)
		 * @return string
		 */
		private function getServiceName($partName) {
			return sprintf('UmiDump%sDemolisher', $partName);
		}

		/**
		 * Возвращает сервис контейнер
		 * @return \iServiceContainer
		 */
		private function getServiceContainer() {
			return $this->serviceContainer;
		}
	}
