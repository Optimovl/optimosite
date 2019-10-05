<?php

	namespace UmiCms\System\Session;

	use UmiCms\System\Cookies\iCookieJar;

	/**
	 * Интерфейс сессии
	 * @package UmiCms\System\Session
	 */
	interface iSession extends \iMapContainer {

		/** @const int SECONDS_IN_ONE_MINUTE количество секунд в одной минуте */
		const SECONDS_IN_ONE_MINUTE = 60;

		/** @const int SESSION_COOKIE_LIFE_TIME время жизни сессиионной куки в секундах */
		const TWO_WEEKS_IN_SECONDS = 1209600;

		/** @const string DEFAULT_COOKIE_PATH uri, в рамках которого будет действовать кука, по умолчанию */
		const DEFAULT_COOKIE_PATH = '/';

		/** @const null DEFAULT_COOKIE_DOMAIN домен, в рамках которого будет действовать кука, по умолчанию */
		const DEFAULT_COOKIE_DOMAIN = null;

		/** @const bool DEFAULT_COOKIE_SECURE_FLAG флаг, что куку можно использовать только по https, по умолчанию */
		const DEFAULT_COOKIE_SECURE_FLAG = false;

		/** @const bool DEFAULT_COOKIE_HTTP_ONLY_FLAG флаг, что куку можно использовать только по HTTP, по умолчанию */
		const DEFAULT_COOKIE_HTTP_ONLY_FLAG = true;

		/** @const int DEFAULT_ACTIVE_TIME_IN_MINUTES время активности сессии в минутах, по умолчанию */
		const DEFAULT_ACTIVE_TIME_IN_MINUTES = 60;

		/** @const string DEFAULT_NAME имя сессии по умолчанию */
		const DEFAULT_NAME = 'PHPSESSID';

		/**
		 * Конструктор
		 * @param \iConfiguration $config конфиг
		 * @param iCookieJar $cookieJar класс для работы с куками
		 */
		public function __construct(\iConfiguration $config, iCookieJar $cookieJar);

		/**
		 * Меняет идентификатор сессии
		 * @param string|null $id новый идентификатор сессии, если передан, но id сформирует автоматически
		 * @return iSession
		 */
		public function changeId($id = null);

		/**
		 * Возвращает идентификатор сессии
		 * @return string
		 */
		public function getId();

		/**
		 * Возвращает имя сессии (оно же является именем сессионной куки)
		 * @return string
		 */
		public function getName();

		/**
		 * Фиксирует начало активности сессии
		 * @return iSession
		 */
		public function startActiveTime();

		/**
		 * Фиксирует конец активности сессии
		 * @return iSession
		 */
		public function endActiveTime();

		/**
		 * Проверяет закончилось ли время активности сессии
		 * @return bool
		 */
		public function isActiveTimeExpired();

		/**
		 * Возвращает время, которое сесиия будет активна, в минутах
		 * @return int
		 */
		public function getActiveTime();

		/**
		 * Возвращает максимальное время активности сессии в минутах
		 * @return int
		 */
		public function getMaxActiveTime();

		/**
		 * Возвращает время жизни сессионной куки в секундах
		 * @return int
		 */
		public function getCookieLifeTime();
	}
