<?php

	namespace UmiCms\System\Cookies;

	/**
	 * Интерфейс фабрики кук
	 * @package UmiCms\System\Cookies
	 */
	interface iFactory {

		/**
		 * Создает экземпляр куки
		 * @param string $name имя куки
		 * @param string $value значение куки
		 * @param int $expirationTime время, когда срок действия куки истекает
		 * @return iCookie
		 */
		public function create($name, $value = '', $expirationTime = 0);

		/**
		 * Создает экземпляр куки из заголовка
		 * @param string $header заголовок
		 * @example:
		 *
		 * Set-Cookie: foo=bar; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=-1491548883; path=/; domain=test.com;
		 *     secure; httponly
		 *
		 * @return iCookie
		 */
		public function createFromHeader($header);

		/**
		 * Устанавливает значение uri по умолчанию, в рамках которого будут действовать создаваемые куки
		 * @param string $path uri
		 * @return iFactory
		 */
		public function setPath($path);

		/**
		 * Устанавливает значение домена (поддомена) по умолчанию, в рамках которого будут действовать создаваемые куки
		 * @param string $domain домен (поддомен)
		 * @return iFactory
		 */
		public function setDomain($domain);

		/**
		 * Устанавливает значение флага по умолчанию, что куку можно использовать только по https для создаваемых кук
		 * @param bool $secureFlag
		 * @return iFactory
		 */
		public function setSecureFlag($secureFlag);

		/**
		 * Устанавливает значение флага по умолчанию, что кука будет доступна только через протокол HTTP,
		 * то есть к ней не будет доступа из javascript
		 * @param bool $httpOnlyFlag
		 * @return iFactory
		 */
		public function setHttpOnlyFlag($httpOnlyFlag);
	}
