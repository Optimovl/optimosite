<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange;

	use UmiCms\Classes\System\Utils\QuickExchange\Csv\iExporter;
	use UmiCms\Classes\System\Utils\QuickExchange\Csv\iImporter;
	use UmiCms\Classes\System\Utils\QuickExchange\File\iDownloader;
	use UmiCms\Classes\System\Utils\QuickExchange\File\iUploader;
	use UmiCms\System\Response\iFacade as Response;

	/**
	 * Интерфейс фасада быстрого обмена данными в формате csv.
	 * Функционал задействован в табличном контроле административной панели.
	 * @package UmiCms\Classes\System\Utils\QuickExchange
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iExporter $exporter csv экспортер
		 * @param iImporter $importer csv импортер
		 * @param iDownloader $downloader инициатор скачивания файла
		 * @param iUploader $uploader инициатор загрузки файла
		 * @param \iConfiguration $configuration конфигурация
		 * @param Response $response фасад вывода
		 */
		public function __construct(
			iExporter $exporter,
			iImporter $importer,
			iDownloader $downloader,
			iUploader $uploader,
			\iConfiguration $configuration,
			Response $response
		);

		/**
		 * Устанавливает кодировку
		 * @param string $encoding кодировка csv файла (windows-1251 / utf-8)
		 * @return $this
		 */
		public function setEncoding($encoding = 'windows-1251');

		/**
		 * Загружает csv файл на сервер.
		 * Возвращает путь до загруженного файла в буфер вывода
		 */
		public function upload();

		/**
		 * Импортирует загруженный файл.
		 * Импорт производится в несколько итераций.
		 * Возвращает в буфер был ли импорт завершен.
		 * @param \selector $query выборка сущностей, с помощью которой определяется тип импортируемых
		 * сущностей (объекты/страницы) и идентификатор родителя (для страниц).
		 */
		public function import(\selector $query);

		/**
		 * Экспортирует сущности в csv файл.
		 * Экспорт производится в несколько итераций.
		 * Возвращает в буфер был ли экспорт завершен.
		 * @param \selector $query выборка сущностей, которые будут экспортированы
		 */
		public function export(\selector $query);

		/** Отправляет буфер вывода с предложением клиенту (браузеру) скачать csv файл */
		public function download();
	}
