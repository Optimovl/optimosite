<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Settings;

	/**
	 * Класс для работы с настройками капчи, общими для всех сайтов
	 * @package UmiCms\Classes\System\Utils\Captcha\Settings
	 */
	class Common implements iSettings, \iUmiRegistryInjector, \iUmiConfigInjector {

		use \tUmiRegistryInjector;
		use \tUmiConfigInjector;

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration класс конфигурации
		 * @param \iRegedit $registry класс регистра
		 */
		public function __construct(\iConfiguration $configuration, \iRegedit $registry) {
			$this->setConfiguration($configuration);
			$this->setRegistry($registry);
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
			return (bool) $this->getConfiguration()->get('anti-spam', 'captcha.enabled');
		}

		/**
		 * Устанавливает настройку "Использовать классическую капчу"
		 * @param bool $flag статус
		 * @return $this
		 */
		protected function setClassicEnabled($flag) {
			$configuration = $this->getConfiguration();
			$configuration->set('anti-spam', 'captcha.enabled', $flag);
			$configuration->save();
			return $this;
		}

		/**
		 * Возвращает настройку "Использовать Google Recaptcha"
		 * @return bool
		 */
		protected function isRecaptchaEnabled() {
			return (bool) $this->getRegistry()->get('//settings/enable-recaptcha');
		}

		/**
		 * Устанавливает настройку "Использовать Google Recaptcha"
		 * @param bool $flag статус
		 * @return $this
		 */
		protected function setRecaptchaEnabled($flag) {
			$this->getRegistry()->set('//settings/enable-recaptcha', $flag);
			return $this;
		}

		/** @inheritdoc */
		public function shouldRemember() {
			return (bool) $this->getRegistry()->get('//settings/captcha-remember');
		}

		/** @inheritdoc */
		public function setShouldRemember($flag) {
			$this->getRegistry()->set('//settings/captcha-remember', $flag);
			return $this;
		}

		/** @inheritdoc */
		public function getDrawerName() {
			return (string) $this->getConfiguration()->get('anti-spam', 'captcha.drawer');
		}

		/** @inheritdoc */
		public function setDrawerName($name) {
			$configuration = $this->getConfiguration();
			$configuration->set('anti-spam', 'captcha.drawer', $name);
			$configuration->save();
			return $this;
		}

		/** @inheritdoc */
		public function getSitekey() {
			return (string) $this->getRegistry()->get('//settings/recaptcha-sitekey');
		}

		/** @inheritdoc */
		public function setSitekey($sitekey) {
			$this->getRegistry()->set('//settings/recaptcha-sitekey', $sitekey);
			return $this;
		}

		/** @inheritdoc */
		public function getSecret() {
			return (string) $this->getRegistry()->get('//settings/recaptcha-secret');
		}

		/** @inheritdoc */
		public function setSecret($secret) {
			$this->getRegistry()->set('//settings/recaptcha-secret', $secret);
			return $this;
		}
	}
