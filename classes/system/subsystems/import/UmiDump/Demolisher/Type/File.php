<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;
	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\System\Import\UmiDump\Demolisher\FileSystem;

	/**
	 * Класс удаления файлов
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Part
	 */
	class File extends FileSystem {

		/** @var FileFactory $fileFactory фабрика файлов */
		private $fileFactory;

		/**
		 * Конструктор
		 * @param FileFactory $fileFactory фабрика файлов
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 */
		public function __construct(FileFactory $fileFactory, DirectoryFactory $directoryFactory) {
			$this->fileFactory = $fileFactory;
			$this->setDirectoryFactory($directoryFactory);
		}

		/** @inheritdoc */
		protected function execute() {
			$fileFactory = $this->getFileFactory();

			foreach ($this->getFilePathList() as $path) {
				$destinationPath = $this->getDestinationPath($path);
				$file = $fileFactory->create($destinationPath);

				if (!$file->isExists()) {
					$this->pushLog(sprintf('File "%s" not exists', $file->getFilePath()));
					continue;
				}

				$status = $file->delete() ? 'was deleted' : 'was not deleted';
				$this->pushLog(sprintf('File "%s" %s', $file->getFilePath(), $status));

				$this->deleteDirectory($file->getDirName());
			}
		}

		/**
		 * Возвращает список относительных путей удаляемых файлов
		 * @return string[]
		 */
		private function getFilePathList() {
			return $this->getNodeValueList('/umidump/files/file');
		}

		/**
		 * Возвращает фабрику файлов
		 * @return FileFactory
		 */
		private function getFileFactory() {
			return $this->fileFactory;
		}
	}
