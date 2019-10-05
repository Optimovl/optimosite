<?php

	namespace UmiCms\System\Cache\Key;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	/**
	 * Класс генератора ключей для кеширования
	 * @package UmiCms\System\Cache\Key
	 */
	class Generator implements iGenerator {

		/** @var \iConfiguration $configuration */
		private $configuration;

		/** @var DomainDetector $domainDetector */
		private $domainDetector;

		/** @var LanguageDetector $languageDetector */
		private $languageDetector;

		/** @var string $basePrefix базовый префикс для ключей */
		private $basePrefix;

		/** @inheritdoc */
		public function __construct(
			\iConfiguration $configuration,
			DomainDetector $domainDetector,
			LanguageDetector $languageDetector
		) {
			$this->configuration = $configuration;
			$this->domainDetector = $domainDetector;
			$this->languageDetector = $languageDetector;
			$this->generateBasePrefix();
		}

		/** @inheritdoc */
		public function createKey($key, $storeType = null) {
			$storeType = $storeType ?: '';
			return $this->getBasePrefix() . $this->getLanguageId() . $this->getDomainId() . $key . $storeType;
		}

		/** @inheritdoc */
		public function createKeyForQuery($query) {
			return $this->getBasePrefix() . sha1($query);
		}

		/** @inheritdoc */
		public function getBasePrefix() {
			return $this->basePrefix;
		}

		/** Формирует базовый префикс */
		private function generateBasePrefix() {
			$salt = $this->getConfiguration()
				->get('system', 'salt');
			$hash = hash('crc32', $salt);
			$this->basePrefix = sprintf('umic:%s:', $hash);
		}

		/**
		 * Возвращает идентификатор домена
		 * @return int
		 */
		private function getDomainId() {
			return $this->getDomainDetector()->detectId();
		}

		/**
		 * Возвращает идентификатор языка
		 * @return int
		 */
		private function getLanguageId() {
			return $this->getLanguageDetector()->detectId();
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает определитель домена
		 * @return DomainDetector
		 */
		private function getDomainDetector() {
			return $this->domainDetector;
		}

		/**
		 * Возвращает определитель языков
		 * @return LanguageDetector
		 */
		private function getLanguageDetector() {
			return $this->languageDetector;
		}
	}
