<?php

	namespace UmiCms\Classes\System\Utils\Settings;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	/**
	 * Интерфейс абстрактной фабрики настроек
	 * @package UmiCms\Classes\System\Utils\Settings
	 */
	interface iFactory {

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
		 * Создает настройки, общие для всех сайтов
		 * @return Common
		 */
		public function createCommon();

		/**
		 * Создает настройки, специфические для конкретного сайта
		 * @param int $domainId ИД домена сайта, для которого берутся настройки
		 * @param int $langId ИД языка сайта, для которого берутся настройки
		 * @return Custom
		 */
		public function createCustom($domainId = null, $langId = null);
	}