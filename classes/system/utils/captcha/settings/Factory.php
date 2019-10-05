<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Settings;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	/**
	 * Класс фабрики настроек капчи
	 * @package UmiCms\Classes\System\Utils\Captcha\Settings
	 */
	class Factory implements iFactory {

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var \iRegedit $registry реестр */
		private $registry;

		/** @var DomainDetector $domainDetector определитель домена */
		private $domainDetector;

		/** @var LanguageDetector $languageDetector определитель языка */
		private $languageDetector;

		/** @inheritdoc */
		public function __construct(
			\iConfiguration $configuration,
			\iRegedit $registry,
			DomainDetector $domainDetector,
			LanguageDetector $languageDetector
		) {
			$this->configuration = $configuration;
			$this->registry = $registry;
			$this->domainDetector = $domainDetector;
			$this->languageDetector = $languageDetector;
		}

		/** @inheritdoc */
		public function getCommonSettings() {
			return new Common($this->getConfiguration(), $this->getRegistry());
		}

		/** @inheritdoc */
		public function getSiteSettings($domainId = null, $langId = null) {
			$domainId = $domainId ?: $this->getDomainDetector()->detectId();
			$langId = $langId ?: $this->getLanguageDetector()->detectId();
			return new Site($domainId, $langId, $this->getRegistry());
		}

		/** @inheritdoc */
		public function getCurrentSettings($domainId = null, $langId = null) {
			$siteSettings = $this->getSiteSettings($domainId, $langId);

			if ($siteSettings->shouldUseSiteSettings()) {
				return $siteSettings;
			}

			return $this->getCommonSettings();
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает реестр
		 * @return \iRegedit
		 */
		private function getRegistry() {
			return $this->registry;
		}

		/**
		 * Возвращает определитель домена
		 * @return DomainDetector
		 */
		private function getDomainDetector() {
			return $this->domainDetector;
		}

		/**
		 * Возвращает определитель языка
		 * @return LanguageDetector
		 */
		private function getLanguageDetector() {
			return $this->languageDetector;
		}
	}
