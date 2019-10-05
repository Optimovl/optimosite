<?php

	use UmiCms\Service;

	/** @deprecated */
	class session implements iSession, iSessionValidation {

		/** @var session|null экземпляр класса */
		private static $instance;

		/** @const int SECONDS_IN_ONE_MINUTE количество секунд в одной минуте */
		const SECONDS_IN_ONE_MINUTE = 60;

		/** @const int SESSION_COOKIE_LIFE_TIME время жизни сессиионной куки в секундах */
		const SESSION_COOKIE_LIFE_TIME = 1209600;

		/** @inheritdoc */
		public static function getInstance($start = true) {
			if (!self::isStarted() && $start) {
				@session_start();
				self::bufferCookieHeaders();
				self::deleteCookieHeaders();
			}

			if (self::$instance === null) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/** @inheritdoc */
		public function regenerateId() {
			if (self::isStarted()) {
				$result = session_regenerate_id();
				self::bufferCookieHeaders();
				self::deleteCookieHeaders();
				return $result;
			}

			return null;
		}

		/** @inheritdoc */
		public function get($key) {
			return self::isStarted() ? getArrayKey($_SESSION, $key) : false;
		}

		/** @inheritdoc */
		public function isExist($key) {
			return self::isStarted() ? array_key_exists($key, $_SESSION) : false;
		}

		/** @inheritdoc */
		public function set($key, $value) {
			return self::isStarted() ? $_SESSION[$key] = $value : false;
		}

		/** @inheritdoc */
		public function del($key) {
			if (!self::isStarted()) {
				return false;
			}

			if (is_array($key)) {
				foreach ($key as $singleKey) {
					unset($_SESSION[$singleKey]);
				}
			} else {
				unset($_SESSION[$key]);
			}

			return true;
		}

		/** @inheritdoc */
		public function getAndClose($key) {
			$value = $this->get($key);
			$this->commit();
			return $value;
		}

		/** @inheritdoc */
		public function issetAndClose($key) {
			$value = $this->isExist($key);
			$this->commit();
			return $value;
		}

		/** @inheritdoc */
		public function setAndClose($key, $value) {
			$this->set($key, $value);
			$this->commit();
		}

		/** @inheritdoc */
		public function delAndClose($key) {
			$this->del($key);
			$this->commit();
		}

		/** @inheritdoc */
		public function getArrayCopy() {
			return self::isStarted() ? $_SESSION : [];
		}

		/** @inheritdoc */
		public function clear() {
			if (self::isStarted()) {
				$_SESSION = [];
				session_unset();
			}
		}

		/** @inheritdoc */
		public static function commit() {
			if (self::isStarted()) {
				session_commit();
			}
		}

		/** @inheritdoc */
		public static function destroy() {
			if (self::isStarted()) {
				session_destroy();
			}
		}

		/** @inheritdoc */
		public static function setId($id) {
			if (self::isStarted()) {
				return session_id($id);
			}

			return null;
		}

		/** @inheritdoc */
		public static function getId() {
			if (self::isStarted()) {
				return session_id();
			}

			return null;
		}

		/** @inheritdoc */
		public function getIdAndClose() {
			$id = $this->getId();
			$this->commit();
			return $id;
		}

		/** @inheritdoc */
		public static function setName($name) {
			if (self::isStarted()) {
				return session_name($name);
			}

			return null;
		}

		/** @inheritdoc */
		public static function getName() {
			if (self::isStarted()) {
				return session_name();
			}

			return null;
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
		public function __unset($key) {
			$this->del($key);
		}

		/** @inheritdoc */
		public function startActiveTime() {
			$this->set(self::START_TIME_KEY, time());
			return $this;
		}

		/** @inheritdoc */
		public function endActiveTime() {
			$expiredTime = time() - ($this->getMaxActiveTime() + 1) * self::SECONDS_IN_ONE_MINUTE;
			$this->set(self::START_TIME_KEY, $expiredTime);
			return $this;
		}

		/** @inheritdoc */
		public function isActiveTimeExpired() {
			$startActiveTime = (int) $this->get(self::START_TIME_KEY);

			if ($startActiveTime === 0) {
				return false;
			}

			$maxSessionLifeTime = $this->getMaxActiveTime() * self::SECONDS_IN_ONE_MINUTE;

			if ($startActiveTime + $maxSessionLifeTime < time()) {
				return true;
			}

			return false;
		}

		/** @inheritdoc */
		public function getActiveTime() {
			$startActiveTime = (int) $this->get(self::START_TIME_KEY);
			$maxSessionLifeTime = $this->getMaxActiveTime() * self::SECONDS_IN_ONE_MINUTE;

			if ($startActiveTime === 0) {
				return $maxSessionLifeTime;
			}

			return $startActiveTime + $maxSessionLifeTime - time();
		}

		/** @inheritdoc */
		public function getMaxActiveTime() {
			return defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 0;
		}

		/**
		 * Проверяет запущена ли сессия
		 * @return bool результат проверки
		 */
		private static function isStarted() {
			return session_status() === PHP_SESSION_ACTIVE;
		}

		/** Конструктор */
		private function __construct() {
			$this->setCookieParams();
		}

		/** Обработчик копирования объекта */
		private function __clone() {
		}

		/** Устанавливает параметры сессионных кук */
		private function setCookieParams() {
			$twoWeeks = self::SESSION_COOKIE_LIFE_TIME;
			$secure = false;
			$httpOnly = true;
			$cookieDomain = null;
			$path = '/';
			session_set_cookie_params($twoWeeks, $path, $cookieDomain, $secure, $httpOnly);
		}

		/** Буфферизует заголовки кук */
		private static function bufferCookieHeaders() {
			$cookieJar = Service::CookieJar();

			foreach (headers_list() as $header) {
				$isCookieHeader = is_int(strpos($header, 'Set-Cookie'));

				if (!$isCookieHeader) {
					continue;
				}

				try {
					$cookieJar->setFromHeader($header);
				} catch (wrongParamException $e) {
					continue;
				}
			}
		}

		/** Удаляет все куки из заголовков */
		private static function deleteCookieHeaders() {
			@header_remove('Set-Cookie');
		}

		/** @deprecated */
		public static function recreateInstance($resetData = false) {
			if ((bool) $resetData) {
				self::destroy();
			} else {
				self::commit();
			}

			return self::getInstance();
		}

		/** @deprecated */
		public function setValid($isValid = true) {
			$sessionLifeTime = $this->getMaxActiveTime();

			if ($isValid) {
				$this->set(self::START_TIME_KEY, time());
			} else {
				$this->set(self::START_TIME_KEY, time() - ($sessionLifeTime + 1) * self::SECONDS_IN_ONE_MINUTE);
			}
		}

		/** @deprecated */
		public function isValid() {
			$sessionLifeTime = $this->getMaxActiveTime();

			if (!$this->get(self::START_TIME_KEY)) {
				return $sessionLifeTime * self::SECONDS_IN_ONE_MINUTE;
			} elseif ($this->get(self::START_TIME_KEY) + $sessionLifeTime * self::SECONDS_IN_ONE_MINUTE < time()) {
				return false;
			} else {
				return $this->get(self::START_TIME_KEY) + $sessionLifeTime * self::SECONDS_IN_ONE_MINUTE - time();
			}
		}
	}
