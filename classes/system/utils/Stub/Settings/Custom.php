<?php

	namespace UmiCms\Classes\System\Utils\Stub\Settings;

	use UmiCms\Service;

	/**
	 * Класс для работы с настройками доступа к сайту, специфическими для конкретного сайта
	 * @package UmiCms\Classes\System\Utils\Stub\Settings
	 */
	class Custom extends Settings {

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

		/** @inheritdoc */
		public function isIpStub() {
			return $this->isUseConfigDirective('stub', 'enabled-for-domain');
		}

		/** @inheritdoc */
		public function setIpStub($isUse) {
			$this->setConfigDirective($isUse, 'stub', 'enabled-for-domain');
			return $this;
		}

		/** @inheritdoc */
		public function isUseBlackList() {
			return $this->isUseConfigDirective(
				'kernel',
				'use-ip-blacklist-guide-for-domain'
			);
		}

		/** @inheritdoc */
		public function setUseBlackList($isUse) {
			$this->setConfigDirective($isUse, 'kernel', 'use-ip-blacklist-guide-for-domain');
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getBlackList() {
			return $this->getGuideList('ip-blacklist');
		}

		/** @inheritdoc */
		public function addToBlackList($name) {
			return $this->addToGuide($name, 'ip-blacklist');
		}

		/** @inheritdoc */
		public function getStubFilePath() {
			return CURRENT_WORKING_DIR
				. iSettings::STUB_DIRECTORY
				. iSettings::FILE_NAME
				. '_'
				. $this->domainId
				. iSettings::FILE_EXTENSION;
		}

		/** @inheritdoc */
		protected function getPrefix() {
			return "//umiStub/{$this->domainId}/{$this->langId}";
		}

		/** @inheritdoc */
		protected function validateObject(\iUmiObject $object) {
			return $object->getValue('domain_id') == $this->domainId;
		}

		/** @inheritdoc */
		protected function addToGuide($name, $guid) {
			$objectId = $this->createObject($name, $guid);

			$object = \umiObjectsCollection::getInstance()
				->getById($objectId);

			if (!$object instanceof \iUmiObject) {
				return false;
			}

			$object->setValue('domain_id', $this->domainId);
			$object->commit();

			return $objectId;
		}

		/**
		 * Возвращает хост домена
		 * @return string
		 */
		private function getHost() {
			return Service::DomainCollection()
				->getDomain($this->domainId)
				->getHost();
		}

		/** @inheritdoc */
		protected function isUseConfigDirective($group, $directive) {
			$hostList = \mainConfiguration::getInstance()
				->get($group, $directive);

			return $this->isHostInList($hostList);
		}

		/** @inheritdoc */
		protected function setConfigDirective($isUse, $group, $directive) {
			$mainConfiguration = \mainConfiguration::getInstance();
			$paramHostList = $mainConfiguration->get($group, $directive) ?: [];
			$isHostInHostList = $this->isHostInList($paramHostList);
			$host = $this->getHost();

			if ($isUse && !$isHostInHostList) {
				$paramHostList[] = $host;
			} elseif (!$isUse && $isHostInHostList) {
				$paramHostList = array_diff($paramHostList, [$host]);
			}

			$mainConfiguration->set($group, $directive, $paramHostList);
			$mainConfiguration->save();

			return $this;
		}

		/**
		 * Находится ли хост домена в списке
		 * @param array $list проверяемый список
		 * @return bool
		 */
		private function isHostInList($list) {
			return is_array($list) ? in_array($this->getHost(), $list) : false;
		}

	}