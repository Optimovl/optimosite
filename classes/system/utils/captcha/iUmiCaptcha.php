<?php

	/** Интерфейс класса, контролирующего работу с капчей */
	interface iUmiCaptcha {

		/**
		 * Генерирует код вызова капчи
		 * @param string $template шаблон для генерации кода капчи
		 * @param string $inputId идентификатор инпута для капчи
		 * @param string $captchaHash md5-хеш кода, который будет выведен на картинке
		 * для предварительно проверки на клиенте
		 * @param string $captchaId идентификатор CAPTCHA
		 * @return array|string результат обработки в зависимости от текущего шаблонизатора
		 */
		public static function generateCaptcha(
			$template = 'default',
			$inputId = 'sys_captcha',
			$captchaHash = '',
			$captchaId = ''
		);

		/**
		 * Проверяет валидность выбранной на сайте CAPTCHA
		 * @return bool
		 */
		public static function checkCaptcha();

		/**
		 * Получает объект отрисовки капчи
		 * @return captchaDrawer объект отрисовки капчи
		 * @throws coreException
		 */
		public static function getDrawer();
	}
