<?php

	/** Трейт файла состояния атомарного действия */
	trait tAtomicOperationStateFile {

		use tUmiConfigInjector;

		/**
		 * Возвращает путь до файла состояния
		 * @param iAtomicOperation $atomicOperation атомарное действие
		 * @return string
		 */
		protected function getFilePath(iAtomicOperation $atomicOperation) {
			$rootTempDirectory = $this->getConfiguration()
				->includeParam('sys-temp-path');

			$className = trimNameSpace(get_class($atomicOperation));
			$name = trimNameSpace($atomicOperation->getName());

			return $rootTempDirectory . '/manifest/' . $className . '_' . $name;
		}
	}
