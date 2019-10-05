<?php

	namespace UmiCms\System\Request;

	use UmiCms\System\Request\Http\iCookies;
	use UmiCms\System\Request\Http\iFiles;
	use UmiCms\System\Request\Http\iGet;
	use UmiCms\System\Request\Http\iPost;
	use UmiCms\System\Request\Http\iRequest;
	use UmiCms\System\Request\Http\iServer;
	use UmiCms\System\Request\Mode\iDetector as ModeDetector;
	use UmiCms\System\Request\Path\iResolver as PathResolver;
	use UmiCms\Utils\Browser\iDetector as BrowserDetector;

	/**
	 * Интерфейс фасада запроса
	 * @package UmiCms\System\Request
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iRequest $request класс http запроса
		 * @param BrowserDetector $browserDetector определитель параметров браузера
		 * @param ModeDetector $modeDetector определитель режима работы системы
		 * @param PathResolver $pathResolver распознаватель обрабатываемого пути
		 */
		public function __construct(
			iRequest $request,
			BrowserDetector $browserDetector,
			ModeDetector $modeDetector,
			PathResolver $pathResolver
		);

		/**
		 * Возвращает контейнер кук запроса
		 * @return iCookies
		 */
		public function Cookies();

		/**
		 * Возвращает контейнер серверных переменных
		 * @return iServer
		 */
		public function Server();

		/**
		 * Возвращает контейнер POST параметров
		 * @return iPost
		 */
		public function Post();

		/**
		 * Возвращает контейнер GET параметров
		 * @return iGet
		 */
		public function Get();

		/**
		 * Возвращает контейнер загруженных файлов
		 * @return iFiles
		 */
		public function Files();

		/**
		 * Возвращает метод
		 * @return string
		 */
		public function method();

		/**
		 * Определяет, что запрос произведен методом "POST"
		 * @return bool
		 */
		public function isPost();

		/**
		 * Определяет, что запрос произведен методом "GET"
		 * @return bool
		 */
		public function isGet();

		/**
		 * Определяет работает ли система в режиме панели администрирования
		 * @return bool
		 */
		public function isAdmin();

		/**
		 * Определяет, что система не работает в режиме панели администрирования
		 * @return bool
		 */
		public function isNotAdmin();

		/**
		 * Определяет работает ли система в режиме сайта
		 * @return bool
		 */
		public function isSite();

		/**
		 * Определяет работает ли система в режиме консоли
		 * @return bool
		 */
		public function isCli();

		/**
		 * Определяет режим работы системы
		 * @return string
		 */
		public function mode();

		/**
		 * Возвращает хост
		 * @return string
		 */
		public function host();

		/**
		 * Возвращает uri запроса, @see .htaccess
		 * @return string
		 */
		public function getPath();

		/**
		 * Возвращает части пути
		 * @return string[]
		 */
		public function getPathParts();

		/**
		 * Определяет запрошен ли поток
		 * @return bool
		 */
		public function isStream();

		/**
		 * Возвращает схему потока
		 * @return string|null
		 */
		public function getStreamScheme();

		/**
		 * Определяет запрошен ли json
		 * @return bool
		 */
		public function isJson();

		/**
		 * Определяет запрошен ли xml
		 * @return bool
		 */
		public function isXml();

		/**
		 * Определяет запрошен ли html, то есть страница сайта
		 * @return bool
		 */
		public function isHtml();

		/**
		 * Определяет запрошена ли мобильная версия
		 * @return bool
		 */
		public function isMobile();

		/**
		 * Определяет обрабатывается ли запрос локальным сервером
		 * @return bool
		 */
		public function isLocal();

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
		 * Определяет сделан ли запрос ботом
		 * @return bool
		 */
		public function isRobot();

		/**
		 * Определяет запрошен ли стек вызовов протоколов
		 * @return bool
		 */
		public function isStreamCallStack();

		/**
		 * Возвращает user agent
		 * @return string
		 */
		public function userAgent();

		/**
		 * Возвращает ip адрес отправителя запроса
		 * @return string
		 */
		public function remoteAddress();

		/**
		 * Возвращает ip адрес сервера
		 * @return string
		 */
		public function serverAddress();

		/**
		 * Возвращает uri
		 * @return string
		 */
		public function uri();

		/**
		 * Возвращает query
		 * @return string
		 */
		public function query();

		/**
		 * Возвращает корневую папку
		 * @return string
		 */
		public function documentRoot();

		/**
		 * Возвращает хеш от query
		 * @return string
		 */
		public function queryHash();

		/**
		 * Возвращает необработанные данные тела запроса
		 * @return string
		 */
		public function getRawBody();

		/**
		 * Определяет отправлен ли запрос через ajax
		 * @return bool
		 */
		public function isAjax();
	}
