<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher;

	use UmiCms\System\Import\UmiDump\Demolisher;
	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\System\Import\UmiDump\Helper\File\Path\iConverter as FilePathConverter;

	/**
	 * Абстрактный класс удаления элементов файловой системы.
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	abstract class FileSystem extends Demolisher implements iFileSystem {

		/** @var string $rootPath абсолютный путь до корневой директории системы */
		private $rootPath;

		/** @var DirectoryFactory $directoryFactory фабрика директорий */
		private $directoryFactory;

		/** @var FilePathConverter $filePathConverter конвертер файловых путей */
		private $filePathConverter;

		/** @inheritdoc */
		public function setRootPath($path) {
			$this->rootPath = $path;
			return $this;
		}

		/**
		 * Устанавливает конвертер файловых путей
		 * @param FilePathConverter $filePathConverter конвертер файловых путей
		 * @return iFileSystem
		 */
		public function setFilePathConverter(FilePathConverter $filePathConverter) {
			$this->filePathConverter = $filePathConverter;
			return $this;
		}

		/**
		 * Устанавливает фабрику директорий
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 * @return $this
		 */
		protected function setDirectoryFactory(DirectoryFactory $directoryFactory) {
			$this->directoryFactory = $directoryFactory;
			return $this;
		}

		/**
		 * Возвращает фабрику директорий
		 * @return DirectoryFactory
		 * @throws \RequiredPropertyHasNoValueException
		 */
		protected function getDirectoryFactory() {
			if (!$this->directoryFactory instanceof DirectoryFactory) {
				throw new \RequiredPropertyHasNoValueException('You should inject DirectoryFactory first');
			}

			return $this->directoryFactory;
		}

		/**
		 * Возвращает путь до удаляемого объекта файловой системы
		 * @param string $originalPath оригинальный путь
		 * @return string
		 */
		protected function getDestinationPath($originalPath) {
			$absolutePath = $this->buildAbsolutePath($originalPath);
			$originalSourceName = $this->getOriginalSourceName();
			$actualSourceName = $this->getSourceName();

			if ($originalSourceName !== $actualSourceName) {
				$pathWithPostfix = $this->getFilePathConverter()
					->setSuffix($actualSourceName)
					->convert($absolutePath);

				if (file_exists($pathWithPostfix)) {
					return $pathWithPostfix;
				}
			}

			return $absolutePath;
		}

		/**
		 * Удаляет директорию
		 * @param string $path путь до директории
		 * @return bool
		 */
		protected function deleteDirectory($path) {
			$directory = $this->getDirectoryFactory()
				->create($path);

			if (!$directory->isExists()) {
				$this->pushLog(sprintf('Directory "%s" not exists', $directory->getPath()));
				return false;
			}

			$status = $directory->deleteEmptyDirectory() ? 'was deleted' : 'was not deleted';
			$this->pushLog(sprintf('Directory "%s" %s', $directory->getPath(), $status));

			return $this->deleteDirectory($directory->getParentPath());
		}

		/**
		 * Возвращает абсолютный путь до корневой директории системы
		 * @return string
		 */
		protected function getRootPath() {
			return rtrim($this->rootPath ?: CURRENT_WORKING_DIR, '/');
		}

		/**
		 * Формирует абсолютный путь до файла
		 * @param string $localPath путь до файла, относительно корня
		 * @return string
		 */
		protected function buildAbsolutePath($localPath) {
			return $this->getRootPath() . '/' . ltrim($localPath, '/');
		}

		/**
		 * Возвращает конвертер файлового пути
		 * @return FilePathConverter
		 */
		private function getFilePathConverter() {
			return $this->filePathConverter;
		}
	}
