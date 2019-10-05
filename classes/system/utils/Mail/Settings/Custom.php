<?php

	namespace UmiCms\Classes\System\Utils\Mail\Settings;

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Settings\Custom as SettingsCustom;

	/**
	 * Класс для работы с настройками отправки почты, специфическими для конкретного сайта
	 * @package UmiCms\Classes\System\Utils\Mail\Settings
	 */
	class Custom extends SettingsCustom implements iSettings {

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getAdminEmail() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/admin_email");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setAdminEmail($email) {
			$this->getRegistry()->set("{$this->getPrefix()}/admin_email", $email);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getSenderEmail() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/email_from");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setSenderEmail($email) {
			$this->getRegistry()->set("{$this->getPrefix()}/email_from", $email);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getSenderName() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/fio_from");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setSenderName($name) {
			$this->getRegistry()->set("{$this->getPrefix()}/fio_from", $name);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getEngine() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/engine");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setEngine($engine) {
			$this->getRegistry()->set("{$this->getPrefix()}/engine", $engine);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function isDisableParseContent() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/disable-parse");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setDisableParseContent($isDisable) {
			$this->getRegistry()->set("{$this->getPrefix()}/disable-parse", $isDisable);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function Smtp() {
			return Service::get('SmtpMailSettingsFactory')->createCustom($this->domainId, $this->langId);
		}

		/** @inheritdoc */
		protected function getPrefix() {
			return "//settings/mail/{$this->domainId}/{$this->langId}";
		}
	}