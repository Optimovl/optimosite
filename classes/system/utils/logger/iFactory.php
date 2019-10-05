<?php

	namespace UmiCms\Utils\Logger;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;

	/**
	 * Интерфейс фабрики логгера
	 * @package UmiCms\Utils\Logger
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 */
		public function __construct(DirectoryFactory $directoryFactory);

		/**
		 * Создает логгер
		 * @param string $directoryPath путь до директории с логами
		 * @return \iUmiLogger
		 */
		public function create($directoryPath);
	}
