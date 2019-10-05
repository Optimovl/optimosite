<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\File;

	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;
	use UmiCms\Classes\System\Utils\QuickExchange\Source\iDetector as SourceDetector;
	use UmiCms\System\Response\iFacade as Response;

	/**
	 * Интерфейс инициатора скачивания csv файла
	 * @package UmiCms\Classes\System\Utils\QuickExchange\File
	 */
	interface iDownloader {

		/**
		 * Конструктор
		 * @param SourceDetector $sourceDetector определитель источника
		 * @param FileFactory $fileFactory фабрика файлов
		 * @param Response $response фасад вывода
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(
			SourceDetector $sourceDetector,
			FileFactory $fileFactory,
			Response $response,
			\iConfiguration $configuration
		);

		/** Завершает запрос предложением клиенту (браузеру) скачать файл */
		public function download();
	}
