<?php

	namespace UmiCms\System\Cookies;

	/**
	 * Интерфейс куки
	 * @package UmiCms\System\Cookies
	 */
	interface iCookie {

		/**
		 * Конструктор
		 * @param string $name название
		 * @param mixed $value значение
		 * @param int $expirationTime время, когда срок действия истекает
		 */
		public function __construct($name, $value = '', $expirationTime = 0);

		/**
		 * Возвращает название
		 * @return string
		 */
		public function getName();

		/**
		 * Возвращает значение
		 * @return mixed
		 */
		public function getValue();

		/**
		 * Устанавливает значение
		 * @param mixed $value значение
		 * @return iCookie
		 */
		public function setValue($value);

		/**
		 * время, когда срок действия истекает
		 * @return int
		 */
		public function getExpirationTime();

		/**
		 * Устанавливает время, когда срок действия истекает
		 * @param int $time время
		 * @return iCookie
		 */
		public function setExpirationTime($time);

		/**
		 * Возвращает uri, в рамках которого будет действовать кука
		 * @return string
		 */
		public function getPath();

		/**
		 * Устанавливает uri, в рамках которого будет действовать кука
		 * @param string $path uri
		 * @return iCookie
		 */
		public function setPath($path);

		/**
		 * Возвращает домен (поддомен), в рамках которого будет действовать кука
		 * @return string
		 */
		public function getDomain();

		/**
		 * Устанавливает домен (поддомен), в рамках которого будет действовать кука
		 * @param string $domain
		 * @return iCookie
		 */
		public function setDomain($domain);

		/**
		 * Определяет, что куку можно использовать только по https
		 * @return bool
		 */
		public function isSecure();

		/**
		 * Устанавливает флаг, что куку можно использовать только по https
		 * @param bool $flag
		 * @return iCookie
		 */
		public function setSecureFlag($flag);

		/**
		 * Определяет, что кука будет доступна только через протокол HTTP, то есть к ней не будет
		 * доступа из javascript
		 * @return bool
		 */
		public function isForHttpOnly();

		/**
		 * Устанавливает флаг, что кука будет доступна только через протокол HTTP, то есть к ней не будет
		 * доступа из javascript
		 * @param bool $flag
		 * @return iCookie
		 */
		public function setHttpOnlyFlag($flag);
	}
