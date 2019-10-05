<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Strategies;

	use UmiCms\Service;

	/**
	 * Классическая стратегия работы с капчей.
	 * Капча рисуется объектами класса \captchaDrawer.
	 */
	class ClassicCaptcha extends CaptchaStrategy {

		/** @const ID_PARAM имя параметра для идентификатора CAPTCHA */
		const ID_PARAM = 'captcha_id';

		/** @inheritdoc */
		public function getName() {
			return 'captcha';
		}

		/** @inheritdoc */
		public function generate($template, $inputId, $captchaHash, $captchaId) {
			if (!$this->isRequired()) {
				return \def_module::isXSLTResultMode() ? [
					'comment:explain' => 'Captcha is not required for logged users'
				] : '';
			}

			if (!$template) {
				$template = 'default';
			}

			if (!$inputId) {
				$inputId = 'sys_captcha';
			}

			if (!$captchaHash) {
				$captchaHash = '';
			}

			$randomString = '?' . time();
			$langId = Service::LanguageDetector()->detectId();

			$block_arr = [];
			$block_arr['void:input_id'] = $inputId;
			$block_arr['attribute:captcha_hash'] = $captchaHash;
			$block_arr['attribute:random_string'] = $randomString;
			$block_arr['attribute:lang_id'] = $langId;
			$block_arr['url'] = [
				'attribute:random-string' => $randomString,
				'attribute:lang_id' => $langId,
				'node:value' => '/captcha.php',
			];

			if ($captchaId) {
				$block_arr['url']['attribute:id_param'] = self::ID_PARAM;
				$block_arr['url']['attribute:id'] = $captchaId;
			}

			list($template_captcha) = \def_module::loadTemplates('captcha/' . $template, 'captcha');
			return \def_module::parseTemplate($template_captcha, $block_arr);
		}

		/** @inheritdoc */
		public function isRequired() {
			if (!parent::isRequired()) {
				return false;
			}

			if (!$this->shouldRemember()) {
				return true;
			}

			return (Service::Session()->get('is_human') != 1);
		}

		/**
		 * Нужно ли запоминать результат заполнения капчи между запросами на текущем сайте
		 * @return bool
		 */
		public function shouldRemember() {
			return Service::CaptchaSettingsFactory()
				->getCurrentSettings()
				->shouldRemember();
		}

		/** @inheritdoc */
		public function isValid() {
			if (!$this->isRequired()) {
				return true;
			}

			$session = Service::Session();

			if (!$session->isExist('umi_captcha')) {
				return false;
			}

			$captcha = $session->get('umi_captcha');
			$captchaCode = $captcha;
			$userCaptchaCode = (string) getRequest('captcha');

			if (is_array($captcha)) {
				$captchaId = (string) getRequest(self::ID_PARAM);

				if (!isset($captcha[$captchaId])) {
					return false;
				}

				$captchaCode = $captcha[$captchaId];
			}

			return $this->compareCodes($captchaCode, $userCaptchaCode);
		}

		/** @inheritdoc */
		public function getDrawer() {
			$name = Service::CaptchaSettingsFactory()
				->getCurrentSettings()
				->getDrawerName() ?: 'default';
			return $this->findDrawer($name);
		}

		/**
		 * Сравнивает коды CAPTCHA и возвращает результат сравнения
		 * @param string $captchaCode сохранный код CAPTCHA
		 * @param mixed $userCode код, отправленный пользователем
		 * @return bool
		 */
		private function compareCodes($captchaCode, $userCode) {
			$codeHashFromUser = md5($userCode);
			$session = Service::Session();

			if (Service::Protection()->hashEquals($captchaCode, $codeHashFromUser)) {
				$session->set('is_human', 1);
				return true;
			}

			$session->del('is_human');
			return false;
		}

	}
