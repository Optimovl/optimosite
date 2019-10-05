<?php

	namespace UmiCms\System\Interfaces;

	/** Интерфейс работника с авторизационным токеном Яндекса */
	interface iYandexTokenInjector {

		/**
		 * Возвращает авторизационный токен для сервисов Яндекса
		 * @return string
		 */
		public function getYandexToken();

		/**
		 * Устанавливает авторизационный токен для сервисов Яндекса
		 * @param string $token авторизационный токен
		 * @return $this
		 */
		public function setYandexToken($token);
	}
