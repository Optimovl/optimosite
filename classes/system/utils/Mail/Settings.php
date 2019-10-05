<?php

	namespace UmiCms\Classes\System\Utils\Mail;

	use UmiCms\Service;
	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	class Settings implements iSettings {

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

		/**
		 * @inheritdoc
		 * @throws \coreException
		 */
		public function getAdminEmail() {
			return $this->getMailOption('admin_email');
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 */
		public function getSenderEmail() {
			return $this->getMailOption('email_from');
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 */
		public function getSenderName() {
			return $this->getMailOption('fio_from');
		}

		/**
		 * Возвращает значение настройки почты
		 * @param string $name имя настройки
		 * @return string
		 * @throws \coreException
		 */
		private function getMailOption($name) {
			$registry = Service::Registry();

			return $this->isUseCustomMailSettings()
				? $registry->get($this->getMailCustomPrefix() . $name)
				: $registry->get($this->getMailCommonPrefix() . $name);
		}

		/**
		 * Определяет использовать ли настройки почты
		 * специфичные для конкретного сайта и языковой версии
		 * @return string
		 * @throws \coreException
		 */
		private function isUseCustomMailSettings() {
			return Service::Registry()
				->get($this->getMailCustomPrefix() . 'use-custom-settings');
		}

		/**
		 * Возвращает префикс настроек почты общих для всех сайтов
		 * @return string
		 */
		private function getMailCommonPrefix() {
			return "//settings/";
		}

		/**
		 * Возвращает префикс настроек почты специфичный для конкретного сайта и языковой версии
		 * @return string
		 * @throws \coreException
		 */
		private function getMailCustomPrefix() {
			$domainId = Service::DomainDetector()->detectId();
			$languageId = Service::LanguageDetector()->detectId();

			return "//settings/mail/$domainId/$languageId/";
		}
	}