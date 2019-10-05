<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\Source;

	/**
	 * Интерфейс определителя источника
	 * @package UmiCms\Classes\System\Utils\QuickExchange\Source
	 */
	interface iDetector {

		/**
		 * Констркутор
		 * @param \iCmsController $cmsController cms контроллер
		 */
		public function __construct(\iCmsController $cmsController);

		/**
		 * Определяет источник для импорта
		 * @return string
		 */
		public function detectForImport();

		/**
		 * Определяет источник для экспорта
		 * @return string
		 */
		public function detectForExport();
	}
