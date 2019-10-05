<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Strategies;

	use UmiCms\Service;

	/**
	 * Стратегия работы с капчей Google reCaptcha
	 * @link https://www.google.com/recaptcha
	 */
	class GoogleRecaptcha extends CaptchaStrategy {

		/** @inheritdoc */
		public function getName() {
			return 'recaptcha';
		}

		/** @const ссылка на скрипт виджета */
		const WIDGET_URL = 'https://www.google.com/recaptcha/api.js';

		/** @const ссылка на api проверки */
		const VERIFICATION_URL = 'https://www.google.com/recaptcha/api/siteverify';

		/** @inheritdoc */
		public function generate($template, $inputId, $captchaHash, $captchaId) {
			if (!$this->isRequired()) {
				return '';
			}

			list($recaptchaTemplate) = \def_module::loadTemplates('captcha/' . $template, 'recaptcha');
			$variables = [
				'recaptcha-url' => self::WIDGET_URL,
				'recaptcha-class' => 'g-recaptcha',
				'recaptcha-sitekey' => $this->getSitekey(),
			];

			return \def_module::parseTemplate($recaptchaTemplate, $variables);
		}

		/**
		 * Возвращает параметр "recaptcha sitekey" на текущем сайте
		 * @return string
		 */
		public function getSitekey() {
			return Service::CaptchaSettingsFactory()
				->getCurrentSettings()
				->getSitekey();
		}

		/**
		 * Возвращает параметр "recaptcha secret" на текущем сайте
		 * @return string
		 */
		public function getSecret() {
			return Service::CaptchaSettingsFactory()
				->getCurrentSettings()
				->getSecret();
		}

		/**
		 * @inheritdoc
		 * @link https://developers.google.com/recaptcha/docs/verify
		 */
		public function isValid() {
			if (!$this->isRequired()) {
				return true;
			}

			$response = getRequest('g-recaptcha-response');
			$secret = $this->getSecret();

			if (!$response || !$secret) {
				return false;
			}

			$localName = false;
			$headers = [
				'Content-Type' => 'application/x-www-form-urlencoded',
			];
			$params = [
				'secret' => $secret,
				'response' => $response,
				'remoteip' => getServer('REMOTE_ADDR'),
			];

			$result = \umiRemoteFileGetter::get(self::VERIFICATION_URL, $localName, $headers, $params);
			$result = json_decode($result, true);

			return is_array($result) && array_key_exists('success', $result) && (bool) $result['success'];
		}
	}
