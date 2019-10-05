<?php

	namespace UmiCms\Classes\System\Utils\Mail\Settings\Smtp;

	use UmiCms\Classes\System\Utils\Settings\iSettings as iMainSettings;

	/**
	 * Интерфейс настроек SMTP
	 * @package UmiCms\Classes\System\Utils\Mail\Settings\Smtp
	 */
	interface iSettings extends iMainSettings {

		/**
		 * Возвращает таймаут (в секундах)
		 * @return int
		 */
		public function getTimeout();

		/**
		 * Изменяет таймаут  (в секундах)
		 * @param int $timeout
		 * @return $this
		 */
		public function setTimeout($timeout);

		/**
		 * Возвращает хост
		 * @return string
		 */
		public function getHost();

		/**
		 * Изменяет хост
		 * @param string $host
		 * @return $this
		 */
		public function setHost($host);

		/**
		 * Возвращает порт
		 * @return int
		 */
		public function getPort();

		/**
		 * Изменяет порт
		 * @param int $port
		 * @return $this
		 */
		public function setPort($port);

		/**
		 * Возвращает способ шифрования
		 * @return string
		 */
		public function getEncryption();

		/**
		 * Изменяет способ шифрования
		 * @param string $encryption
		 * @return $this
		 */
		public function setEncryption($encryption);

		/**
		 * Включена ли авторизация
		 * @return bool
		 */
		public function isAuth();

		/**
		 * Изменяет использование авторизации
		 * @param bool $isAuth
		 * @return $this
		 */
		public function setAuth($isAuth);

		/**
		 * Возвращает имя пользователя 
		 * @return bool
		 */
		public function getUserName();

		/**
		 * Изменяет имя пользователя 
		 * @param string $username
		 * @return $this
		 */
		public function setUserName($username);

		/**
		 * Возвращает пароль 
		 * @return bool
		 */
		public function getPassword();

		/**
		 * Изменяет пароль 
		 * @param string $password
		 * @return $this
		 */
		public function setPassword($password);

		/**
		 * Включен ли режим отладки 
		 * @return bool
		 */
		public function isDebug();

		/**
		 * Включить/отключить режим отладки 
		 * @param bool $isDebug
		 * @return $this
		 */
		public function setDebug($isDebug);

		/**
		 * Использовать ли VERP
		 * @return bool
		 */
		public function isUseVerp();

		/**
		 * Включить/отключить VERP
		 * @param bool $isVerp
		 * @return mixed
		 */
		public function setUseVerp($isVerp);

	}