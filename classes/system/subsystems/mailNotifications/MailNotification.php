<?php

	use UmiCms\Service;
	use UmiCms\System\iMailNotification;

	/** Уведомление */
	class MailNotification implements
		iUmiDataBaseInjector,
		iUmiConstantMapInjector,
		iClassConfigManager,
		iMailNotification {

		use tCommonCollectionItem;
		use tUmiDataBaseInjector;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var int идентификатор языка */
		private $langId;

		/** @var int идентификатор домена */
		private $domainId;

		/** @var string имя */
		private $name;

		/** @var string модуль */
		private $module;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId'
				],
				[
					'name' => 'LANG_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setLangId',
					'getter' => 'getLangId'
				],
				[
					'name' => 'DOMAIN_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setDomainId',
					'getter' => 'getDomainId'
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName'
				],
				[
					'name' => 'MODULE_FIELD_NAME',
					'required' => true,
					'setter' => 'setModule',
					'getter' => 'getModule'
				]
			]
		];

		/** @inheritdoc */
		public function setId($id) {
			$this->setDifferentValue('id', $id, 'int');
		}

		/** @inheritdoc */
		public function getLangId() {
			return $this->langId;
		}

		/** @inheritdoc */
		public function setLangId($id) {
			$this->setDifferentValue('langId', $id, 'int');
		}

		/** @inheritdoc */
		public function getDomainId() {
			return $this->domainId;
		}

		/** @inheritdoc */
		public function setDomainId($id) {
			$this->setDifferentValue('domainId', $id, 'int');
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			$this->setDifferentValue('name', $name, 'string');
		}

		/** @inheritdoc */
		public function getModule() {
			return $this->module;
		}

		/** @inheritdoc */
		public function setModule($module) {
			$this->setDifferentValue('module', $module, 'string');
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$langIdField = $connection->escape($map->get('LANG_ID_FIELD_NAME'));
			$domainIdField = $connection->escape($map->get('DOMAIN_ID_FIELD_NAME'));
			$nameField = $connection->escape($map->get('NAME_FIELD_NAME'));
			$moduleField = $connection->escape($map->get('MODULE_FIELD_NAME'));

			$id = $this->getId();
			$name = $connection->escape($this->getName());
			$module = $connection->escape($this->getModule());
			$langId = $connection->escape($this->getLangId());
			$domainId = $connection->escape($this->getDomainId());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$langIdField` = '$langId', `$domainIdField` = '$domainId', `$nameField` = '$name', `$moduleField` = '$module'
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function getTemplates() {
			$mailTemplates = Service::MailTemplates();
			return $mailTemplates->getByNotificationId($this->getId());
		}

		/** @inheritdoc */
		public function getTemplateByName($name) {
			$mailTemplates = Service::MailTemplates();
			$map = $mailTemplates->getMap();

			$templates = $mailTemplates->get([
				$map->get('NOTIFICATION_ID_FIELD_NAME') => $this->getId(),
				$map->get('NAME_FIELD_NAME') => $name
			]);

			return (umiCount($templates) > 0) ? array_shift($templates) : null;
		}
	}
