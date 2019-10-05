<?php

	namespace UmiCms\Utils\Logger;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;

	/**
	 * Класс фабрики логгера
	 * @package UmiCms\Utils\Logger
	 */
	class Factory implements iFactory {

		/** @var DirectoryFactory $directoryFactory фабрика директорий */
		private $directoryFactory;

		/**
		 * Конструктор
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 */
		public function __construct(DirectoryFactory $directoryFactory) {
			$this->directoryFactory = $directoryFactory;
		}

		/** @inheritdoc */
		public function create($directoryPath) {
			$directory = $this->getDirectoryFactory()
				->create($directoryPath);

			if (!$directory->isExists()) {
				$directory::requireFolder($directory->getPath());
			}

			return new \umiLogger($directory->getPath());
		}

		/**
		 * Возвращает фабрику директорий
		 * @return DirectoryFactory
		 */
		private function getDirectoryFactory() {
			return $this->directoryFactory;
		}
	}
