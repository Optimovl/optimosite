<?php

	namespace UmiCms\Classes\System\Utils\Mail;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	/**
	 * Интерфейс класса для получения настроек отправки почты
	 * @package UmiCms\Classes\System\Utils\Mail
	 */
	interface iSettings {

		/**
		 * Конструктор
		 * @param \iRegedit $registry реестр
		 * @param DomainDetector $domainDetector определитель домена
		 * @param LanguageDetector $languageDetector определитель языка
		 */
		public function __construct(
			\iRegedit $registry,
			DomainDetector $domainDetector,
			LanguageDetector $languageDetector
		);

		/**
		 * Возвращает email администратора
		 * @return string
		 */
		public function getAdminEmail();

		/**
		 * Возвращает email отправителя
		 * @return string
		 */
		public function getSenderEmail();


		/**
		 * Возвращает имя отправителя
		 * @return string
		 */
		public function getSenderName();

	}