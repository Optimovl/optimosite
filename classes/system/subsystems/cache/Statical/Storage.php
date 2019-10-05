<?php

	namespace UmiCms\System\Cache\Statical;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;

	/**
	 * Класс хранилища статического кеша
	 * @package UmiCms\System\Cache\Statical
	 */
	class Storage implements iStorage {

		/** @var \iConfiguration $config */
		private $config;

		/** @var FileFactory $fileFactory фабрика файлов */
		private $fileFactory;

		/** @var DirectoryFactory $directoryFactory фабрика директорий */
		private $directoryFactory;

		/** @inheritdoc */
		public function __construct(\iConfiguration $config, FileFactory $fileFactory, DirectoryFactory $directoryFactory) {
			$this->config = $config;
			$this->fileFactory = $fileFactory;
			$this->directoryFactory = $directoryFactory;
		}

		/** @inheritdoc */
		public function save($path, $content) {
			if (!$this->isValidString($path) || !$this->isValidString($content)) {
				return false;
			}

			$file = $this->getFile($path);

			$directoryPath = $file->getDirName() . '/';
			$directory = $this->getDirectoryFactory()
				->create($directoryPath);

			if (!$directory->isExists()) {
				$directory::requireFolder($directory->getPath());
				$directory->refresh();
			}

			return (bool) $file->putContent($content);
		}

		/** @inheritdoc */
		public function load($path) {
			if (!$this->isValidString($path)) {
				return false;
			}

			$file = $this->getFile($path);

			if (!$file->isExists() || !$file->isReadable()) {
				return false;
			}

			if ($file->getModifyTime() + $this->getTimeToLive() < time()) {
				$this->delete($path);
				return false;
			}

			return (string) $file->getContent();
		}

		/** @inheritdoc */
		public function delete($path) {
			if (!$this->isValidString($path)) {
				return false;
			}

			$file = $this->getFile($path);

			if (!$file->isExists()) {
				return false;
			}

			$isDeleted = $file->delete();

			$directoryPath = $file->getDirName() . '/';
			$this->deleteEmptyDirectory($directoryPath);

			return $isDeleted;
		}

		/** @inheritdoc */
		public function deleteForEveryQuery($path) {
			if (!$this->isValidString($path)) {
				return false;
			}

			$directoryPath = $this->getFile($path)
					->getDirName() . '/';
			$directory = $this->getDirectoryFactory()
				->create($directoryPath);

			if (!$directory->isExists()) {
				return false;
			}

			$fileFactory = $this->getFileFactory();

			foreach ($directory->getFiles() as $filePath) {
				$file = $fileFactory->create($filePath);

				if (!$file->isExists()) {
					continue;
				}

				$file->delete();
			}

			$this->deleteEmptyDirectory($directoryPath);

			return true;
		}

		/** @inheritdoc */
		public function flush() {
			$directory = $this->getRootDirectory();

			if ($directory->isExists() && $directory->isWritable()) {
				return $directory->deleteContent();
			}

			return false;
		}

		/** @inheritdoc */
		public function getTimeToLive() {
			switch ($this->getConfig()->get('cache', 'static.mode')) {
				case 'test': {
					return 10;
				}
				case 'short': {
					return 10 * 60;
				}
				case 'long': {
					return 3600 * 2 * 365;
				}
				default: {
					return 3600 * 24;
				}
			}
		}

		/**
		 * Ваилидирует строку
		 * @param mixed $value
		 * @return bool
		 */
		private function isValidString($value) {
			if (!is_string($value)) {
				return false;
			}

			$trimmedValue = trim($value);
			return $trimmedValue !== '';
		}

		/**
		 * Удаляет директорию, если она пуста.
		 * Вызывается рекурсивно
		 * @param string $path путь до директории
		 * @return bool
		 */
		private function deleteEmptyDirectory($path) {
			$directoryPath = rtrim($path, '/') . '/';
			$directory = $this->getDirectoryFactory()
				->create($directoryPath);

			if ($directory->isExists() && $directory->isWritable()) {
				$parentPath = realpath($directory->getPath() . '/../');
				$directory->deleteEmptyDirectory();
				return $this->deleteEmptyDirectory($parentPath);
			}

			return true;
		}

		/**
		 * Возвращает файл кеша
		 * @param string $path путь до кешируемой страницы
		 * @return \iUmiFile
		 */
		private function getFile($path) {
			$parsedPath = parse_url($path);
			$directory = $this->getDirectory($parsedPath);
			$path = $directory->getPath() . '/' . $this->getFileName($parsedPath);
			return $this->getFileFactory()
				->createByRawPath($path);
		}

		/**
		 * Возвращает директорию, в которой будет хранится файл для кеша
		 * @param array $parsedPath разобранный путь до кешируемой страницы
		 * @return \iUmiDirectory
		 */
		private function getDirectory(array $parsedPath) {
			$path = isset($parsedPath['path']) ? trim($parsedPath['path'], '/') : '';
			$path = empty($path) ? $path : $path . '/';

			$rootDirectory = $this->getRootDirectory();

			if (!$rootDirectory->isExists()) {
				$rootDirectory::requireFolder($rootDirectory->getPath());
				$rootDirectory->refresh();
			}

			$path = $rootDirectory->getPath() . '/' . $path;

			return $this->getDirectoryFactory()
				->create($path);
		}

		/**
		 * Возвращает имя файла кеша
		 * @param array $parsedPath разобранный путь до кешируемой страницы
		 * @return string
		 */
		private function getFileName(array $parsedPath) {
			$name = isset($parsedPath['query']) ? md5($parsedPath['query']) : 'index';
			return $name . '.html';
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfig() {
			return $this->config;
		}

		/**
		 * Возвращает фабрику файлов
		 * @return FileFactory
		 */
		private function getFileFactory() {
			return $this->fileFactory;
		}

		/**
		 * Возвращает фабрику директорий
		 * @return DirectoryFactory
		 */
		private function getDirectoryFactory() {
			return $this->directoryFactory;
		}

		/**
		 * Возвращает корневую директорию
		 * @return \iUmiDirectory
		 */
		private function getRootDirectory() {
			$config = $this->getConfig();
			$path = (string) $config->includeParam('system.static-cache');

			if (empty($path)) {
				$path = (string) $config->includeParam('sys-temp-path');
				$path = rtrim($path, '/') . '/static-cache';
			}

			$path = rtrim($path, '/') . '/';

			return $this->getDirectoryFactory()
				->create($path);
		}
	}
