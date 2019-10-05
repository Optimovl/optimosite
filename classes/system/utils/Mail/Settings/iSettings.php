<?php

	namespace UmiCms\Classes\System\Utils\Mail\Settings;

	use UmiCms\Classes\System\Utils\Settings\iSettings as iMainSettings;
	use UmiCms\Classes\System\Utils\Mail\Settings\Smtp\iSettings as iSmtpSettings;

	/**
	 * Интерфейс настроек отправки почты
	 * @package UmiCms\Classes\System\Utils\Mail\Settings
	 */
	interface iSettings extends iMainSettings {

		/**
		 * Возвращает email администратора
		 * @return string
		 */
		public function getAdminEmail();

		/**
		 * Изменяет email администратора
		 * @param string $email
		 * @return $this
		 */
		public function setAdminEmail($email);

		/**
		 * Возвращает email отправителя
		 * @return string
		 */
		public function getSenderEmail();

		/**
		 * Изменяет email отправителя
		 * @param string $email
		 * @return $this
		 */
		public function setSenderEmail($email);

		/**
		 * Возвращает имя отправителя
		 * @return string
		 */
		public function getSenderName();

		/**
		 * Изменяет имя отправителя
		 * @param string $name
		 * @return $this
		 */
		public function setSenderName($name);

		/**
		 * Возвращает средство отправки писем
		 * @return string
		 */
		public function getEngine();

		/**
		 * Изменяет средство отправки писем
		 * @param string $engine
		 * @return $this
		 */
		public function setEngine($engine);

		/**
		 * Выключен ли парсинг тела письма
		 * @return bool
		 */
		public function isDisableParseContent();

		/**
		 * Изменить выключение парсинга тела письма
		 * @param bool $isDisable
		 * @return $this
		 */
		public function setDisableParseContent($isDisable);

		/**
		 * Возвращает настройки SMTP
		 * @return iSmtpSettings
		 */
		public function Smtp();
	}