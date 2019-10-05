<?php

	/** Фабрика обработчиков хода выполнения атомарного действия */
	class AtomicOperationCallbackFactory implements iAtomicOperationCallbackFactory {

		/**
		 * Создает обработчик хода выполнения атомарного действия
		 * @param string $type тип обработчика
		 * @return iAtomicOperationCallback
		 * @throws Exception
		 */
		public function create($type = iAtomicOperationCallbackFactory::COMMON) {

			if (!is_string($type) || empty($type)) {
				throw new Exception('Wrong type ' . $type);
			}

			$filePath = $this->getFilePath($type);

			return $this->includeClass($filePath)
				->instantiateClass($type);
		}

		/**
		 * Возвращает путь до файла с реализацией обработчика манифеста
		 * @param string $type тип обработчика манифеста
		 * @return string
		 */
		private function getFilePath($type) {
			return SYS_KERNEL_PATH . 'subsystems/manifest/classes/callback/' . $type . 'AtomicOperationCallback.php';
		}

		/**
		 * Возвращает имя класс обработчика манифеста
		 * @param string $type тип обработчика манифеста
		 * @return string
		 */
		private function getClassName($type) {
			return $type . 'AtomicOperationCallback';
		}

		/**
		 * Подключает файл с реализацией обработчика манифеста
		 * @param string $filePath путь до файла
		 * @return $this
		 * @throws Exception
		 */
		private function includeClass($filePath) {
			if (!file_exists($filePath)) {
				throw new Exception('File not found: ' . $filePath);
			}

			include_once $filePath;
			return $this;
		}

		/**
		 * Инстанцирует класс обработчика манифеста
		 * @param string $type тип обработчика
		 * @return iAtomicOperationCallback
		 * @throws Exception
		 */
		private function instantiateClass($type) {
			$className = $this->getClassName($type);

			if (!class_exists($className)) {
				throw new Exception('Class not found: ' . $className);
			}

			return new $className();
		}
	}
