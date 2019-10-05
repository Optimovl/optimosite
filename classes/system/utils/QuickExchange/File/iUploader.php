<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\File;

	use UmiCms\System\Request\iFacade as iRequest;

	/**
	 * Интерфейс инициатора загрузки файла
	 * @package UmiCms\Classes\System\Utils\QuickExchange\File
	 */
	interface iUploader {

		/**
		 * Конструктор
		 * @param iRequest $request фасад запроса
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(iRequest $request, \iConfiguration $configuration);

		/**
		 * Загружает файл на сервер
		 * @return \iUmiFile
		 */
		public function upload();
	}
