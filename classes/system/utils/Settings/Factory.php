<?php

	namespace UmiCms\Classes\System\Utils\Settings;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	/**
	 * Абстрактный класс фабрики настроек
	 * @package UmiCms\Classes\System\Utils\Settings
	 */
	abstract class Factory implements iFactory {

		/** @var \iRegedit реестр */
		protected $registry;

		/** @var DomainDetector определитель домена */
		protected $domainDetector;

		/** @var LanguageDetector определитель языка */
		protected $languageDetector;

		/** @inheritdoc */
		public function __construct(
			\iRegedit $registry,
			DomainDetector $domainDetector,
			LanguageDetector $languageDetector
		) {
			$this->registry = $registry;
			$this->domainDetector = $domainDetector;
			$this->languageDetector = $languageDetector;
		}

		/** @inheritdoc */
		public function createCommon() {
			return $this->getCommon();
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 */
		public function createCustom($domainId = null, $langId = null) {
			$domainId = $domainId ?: $this->getDomainDetector()->detectId();
			$langId = $langId ?: $this->getLanguageDetector()->detectId();
			return $this->getCustom($domainId, $langId);
		}

		/**
		 * Возвращает класс настроек, общий для сайтов
		 * @return Common
		 */
		abstract protected function getCommon();

		/**
		 * Возвращает класс настроек, для конкретного сайта
		 * @param int $domainId
		 * @param int $langId
		 * @return Custom
		 */
		abstract protected function getCustom($domainId, $langId);

		/**
		 * Возвращает реестр
		 * @return \iRegedit
		 */
		protected function getRegistry() {
			return $this->registry;
		}

		/**
		 * Возвращает определитель домена
		 * @return DomainDetector
		 */
		protected function getDomainDetector() {
			return $this->domainDetector;
		}

		/**
		 * Возвращает определитель языка
		 * @return LanguageDetector
		 */
		protected function getLanguageDetector() {
			return $this->languageDetector;
		}
	}