<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Strategies;

	/** Стратегия Капчи Null object. */
	class NullCaptcha extends CaptchaStrategy {

		/** @inheritdoc */
		public function generate($template, $inputId, $captchaHash, $captchaId) {
			return '';
		}

		/** @inheritdoc */
		public function isValid() {
			return true;
		}

		/** @inheritdoc */
		public function isRequired() {
			return false;
		}

		/** @inheritdoc */
		public function getName() {
			return 'null-captcha';
		}
	}
