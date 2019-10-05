<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Settings;

	/**
	 * Класс для работы с настройками капчи, специфическими для конкретного сайта
	 * @package UmiCms\Classes\System\Utils\Captcha\Settings
	 */
	class Site implements iSettings, \iUmiRegistryInjector {

		use \tUmiRegistryInjector;

		/** @var int|null ИД домена сайта, для которого берутся настройки */
		private $domainId;

		/** @var int|null ИД языка сайта, для которого берутся настройки */
		private $langId;

		/**
		 * Конструктор
		 * @param int $domainId ИД домена сайта, для которого берутся настройки
		 * @param int $langId ИД языка сайта, для которого берутся настройки
		 * @param \iRegedit $registry класс регистра
		 * @throws \ErrorException
		 */
		public function __construct($domainId, $langId, \iRegedit $registry) {
			if (!is_numeric($domainId) || !is_numeric($langId)) {
				throw new \ErrorException(getLabel('error-wrong-domain-and-lang-ids'));
			}
			$this->domainId = $domainId;
			$this->langId = $langId;
			$this->setRegistry($registry);
		}

		/**
		 * Возвращает настройку "Использовать настройки сайта"
		 * @return bool
		 */
		public function shouldUseSiteSettings() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/use-site-settings");
		}

		/**
		 * Устанавливает настройку "Использовать настройки сайта"
		 * @param bool $flag новое значение
		 * @return $this
		 */
		public function setShouldUseSiteSettings($flag) {
			$this->getRegistry()->set("{$this->getPrefix()}/use-site-settings", $flag);
			return $this;
		}

		/** @inheritdoc */
		public function getStrategyName() {
			if ($this->isRecaptchaEnabled()) {
				return 'recaptcha';
			}
			if ($this->isClassicEnabled()) {
				return 'captcha';
			}

			return 'null-captcha';
		}

		/** @inheritdoc */
		public function setStrategyName($name) {
			$this->setClassicEnabled(false)
				->setRecaptchaEnabled(false);

			if ($name === 'recaptcha') {
				$this->setRecaptchaEnabled(true);
			} elseif ($name === 'captcha') {
				$this->setClassicEnabled(true);
			}

			return $this;
		}

		/**
		 * Возвращает настройку "Использовать классическую капчу"
		 * @return bool
		 */
		protected function isClassicEnabled() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/enable-classic");
		}

		/**
		 * Устанавливает настройку "Использовать классическую капчу"
		 * @param bool $flag новое значение
		 * @return $this
		 */
		protected function setClassicEnabled($flag) {
			$this->getRegistry()->set("{$this->getPrefix()}/enable-classic", $flag);
			return $this;
		}

		/**
		 * Возвращает настройку "Использовать Google Recaptcha"
		 * @return bool
		 */
		protected function isRecaptchaEnabled() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/enable-recaptcha");
		}

		/**
		 * Устанавливает настройку "Использовать Google Recaptcha"
		 * @param bool $flag новое значение
		 * @return $this
		 */
		protected function setRecaptchaEnabled($flag) {
			$this->getRegistry()->set("{$this->getPrefix()}/enable-recaptcha", $flag);
			return $this;
		}

		/** @inheritdoc */
		public function shouldRemember() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/remember");
		}

		/** @inheritdoc */
		public function setShouldRemember($flag) {
			$this->getRegistry()->set("{$this->getPrefix()}/remember", $flag);
			return $this;
		}

		/** @inheritdoc */
		public function getDrawerName() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/drawer");
		}

		/** @inheritdoc */
		public function setDrawerName($name) {
			$this->getRegistry()->set("{$this->getPrefix()}/drawer", $name);
			return $this;
		}

		/** @inheritdoc */
		public function getSitekey() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/recaptcha-sitekey");
		}

		/** @inheritdoc */
		public function setSitekey($sitekey) {
			$this->getRegistry()->set("{$this->getPrefix()}/recaptcha-sitekey", $sitekey);
			return $this;
		}

		/** @inheritdoc */
		public function getSecret() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/recaptcha-secret");
		}

		/** @inheritdoc */
		public function setSecret($secret) {
			$this->getRegistry()->set("{$this->getPrefix()}/recaptcha-secret", $secret);
			return $this;
		}

		/**
		 * Возвращает общий для настроек префикс в реестре
		 * @return string
		 */
		private function getPrefix() {
			return "//settings/captcha/{$this->domainId}/{$this->langId}";
		}
	}
