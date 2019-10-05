<?php

	namespace UmiCms\Classes\System\Utils\Mail\Settings\Smtp;

	use UmiCms\Mail\Engine\smtp;
	use UmiCms\Classes\System\Utils\Settings\Common as SettingsCommon;

	/**
	 * Класс для работы с настройками SMTP, общими для всех сайтов
	 * @package UmiCms\Classes\System\Utils\Mail\Settings\Smtp
	 */
	class Common extends SettingsCommon implements iSettings {

		/** @inheritdoc */
		public function getTimeout() {
			return (int) $this->getMailConfig(smtp::TIMEOUT);
		}

		/** @inheritdoc */
		public function setTimeout($timeout) {
			$this->setMailConfig(smtp::TIMEOUT, $timeout);
			return $this;
		}

		/** @inheritdoc */
		public function getHost() {
			return (string) $this->getMailConfig(smtp::HOST);
		}

		/** @inheritdoc */
		public function setHost($host) {
			$this->setMailConfig(smtp::HOST, $host);
			return $this;
		}

		/** @inheritdoc */
		public function getPort() {
			return (int) $this->getMailConfig(smtp::PORT);
		}

		/** @inheritdoc */
		public function setPort($port) {
			$this->setMailConfig(smtp::PORT, $port);
			return $this;
		}

		/** @inheritdoc */
		public function getEncryption() {
			return (string) $this->getMailConfig(smtp::ENCRYPTION);
		}

		/** @inheritdoc */
		public function setEncryption($encryption) {
			$this->setMailConfig(smtp::ENCRYPTION, $encryption);
			return $this;
		}

		/** @inheritdoc */
		public function isAuth() {
			return (bool) $this->getMailConfig(smtp::AUTH);
		}

		/** @inheritdoc */
		public function setAuth($isAuth) {
			$this->setMailConfig(smtp::AUTH, (int) $isAuth);
			return $this;
		}

		/** @inheritdoc */
		public function getUserName() {
			return (string) $this->getMailConfig(smtp::USER);
		}

		/** @inheritdoc */
		public function setUserName($username) {
			$this->setMailConfig(smtp::USER, $username);
			return $this;
		}

		/** @inheritdoc */
		public function getPassword() {
			return (string) $this->getMailConfig(smtp::PASSWORD);
		}

		/** @inheritdoc */
		public function setPassword($password) {
			$this->setMailConfig(smtp::PASSWORD, $password);
			return $this;
		}

		/** @inheritdoc */
		public function isDebug() {
			return (bool) $this->getMailConfig(smtp::DEBUG);
		}

		/** @inheritdoc */
		public function setDebug($isDebug) {
			$this->setMailConfig(smtp::DEBUG, (int) $isDebug);
			return $this;
		}

		/** @inheritdoc */
		public function isUseVerp() {
			return (bool) $this->getMailConfig(smtp::USE_VERP);
		}

		/** @inheritdoc */
		public function setUseVerp($isVerp) {
			$this->setMailConfig(smtp::USE_VERP, (int) $isVerp);
			return $this;
		}

		/** @inheritdoc */
		protected function getPrefix() {
			return "//settings/mail/";
		}

		/**
		 * Изменяет директиву настроек SMTP в config.ini
		 * @param string $name имя директивы
		 * @param mixed $value значение директивы
		 */
		private function setMailConfig($name, $value) {
			$config = $this->getConfig();
			$config->set('mail', $name, (string) $value);
			$config->save();
		}

		/**
		 * Возвращает директиву настроек SMTP в config.ini
		 * @param string $name имя директивы
		 * @return mixed
		 */
		private function getMailConfig($name) {
			return $this->getConfig()->get(smtp::SECTION, $name);
		}

		/**
		 * Возвращает объект конфигурации
		 * @return \iConfiguration
		 */
		private function getConfig() {
			return \mainConfiguration::getInstance();
		}
	}