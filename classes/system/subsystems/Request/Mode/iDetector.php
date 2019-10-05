<?php

	namespace UmiCms\System\Request\Mode;

	use UmiCms\System\Request\Path\iResolver;

	/**
	 * Интерфейс определителя режима работы системы
	 * @package UmiCms\System\Request\Mode
	 */
	interface iDetector {

		/** @const string ADMIN_MODE режим панели администрирования */
		const ADMIN_MODE = 'admin';

		/** @const string SITE_MODE режим сайта */
		const SITE_MODE = '';

		/** @const string CLI_MODE режим консоли */
		const CLI_MODE = 'cli';

		/**
		 * Конструктор
		 * @param iResolver $pathResolver распознаватель обрабатываемого пути
		 */
		public function __construct(iResolver $pathResolver);

		/**
		 * Определяет режим
		 * @return string
		 */
		public function detect();

		/**
		 * Определяет работает ли система режиме панели администрирования
		 * @return bool
		 */
		public function isAdmin();

		/**
		 * Определяет работает ли система режиме сайта
		 * @return bool
		 */
		public function isSite();

		/**
		 * Определяет работает ли система режиме консоли
		 * @return bool
		 */
		public function isCli();
	}
