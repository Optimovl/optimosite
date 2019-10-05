<?php

	namespace UmiCms\Classes\System\Utils\Mail\Settings;

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Settings\Common as SettingsCommon;

	/**
	 * Класс для работы с настройками отправки почты, общими для всех сайтов
	 * @package UmiCms\Classes\System\Utils\Mail\Settings
	 */
	class Common extends SettingsCommon implements iSettings {

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
			return (string) $this->getConfig()->get('mail', 'engine');
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setEngine($engine) {
			$this->setMailConfig('engine', $engine);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function isDisableParseContent() {
			return (bool) !$this->getConfig()->get('mail', 'default.parse.content');
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setDisableParseContent($isDisable) {
			$this->setMailConfig('default.parse.content', (int) !$isDisable);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function Smtp() {
			return Service::get('SmtpMailSettingsFactory')->createCommon();
		}

		/** @inheritdoc */
		protected function getPrefix() {
			return "//settings";
		}

		/**
		 * Возвращает объект конфигурации
		 * @return \iConfiguration
		 */
		private function getConfig() {
			return \mainConfiguration::getInstance();
		}

		/**
		 * Изменяет директиву настроек отправки почты в config.ini
		 * @param string $name имя директивы
		 * @param mixed $value значение директивы
		 */
		private function setMailConfig($name, $value) {
			$config = $this->getConfig();
			$config->set('mail', $name, (string) $value);
			$config->save();
		}
	}