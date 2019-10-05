<?php

	use UmiCms\Classes\System\Utils\Captcha\Strategies;
	use UmiCms\Service;

	/** Фасад для работы с капчей на текущем сайте */
	class umiCaptcha implements iUmiCaptcha {

		/** @var Strategies\CaptchaStrategy стратегия работы с капчей на текущем сайте */
		private static $strategy;

		/** @inheritdoc */
		public static function generateCaptcha(
			$template = 'default',
			$inputId = 'sys_captcha',
			$captchaHash = '',
			$captchaId = ''
		) {
			return self::getStrategy()->generate($template, $inputId, $captchaHash, $captchaId);
		}

		/** @inheritdoc */
		public static function checkCaptcha() {
			return self::getStrategy()->isValid();
		}

		/** @inheritdoc */
		public static function getDrawer() {
			return self::getStrategy()->getDrawer();
		}

		/**
		 * Возвращает стратегию работы с капчей на текущем сайте
		 * @return Strategies\CaptchaStrategy
		 */
		public static function getStrategy() {
			if (!self::$strategy) {
				self::$strategy = self::determineStrategy();
			}
			return self::$strategy;
		}

		/**
		 * Определяет стратегию работы с капчей на текущем сайте
		 * @return Strategies\CaptchaStrategy
		 */
		protected static function determineStrategy() {
			$name = Service::CaptchaSettingsFactory()
				->getCurrentSettings()
				->getStrategyName();
			return Strategies\Factory::get($name);
		}

		/**
		 * Используется ли на текущем сайте Google Recaptcha
		 * @return bool
		 */
		public static function isRecaptcha() {
			return self::getStrategy() instanceof Strategies\GoogleRecaptcha;
		}

		/**
		 * Используется ли на текущем сайте классическая капча
		 * @return bool
		 */
		public static function isClassic() {
			return self::getStrategy() instanceof Strategies\ClassicCaptcha;
		}

		/**
		 * @deprecated
		 * @return string
		 */
		public static function getName() {
			return self::getStrategy()->getName();
		}

		/**
		 * @deprecated
		 * @return bool
		 */
		public static function isNeedCaptha() {
			return self::getStrategy()->isRequired();
		}
	}
