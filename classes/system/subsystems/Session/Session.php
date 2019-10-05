<?php

	namespace UmiCms\System\Session;

	use UmiCms\System\Cookies\iCookieJar;

	/**
	 * Класс сессии
	 * @package UmiCms\System\Session
	 */
	class Session implements iSession {

		/** @var \iConfiguration|null $config */
		private $config;

		/** @var iCookieJar|null $cookieJar */
		private $cookieJar;

		/** @inheritdoc */
		public function __construct(\iConfiguration $config, iCookieJar $cookieJar) {
			$this->config = $config;
			$this->cookieJar = $cookieJar;
			$this->initSettings();
		}

		/** @inheritdoc */
		public function get($key) {
			if (!$this->isValidKey($key)) {
				return null;
			}

			$this->start();
			$value = getArrayKey($_SESSION, $key);
			$this->commit();

			return $value;
		}

		/** @inheritdoc */
		public function isExist($key) {
			if (!$this->isValidKey($key)) {
				return false;
			}

			$this->start();
			$isExists = array_key_exists($key, $_SESSION);
			$this->commit();

			return $isExists;
		}

		/** @inheritdoc */
		public function set($key, $value) {
			if (!$this->isValidKey($key)) {
				return $this;
			}

			$this->start();
			$_SESSION[$key] = $value;
			$this->commit();

			return $this;
		}

		/** @inheritdoc */
		public function del($keyList) {
			$keyList = is_array($keyList) ? $keyList : [$keyList];
			$this->start();

			foreach ($keyList as $key) {
				if (!$this->isValidKey($key)) {
					continue;
				}
				unset($_SESSION[$key]);
			}

			$this->commit();
			return $this;
		}

		/** @inheritdoc */
		public function getArrayCopy() {
			$this->start();
			$session = $_SESSION;
			$this->commit();

			return $session;
		}

		/** @inheritdoc */
		public function clear() {
			$this->start();
			$_SESSION = [];
			$this->commit();
			return $this;
		}

		/** @inheritdoc */
		public function __get($key) {
			return $this->get($key);
		}

		/** @inheritdoc */
		public function __isset($key) {
			return $this->isExist($key);
		}

		/** @inheritdoc */
		public function __set($key, $value) {
			return $this->set($key, $value);
		}

		/** @inheritdoc */
		public function __unset($keyList) {
			return $this->del($keyList);
		}

		/** @inheritdoc */
		public function changeId($id = null) {
			$this->start();

			if ($id !== null && is_string($id) && !empty($id)) {
				session_id($id);
			} else {
				session_regenerate_id();
			}

			return $this->commit()
				->bufferCookieHeaders()
				->deleteCookieHeaders();
		}

		/** @inheritdoc */
		public function getId() {
			$this->start();
			$id = session_id();
			$this->commit();
			return $id;
		}

		/** @inheritdoc */
		public function getName() {
			$this->start();
			$name = session_name();
			$this->commit();
			return $name;
		}

		/** @inheritdoc */
		public function startActiveTime() {
			$this->set('start_time', time());
			return $this;
		}

		/** @inheritdoc */
		public function endActiveTime() {
			$expiredTime = time() - ($this->getMaxActiveTime() + 1) * self::SECONDS_IN_ONE_MINUTE;
			$this->set('start_time', $expiredTime);
			return $this;
		}

		/** @inheritdoc */
		public function isActiveTimeExpired() {
			$startActiveTime = (int) $this->get('start_time');

			if ($startActiveTime === 0) {
				return false;
			}

			$maxSessionLifeTime = $this->getMaxActiveTime() * self::SECONDS_IN_ONE_MINUTE;

			return $startActiveTime + $maxSessionLifeTime < time();
		}

		/** @inheritdoc */
		public function getActiveTime() {
			$startActiveTime = (int) $this->get('start_time');
			$maxSessionLifeTime = $this->getMaxActiveTime() * self::SECONDS_IN_ONE_MINUTE;

			if ($startActiveTime === 0) {
				return $maxSessionLifeTime;
			}

			return $startActiveTime + $maxSessionLifeTime - time();
		}

		/** @inheritdoc */
		public function getMaxActiveTime() {
			$activeLifeTime = $this->getConfig()
				->get('session', 'active-lifetime');
			return is_numeric($activeLifeTime) ? (int) $activeLifeTime : self::TWO_WEEKS_IN_SECONDS;
		}

		/**
		 * Запускает сессию
		 * @return Session
		 */
		private function start() {
			if (!$this->isStarted()) {
				@session_start();
				$this->bufferCookieHeaders()
					->deleteCookieHeaders();
			}

			return $this;
		}

		/**
		 * Фиксирует изменения сессии
		 * @return Session
		 */
		private function commit() {
			if ($this->isStarted()) {
				session_commit();
			}

			return $this;
		}

		/**
		 * Запущена ли сессия
		 * @return bool
		 */
		private function isStarted() {
			return session_status() === PHP_SESSION_ACTIVE;
		}

		/**
		 * Возвращает менеджер кук
		 * @return null|iCookieJar
		 */
		private function getCookieJar() {
			return $this->cookieJar;
		}

		/**
		 * Возвращает конфиг
		 * @return \iConfiguration|null
		 */
		private function getConfig() {
			return $this->config;
		}

		/**
		 * Инициализирует настройки сессии
		 * @return Session
		 */
		private function initSettings() {
			if (headers_sent()) {
				return $this;
			}

			$config = $this->getConfig();
			$lifeTime = $this->getCookieLifeTime();

			$path = $config->get('session', 'cookie-path');
			$path = (is_string($path) && !empty($path)) ? $path : self::DEFAULT_COOKIE_PATH;

			$domain = $config->get('session', 'cookie-domain');
			$domain = (is_string($domain) && !empty($domain)) ? $domain : self::DEFAULT_COOKIE_DOMAIN;

			$secureFlag = $config->get('session', 'cookie-secure-flag');
			$secureFlag = is_numeric($secureFlag) ? (bool) $secureFlag : self::DEFAULT_COOKIE_SECURE_FLAG;

			$httpOnlyFlag = $config->get('session', 'cookie-http-flag');
			$httpOnlyFlag = is_numeric($httpOnlyFlag) ? (bool) $httpOnlyFlag : self::DEFAULT_COOKIE_HTTP_ONLY_FLAG;

			session_set_cookie_params($lifeTime, $path, $domain, $secureFlag, $httpOnlyFlag);

			$name = $config->get('session', 'name');
			$name = (is_string($name) && !empty($name)) ? $name : self::DEFAULT_NAME;
			session_name($name);

			return $this;
		}

		/** @inheritdoc */
		public function getCookieLifeTime() {
			$lifeTime = $this->getConfig()->get('session', 'cookie-lifetime');
			return is_numeric($lifeTime) ? (int) $lifeTime : self::TWO_WEEKS_IN_SECONDS;
		}

		/**
		 * Буфферизует заголовки кук
		 * @return Session
		 */
		private function bufferCookieHeaders() {
			$cookieJar = $this->getCookieJar();

			foreach (headers_list() as $header) {
				$isCookieHeader = is_int(mb_strpos($header, 'Set-Cookie'));

				if (!$isCookieHeader) {
					continue;
				}

				try {
					$cookieJar->setFromHeader($header);
				} catch (\wrongParamException $e) {
					continue;
				}
			}

			return $this;
		}

		/**
		 * Удаляет все куки из заголовков
		 * @return Session
		 */
		private function deleteCookieHeaders() {
			@header_remove('Set-Cookie');
			return $this;
		}

		/**
		 * Определяет валиден ли ключ для значения сессии
		 * @param mixed $key
		 * @return bool
		 */
		private function isValidKey($key) {
			return (is_string($key) || is_int($key));
		}
	}
