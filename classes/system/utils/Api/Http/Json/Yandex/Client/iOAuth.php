<?php

	namespace UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Client;

	/**
	 * Интерфейс клиента API Яндекс.OAuth
	 * @link https://tech.yandex.ru/oauth/
	 * @package UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Client;
	 */
	interface iOAuth {

		/** Конструктор */
		public function __construct();

		/**
		 * Устанавливает идентификационные параметры приложения
		 * @param string $login логин (идентификатор)
		 * @param string $password пароль
		 * @return $this
		 */
		public function setAuth($login, $password);

		/**
		 * Возвращает авторизационный токен по коду, введенному пользователем
		 * @link https://tech.yandex.ru/oauth/doc/dg/reference/console-client-docpage/
		 * @param int $code числовой код
		 * @return string
		 */
		public function getTokenByUserCode($code);
	}
