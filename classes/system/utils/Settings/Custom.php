<?php

	namespace UmiCms\Classes\System\Utils\Settings;

	/**
	 * Абстрактный класс для работы с настройками, специфическими для конкретного сайта
	 * @package UmiCms\Classes\System\Utils\Settings
	 */
	abstract class Custom implements iSettings, \iUmiRegistryInjector {

		use \tUmiRegistryInjector;

		/** @var int|null ИД домена сайта, для которого берутся настройки */
		protected $domainId;

		/** @var int|null ИД языка сайта, для которого берутся настройки */
		protected $langId;

		/**
		 * Конструктор
		 * @param int $domainId ИД домена сайта, для которого берутся настройки
		 * @param int $langId ИД языка сайта, для которого берутся настройки
		 * @param \iRegedit $registry класс регистра
		 * @throws \ErrorException
		 */
		public function __construct($domainId, $langId, \iRegedit $registry) {
			if (!is_numeric($domainId) || !is_numeric($langId)) {
				throw new \ErrorException(getLabel('error-wrong-domain-and-lang-ids'));
			}
			$this->domainId = $domainId;
			$this->langId = $langId;
			$this->setRegistry($registry);
		}

		/**
		 * Возвращает настройку "Использовать настройки сайта"
		 * @return bool
		 * @throws \Exception
		 */
		public function shouldUseCustomSettings() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/use-custom-settings");
		}

		/**
		 * Устанавливает настройку "Использовать настройки сайта"
		 * @param bool $flag новое значение
		 * @return $this
		 * @throws \Exception
		 */
		public function setShouldUseCustomSettings($flag) {
			$this->getRegistry()->set("{$this->getPrefix()}/use-custom-settings", $flag);
			return $this;
		}

		/**
		 * Возвращает общий для настроек префикс в реестре
		 * @return string
		 */
		abstract protected function getPrefix();
	}