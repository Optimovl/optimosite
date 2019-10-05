<?php

	/** Фабрика манифестов */
	class ManifestFactory implements iManifestFactory {

		use tAtomicOperationStateFile;

		/** @var iBaseXmlConfigFactory $configFactory экземпляр фабрики менеджеров конфигураций в xml */
		private $configFactory;

		/** @var iAtomicOperationCallbackFactory $callbackFactory экземпляр фабрики обработчиков манифеста */
		private $callbackFactory;

		/** @var iManifestSourceFactory $sourceFactory экземпляр фабрики источников манифеста */
		private $sourceFactory;

		/**
		 * Конструктор
		 * @param iBaseXmlConfigFactory $configFactory экземпляр фабрики менеджеров конфигураций в xml
		 * @param iAtomicOperationCallbackFactory $callbackFactory экземпляр фабрики обработчиков манифеста
		 * @param iManifestSourceFactory $sourceFactory экземпляр фабрики источников манифеста
		 */
		public function __construct(
			iBaseXmlConfigFactory $configFactory,
			iAtomicOperationCallbackFactory $callbackFactory,
			iManifestSourceFactory $sourceFactory
		) {
			$this->configFactory = $configFactory;
			$this->callbackFactory = $callbackFactory;
			$this->sourceFactory = $sourceFactory;
		}

		/** @inheritdoc */
		public function create($configName, array $params = [], $callBackType = iAtomicOperationCallbackFactory::JSON) {
			$source = $this->getSourceFactory()
				->create();

			return $this->instantiateAndInitiateClass($configName, $source, $params, $callBackType);
		}

		/** @inheritdoc */
		public function createByModule(
			$configName,
			$module,
			array $params = [],
			$callBackType = iAtomicOperationCallbackFactory::COMMON
		) {
			$source = $this->getSourceFactory()
				->create(iManifestSourceFactory::MODULE, $module);

			return $this->instantiateAndInitiateClass($configName, $source, $params, $callBackType);
		}

		/** @inheritdoc */
		public function createBySolution(
			$configName,
			$solution,
			array $params = [],
			$callBackType = iAtomicOperationCallbackFactory::COMMON
		) {
			$source = $this->getSourceFactory()
				->create(iManifestSourceFactory::SOLUTION, $solution);

			return $this->instantiateAndInitiateClass($configName, $source, $params, $callBackType);
		}

		/**
		 * Инстанцирует и инициализирует экземпляр манифеста
		 * @param string $configName
		 * @param iManifestSource $source источник манифеста
		 * @param array $params параметры выполнения
		 * @param string $callBackType тип обработчика
		 * @return iManifest
		 */
		private function instantiateAndInitiateClass(
			$configName,
			iManifestSource $source,
			array $params,
			$callBackType
		) {
			$configPath = $source->getConfigFilePath($configName);

			$config = $this->getConfigFactory()
				->create($configPath);

			$callback = $this->getCallbackFactory()
				->create($callBackType);

			$manifest = new Manifest($config, $source, $params);
			$manifest->setCallback($callback);
			$manifest->loadTransactions();

			$stateFilePath = $this->getFilePath($manifest);
			$manifest->setStatePath($stateFilePath);
			$manifest->loadState();

			return $manifest;
		}

		/**
		 * Возвращает экземпляр фабрики менеджеров конфигураций в xml
		 * @return iBaseXmlConfigFactory
		 */
		private function getConfigFactory() {
			return $this->configFactory;
		}

		/**
		 * Возвращает экземпляр фабрики обработчиков манифеста
		 * @return iAtomicOperationCallbackFactory
		 */
		private function getCallbackFactory() {
			return $this->callbackFactory;
		}

		/**
		 * Возвращает экземпляр фабрики источников манифеста
		 * @return iManifestSourceFactory
		 */
		private function getSourceFactory() {
			return $this->sourceFactory;
		}
	}
