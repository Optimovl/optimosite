<?php

	/** Фабрика команд */
	class ActionFactory implements iActionFactory {

		use tAtomicOperationStateFile;

		/** @inheritdoc */
		public function create($name, array $params = [], $filePath) {
			$action = $this->includeClass($filePath)
				->instantiateClass($name, $params);

			if ($action instanceof iStateFileWorker) {
				$stateFilePath = $this->getFilePath($action);
				$action->setStatePath($stateFilePath);
				$action->loadState();
			}

			return $action;
		}

		/**
		 * Подключает файл с реализацией команды
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
		 * Возвращает имя класса команды
		 * @param string $name имя команды
		 * @return string
		 */
		private function getClassName($name) {
			return $name . 'Action';
		}

		/**
		 * Инстанцирует класс команды
		 * @param string $name имя команды
		 * @param array $params параметры команды
		 * @return iAction
		 * @throws Exception
		 */
		private function instantiateClass($name, array $params = []) {
			$className = $this->getClassName($name);

			if (!class_exists($className)) {
				throw new Exception('Class not found: ' . $className);
			}

			return new $className($name, $params);
		}
	}
