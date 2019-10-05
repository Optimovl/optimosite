<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Settings;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	/**
	 * Интерфейс фабрики настроек капчи
	 * @package UmiCms\Classes\System\Utils\Captcha\Settings
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration конфигурцация
		 * @param \iRegedit $registry реестр
		 * @param DomainDetector $domainDetector определитель домена
		 * @param LanguageDetector $languageDetector определитель языка
		 */
		public function __construct(
			\iConfiguration $configuration,
			\iRegedit $registry,
			DomainDetector $domainDetector,
			LanguageDetector $languageDetector
		);

		/**
		 * Возвращает настройки капчи, общие для всех сайтов
		 * @return Common
		 */
		public function getCommonSettings();

		/**
		 * Возвращает настройки капчи, специфические для конкретного сайта
		 * @param int $domainId ИД домена сайта, для которого берутся настройки
		 * @param int $langId ИД языка сайта, для которого берутся настройки
		 * @return Site
		 */
		public function getSiteSettings($domainId = null, $langId = null);

		/**
		 * Возвращает настройки капчи для текущего сайта
		 * @param int|null $domainId ИД домена сайта, для которого берутся настройки. Если не указан - возьмет текущий.
		 * @param int|null $langId ИД языка сайта, для которого берутся настройки. Если не указан - возьмет текущий.
		 * @return iSettings
		 */
		public function getCurrentSettings($domainId = null, $langId = null);
	}
