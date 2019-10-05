<?php

	/** Интерфейс фабрики манифестов */
	interface iManifestFactory {

		/**
		 * Создает манифест
		 * @param string $configName имя манифеста
		 * @param array $params параметры выполнения
		 * @param string $callBackType тип обработчика
		 * @return iManifest
		 */
		public function create($configName, array $params = [], $callBackType = iAtomicOperationCallbackFactory::JSON);

		/**
		 * Создает манифест модуля
		 * @param string $configName имя манифеста
		 * @param string $module название модуля
		 * @param array $params параметры выполнения
		 * @param string $callBackType тип обработчика
		 * @return iManifest
		 */
		public function createByModule(
			$configName,
			$module,
			array $params = [],
			$callBackType = iAtomicOperationCallbackFactory::COMMON
		);

		/**
		 * Создает манифест решения
		 * @param string $configName имя манифеста
		 * @param string $solution название решения
		 * @param array $params параметры выполнения
		 * @param string $callBackType тип обработчика
		 * @return iManifest
		 */
		public function createBySolution(
			$configName,
			$solution,
			array $params = [],
			$callBackType = iAtomicOperationCallbackFactory::COMMON
		);
	}
