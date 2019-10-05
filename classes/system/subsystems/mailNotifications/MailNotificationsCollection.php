<?php

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;
	use UmiCms\System\MailNotification\iCollection;

	/** Коллекция уведомлений */
	class MailNotificationsCollection implements
		iUmiDataBaseInjector,
		iUmiService,
		iUmiConstantMapInjector,
		iClassConfigManager,
		iUmiLanguagesInjector,
		iUmiDomainsInjector,
		iCollection {

		use tUmiDataBaseInjector;
		use tUmiService;
		use tCommonCollection;
		use tUmiConstantMapInjector;
		use tClassConfigManager;
		use tUmiLanguagesInjector;
		use tUmiDomainsInjector;

		/** @var string класс элементов коллекции */
		private $collectionItemClass = 'MailNotification';

		/** @var DomainDetector $domainDetector */
		private $domainDetector;

		/** @var LanguageDetector $languageDetector */
		private $languageDetector;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'MailNotifications',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false
				],
				[
					'name' => 'LANG_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
				],
				[
					'name' => 'DOMAIN_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'MODULE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				]
			]
		];

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/** @inheritdoc */
		public function getById($id) {
			return $this->getBy($this->getMap()->get('ID_FIELD_NAME'), $id);
		}

		/** @inheritdoc */
		public function getByName($name) {
			return $this->getBy($this->getMap()->get('NAME_FIELD_NAME'), $name);
		}

		/** @inheritdoc */
		public function getByModule($module) {
			return $this->getBy($this->getMap()->get('MODULE_FIELD_NAME'), $module);
		}

		/** @inheritdoc */
		public function getCurrentByName($name) {
			$result = $this->get([
				$this->getMap()->get('LANG_ID_FIELD_NAME') => $this->getLanguageDetector()->detectId(),
				$this->getMap()->get('DOMAIN_ID_FIELD_NAME') => $this->getDomainDetector()->detectId(),
				$this->getMap()->get('NAME_FIELD_NAME') => $name
			]);

			if (umiCount($result) > 0) {
				return array_shift($result);
			}

			return $this->getDefaultByName($name);
		}

		/** @inheritdoc */
		public function setDomainDetector(DomainDetector $detector) {
			$this->domainDetector = $detector;
			return $this;
		}

		/** @inheritdoc */
		public function setLanguageDetector(LanguageDetector $detector) {
			$this->languageDetector = $detector;
			return $this;
		}

		/** @inheritdoc */
		protected function getDefaultByName($name) {
			$defaultLang = $this->getLanguageCollection()->getDefaultLang();
			$defaultDomain = $this->getDomainCollection()->getDefaultDomain();

			$result = $this->get([
				$this->getMap()->get('LANG_ID_FIELD_NAME') => $defaultLang->getId(),
				$this->getMap()->get('DOMAIN_ID_FIELD_NAME') => $defaultDomain->getId(),
				$this->getMap()->get('NAME_FIELD_NAME') => $name,
			]);

			if (umiCount($result) > 0) {
				return array_shift($result);
			}

			return null;
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
