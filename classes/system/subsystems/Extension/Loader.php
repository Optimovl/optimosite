<?php

	namespace UmiCms\System\Extension;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;

	/**
	 * Класс загрузчика расширений модулей
	 * @package UmiCms\System\Extension
	 */
	class Loader implements iLoader {

		/** @var DirectoryFactory $directoryFactory фабрика директорий */
		private $directoryFactory;

		/** @var FileFactory $fileFactory фабрика файлов */
		private $fileFactory;

		/** @var \def_module $module модуль */
		private $module;

		/** @inheritdoc */
		public function __construct(DirectoryFactory $directoryFactory, FileFactory $fileFactory) {
			$this->directoryFactory = $directoryFactory;
			$this->fileFactory = $fileFactory;
		}

		/** @inheritdoc */
		public function setModule(\def_module $module) {
			$this->module = $module;
			return $this;
		}

		/** @inheritdoc */
		public function loadCommon() {
			$directory = $this->getDirectory();

			if (!$directory->isExists()) {
				return $this;
			}

			$this->includeList($directory, 'includes_*.php');
			$this->implementList($directory, 'common_*.php');
			return $this;
		}

		/** @inheritdoc */
		public function loadAdmin() {
			$directory = $this->getDirectory();

			if (!$directory->isExists()) {
				return $this;
			}

			$this->implementList($directory, 'admin_*.php');
			return $this;
		}

		/** @inheritdoc */
		public function loadSite() {
			$directory = $this->getDirectory();

			if (!$directory->isExists()) {
				return $this;
			}

			$this->implementList($directory, 'site_*.php');
			$this->implementList($directory, '__events_*.php');
			return $this;
		}

		/**
		 * Возвращает директорию с расширениями
		 * @return \iUmiDirectory
		 */
		private function getDirectory() {
			$path = sprintf('%s%s/ext/', SYS_MODULES_PATH, get_class($this->getModule()));
			return $this->getDirectoryFactory()
				->create($path);
		}

		/**
		 * Подключает расширения в модуль
		 * @param \iUmiDirectory $directory директория с расширениями
		 * @param string $pattern шаблон поиска файлов
		 */
		private function implementList(\iUmiDirectory $directory, $pattern) {
			$fileFactory = $this->getFileFactory();
			$module = $this->getModule();

			foreach ($directory->getList($pattern) as $filePath) {
				$file = $fileFactory->create($filePath);
				$fileName = $file->getFileName();
				$className = str_replace('.php', '', $fileName);
				$module->__loadLib($fileName, $directory->getPath() . '/');
				$module->__implement($className, true);
			}
		}

		/**
		 * Подключает файлы инициализации расширений
		 * @param \iUmiDirectory $directory директория с расширениями
		 * @param string $pattern шаблон поиска файлов
		 */
		private function includeList(\iUmiDirectory $directory, $pattern) {
			foreach ($directory->getList($pattern) as $filePath) {
				/** @noinspection PhpIncludeInspection */
				require_once $filePath;
			}
		}

		/**
		 * Возвращает модуль
		 * @return \def_module
		 * @throws \DependencyNotInjectedException
		 */
		private function getModule() {
			if (!$this->module instanceof \def_module) {
				throw new \DependencyNotInjectedException('You should inject module first!');
			}

			return $this->module;
		}

		/**
		 * Возвращает фабрику директорий
		 * @return DirectoryFactory
		 */
		private function getDirectoryFactory() {
			return $this->directoryFactory;
		}

		/**
		 * Возвращает фабрику файлов
		 * @return FileFactory
		 */
		private function getFileFactory() {
			return $this->fileFactory;
		}
	}
