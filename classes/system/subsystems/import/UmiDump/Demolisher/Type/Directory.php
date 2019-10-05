<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\System\Import\UmiDump\Demolisher\FileSystem;

	/**
	 * Класс удаления директорий
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Part
	 */
	class Directory extends FileSystem {

		/**
		 * Конструктор
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 */
		public function __construct(DirectoryFactory $directoryFactory) {
			$this->setDirectoryFactory($directoryFactory);
		}

		/** @inheritdoc */
		protected function execute() {
			foreach ($this->sortPathListByLength($this->getDirectoryPathList()) as $path) {
				$destinationPath = $this->getDestinationPath($path);
				$this->deleteDirectory($destinationPath);
			}
		}

		/**
		 * Сортирует список относительных путей удаляемых директорий по убыванию длин путей
		 * @param array $pathList относительных путей удаляемых директорий
		 * @return array
		 */
		private function sortPathListByLength(array $pathList) {
			usort($pathList, function($firstPath, $secondPath) {
				$firstPathLength = mb_strlen($firstPath);
				$secondPathLength = mb_strlen($secondPath);

				if ($firstPathLength == $secondPathLength) {
					return 0;
				}

				return ($firstPathLength > $secondPathLength) ? -1 : 1;
			});
			return $pathList;
		}

		/**
		 * Возвращает список относительных путей удаляемых директорий
		 * @return string[]
		 */
		private function getDirectoryPathList() {
			return $this->getNodeValueList('/umidump/directories/directory');
		}
	}
