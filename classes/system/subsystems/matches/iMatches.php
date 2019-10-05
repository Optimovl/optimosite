<?php

	/**
	 * Механизм альтернативной маршрутизации адресов: Sitemap
	 * @link http://api.docs.umi-cms.ru/razrabotka_nestandartnogo_funkcionala/alternativnaya_marshrutizaciya_adresov_sitemap/
	 */
	interface iMatches {

		/**
		 * Конструктор.
		 * Пытается подключить файл с адресами из директории шаблона дизайна.
		 * Если такого файла нет - подключает файл из системной директории.
		 *
		 * @param string $fileName название файла с адресами
		 */
		public function __construct($fileName = 'sitemap.xml');

		/**
		 * Устанавливает текущий путь
		 * @param string $uri запрошенный путь
		 * @return mixed
		 */
		public function setCurrentURI($uri);

		/**
		 * Запускает маршрутизатор.
		 * Возвращает результат разбора адреса.
		 *
		 * @param bool $flushToBuffer определяет, нужно ли вывести результат в буфер
		 * и завершить на этом запрос.
		 *
		 * @return mixed
		 */
		public function execute($flushToBuffer = true);

	}
