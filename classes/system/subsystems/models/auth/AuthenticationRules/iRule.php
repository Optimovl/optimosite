<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	/**
	 * Интерфейс правила аутентификации пользователя
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	interface iRule {

		/**
		 * Проводит аутентификацию пользователя
		 * @return int|bool идентификатор пользователя в случае успешной аутентификации, иначе - false
		 */
		public function validate();
	}
