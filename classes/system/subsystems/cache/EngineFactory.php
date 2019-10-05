<?php

	namespace UmiCms\System\Cache;

	/**
	 * Фабрика хранилищ кеша
	 * @package UmiCms\System\Cache
	 */
	class EngineFactory implements iEngineFactory {

		/** @var \iCacheEngine[] $engineList список загруженных хранилищ */
		private $engineList = [];

		/** @inheritdoc */
		public function create($name = \databaseCacheEngine::NAME) {
			if ($this->isLoaded($name)) {
				return $this->getLoadedEngine($name);
			}

			$engine = $this->loadEngine($name);
			$this->setLoadedEngine($engine);

			return $engine;
		}

		/** @inheritdoc */
		public function createNew($name) {
			$engine = $this->loadEngine($name);

			if (!$this->isLoaded($name)) {
				$this->setLoadedEngine($engine);
			}

			return $engine;
		}

		/**
		 * Определяет было ли загружено хранилище кеша с заданным названием
		 * @param string $name название хранилища
		 * @return bool
		 */
		private function isLoaded($name) {
			return isset($this->engineList[$name]);
		}

		/**
		 * Возвращает загруженное хранилище кеша с заданным названием
		 * @param string $name название хранилища
		 * @return \iCacheEngine
		 * @throws \coreException
		 */
		private function getLoadedEngine($name) {
			if (!$this->isLoaded($name)) {
				throw new \coreException(sprintf('Cache engine "%s" not loaded', $name));
			}

			return $this->engineList[$name];
		}

		/**
		 * Добавляет хранилище кеша в список загруженные хранилищ
		 * @param \iCacheEngine $engine хранилище кеша
		 * @return $this
		 */
		private function setLoadedEngine(\iCacheEngine $engine) {
			$this->engineList[$engine->getName()] = $engine;
			return $this;
		}

		/**
		 * Возвращает класс хранилища кеша
		 * @param string $name название хранилища
		 * @return string
		 */
		private function getEngineClass($name) {
			return $name . 'CacheEngine';
		}

		/**
		 * Возвращает путь до файла с классом хранилища
		 * @param string $name название хранилища
		 * @return string
		 */
		private function getEngineFilePath($name) {
			return SYS_KERNEL_PATH . 'subsystems/cache/engines/' . $name . '.php';
		}

		/**
		 * Загружает хранилище с заданным названием
		 * @param string $name название хранилища
		 * @return \iCacheEngine
		 * @throws \coreException
		 */
		private function loadEngine($name) {
			$className = $this->getEngineClass($name);

			if (class_exists($className)) {
				return new $className;
			}

			$filePath = $this->getEngineFilePath($name);

			if (!file_exists($filePath)) {
				throw new \coreException(sprintf('File "%s" not found', $filePath));
			}

			/** @noinspection PhpIncludeInspection */
			include_once $filePath;

			if (class_exists($className)) {
				return new $className;
			}

			throw new \coreException(sprintf('Cache engine "%s" not found', $name));
		}
	}
