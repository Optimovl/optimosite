<?php

	/** Переменная шаблона уведомлений */
	class MailVariable implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var int идентификатор шаблона */
		private $templateId;

		/** @var string имя */
		private $name;

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
					'name' => 'TEMPLATE_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setTemplateId',
					'getter' => 'getTemplateId'
				],
				[
					'name' => 'VARIABLE_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName'
				],
			]
		];

		/** @inheritdoc */
		public function setId($id) {
			$this->setDifferentValue('id', $id, 'int');
		}

		/**
		 * Возвращает идентикатор шаблона уведомлений
		 * @return int
		 */
		public function getTemplateId() {
			return $this->templateId;
		}

		/**
		 * Изменяет идентификатор шаблона уведомлений
		 * @param int $id
		 */
		public function setTemplateId($id) {
			$this->setDifferentValue('templateId', $id, 'int');
		}

		/**
		 * Возвращает имя переменной
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * Изменяет имя переменной
		 * @param string $name
		 */
		public function setName($name) {
			$this->setDifferentValue('name', $name, 'string');
		}

		/**
		 * @inheritdoc
		 * @throws Exception
		 */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$templateIdField = $connection->escape($map->get('TEMPLATE_ID_FIELD_NAME'));
			$nameField = $connection->escape($map->get('VARIABLE_FIELD_NAME'));

			$id = $this->getId();
			$name = $connection->escape($this->getName());
			$templateId = $connection->escape($this->getTemplateId());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$templateIdField` = '$templateId', `$nameField` = '$name'
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}
	}
