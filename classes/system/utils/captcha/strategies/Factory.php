<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Strategies;

	/**
	 * Фабрика стратегий работы с капчей
	 * @package UmiCms\Classes\System\Utils\Captcha\Strategies
	 */
	class Factory {

		/**
		 * Возвращает стратегию работы с капчей по ее названию
		 * @param string $name название стратегии
		 * @return ClassicCaptcha|GoogleRecaptcha|NullCaptcha
		 */
		public static function get($name) {
			switch ($name) {
				case 'recaptcha':
					return new GoogleRecaptcha();
				case 'captcha':
					return new ClassicCaptcha();
			}

			return new NullCaptcha();
		}
	}
