<?php

	namespace UmiCms\Classes\System\Utils\Stub\Settings;

	/**
	 * Класс для работы с настройками доступа к сайту, общими для всех сайтов
	 * @package UmiCms\Classes\System\Utils\Stub\Settings
	 */
	class Common extends Settings {

		/**
		 * Конструктор
		 * @param \iRegedit $registry класс регистра
		 */
		public function __construct(\iRegedit $registry) {
			$this->setRegistry($registry);
		}

		/** @inheritdoc */
		public function isIpStub() {
			return $this->isUseConfigDirective('stub', 'enabled');
		}

		/** @inheritdoc */
		public function setIpStub($isUse) {
			$this->setConfigDirective('stub', 'enabled', $isUse);
			return $this;
		}

		/** @inheritdoc */
		public function isUseBlackList() {
			return $this->isUseConfigDirective('kernel', 'use-ip-blacklist-guide');
		}

		/** @inheritdoc */
		public function setUseBlackList($isUse) {
			$this->setConfigDirective('kernel', 'use-ip-blacklist-guide', $isUse);
			return $this;
		}

		/** @inheritdoc */
		public function getStubFilePath() {
			return CURRENT_WORKING_DIR
				. iSettings::STUB_DIRECTORY
				. iSettings::FILE_NAME
				. iSettings::FILE_EXTENSION;
		}

		/**
		 * Возвращает общий для настроек префикс в реестре
		 * @return string
		 */
		protected function getPrefix() {
			return '//umiStub';
		}

		/** @inheritdoc */
		protected function setConfigDirective($group, $directive, $value) {
			$mainConfiguration = \mainConfiguration::getInstance();
			$mainConfiguration->set($group, $directive, $value);
			$mainConfiguration->save();
		}

		/** @inheritdoc */
		protected function isUseConfigDirective($group, $directive) {
			return (bool) \mainConfiguration::getInstance()
				->get($group, $directive);
		}

		/** @inheritdoc */
		protected function validateObject(\iUmiObject $object) {
			return !$object->getValue('domain_id');
		}

		/**
		 * Добавляет объект в справочник и возвращает его идентификатор
		 * @param string $name имя объекта
		 * @param string $guid гуид справочника
		 * @return int|bool
		 */
		protected function addToGuide($name, $guid) {
			return $this->createObject($name, $guid);
		}
	}