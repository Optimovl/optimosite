<?php

	namespace UmiCms\Utils\Browser;

	use UmiCms\System\Cache\iEngineFactory;

	/**
	 * Интерфейс определителся параметров браузера
	 * @package UmiCms\Utils\Browser
	 */
	interface iDetector {

		/**
		 * Конструктор
		 * @param iEngineFactory $cacheFactory фабрика хранилищ кеша
		 */
		public function __construct(iEngineFactory $cacheFactory);

		/**
		 * Возвращает название браузера
		 * @return string
		 */
		public function getBrowser();

		/**
		 * Возвращает название операционной системы
		 * @return string
		 */
		public function getPlatform();

		/**
		 * Возвращает версию браузера
		 * @return string
		 */
		public function getVersion();

		/**
		 * Определяет сделан ли запрос с мобильного устройства
		 * @return bool
		 */
		public function isMobile();

		/**
		 * Определяет сделан ли запрос с планшета
		 * @return bool
		 */
		public function isTablet();

		/**
		 * Определяет сделан ли запрос ботом
		 * @return bool
		 */
		public function isRobot();

		/**
		 * Возвращает user agent
		 * @return string
		 */
		public function getUserAgent();

		/**
		 * Устанавливает user agent
		 * @param string $userAgent
		 */
		public function setUserAgent($userAgent);
	}
