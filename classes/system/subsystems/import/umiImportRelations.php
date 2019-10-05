<?php

	/**
	 * Класс управления связями между идентификаторами импортированных/импортируемых системных объектов
	 * и их идентификаторами из внешних источников.
	 *
	 * Для каждой импортируемой сущности сохраняется ее оригинальный идентификатор, чтобы при повторном импорте
	 * данных из заданного внешнего источника сущности были обновлены, а не созданы вновь.
	 *
	 * Например, из 1С приходит <товар> с <ид> равным "42907251-d287-11de-9943-000fea605ee9".
	 * UMI.CMS создает объект umiHierarchyElement с идентификатором id = 3242,
	 * и записывает в таблицу cms3_import_relations следующую запись:
	 *
	 * source_id    old_id                               new_id
	 * 123          42907251-d287-11de-9943-000fea605ee9 3242
	 *
	 * Когда в следующий раз из 1С опять придет <товар> с <ид> равным "42907251-d287-11de-9943-000fea605ee9",
	 * то заново его создавать UMI.CMS не будет, система просто обновит страницу с id = 3242.
	 */
	class umiImportRelations extends singleton implements iUmiImportRelations {
		/** @const таблица связей полей с внешними сущностями */
		const FIELD_RELATIONS_TABLE = 'cms3_import_fields';
		/** @const имя поля идентификатора внешней сущности */
		const FIELD_RELATIONS_EXTERNAL_ID_FIELD = 'field_name';
		/** @const имя поля ID типа данных, в котором содержится поле */
		const FIELD_RELATIONS_TYPE_ID_FIELD = 'type_Id';
		/** @const имя поля, в котором хранится ID поля системы */
		const FIELD_RELATIONS_INTERNAL_ID_FIELD = 'new_id';
		/** @const имя поля идентификатора обмена */
		const SOURCE_FIELD = 'source_id';

		/** @var string|null идентификатор обмена */
		private $sourceId = null;

		/** @inheritdoc */
		protected function __construct() {
		}

		/**
		 * @inheritdoc
		 * @return iUmiImportRelations
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function getSourceId($name) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$name = $connection->escape($name);

			$selectSql = <<<SQL
SELECT `id` FROM `cms3_import_sources` WHERE `source_name` = '{$name}' LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getSourceName($id) {
			$id = (int) $id;
			$sql = <<<SQL
SELECT `source_name` FROM `cms3_import_sources` WHERE `id` = $id LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql)
				->setFetchType(IQueryResult::FETCH_ROW);
			$name = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$name = (string) array_shift($fetchResult);
			}

			return $name ?: false;
		}

		/** @inheritdoc */
		public function addNewSource($name) {
			$id = $this->getSourceId($name);
			if ($id) {
				return $id;
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$name = $connection->escape($name);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_sources` (`source_name`) VALUES ('{$name}')
SQL;
			$connection->query($insertSql);

			return (int) $connection->insertId();
		}

		/** @inheritdoc */
		public function deleteSource($id) {
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_sources` WHERE `id` = $id
SQL;
			ConnectionPool::getInstance()
				->getConnection()
				->query($deleteSql);

			return $this;
		}

		/** @inheritdoc */
		public function setIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			if (!$id) {
				return false;
			}

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_relations` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_relations` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_relations` WHERE `old_id` = '{$extId}' AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldIdRelation($sourceId, $id) {
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_relations` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function setObjectIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			if (!$id) {
				return false;
			}

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_objects` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_objects` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewObjectIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_objects` WHERE old_id = '{$extId}' AND source_id = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldObjectIdRelation($sourceId, $id) {
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_objects` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isObjectRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_objects');
		}

		/** @inheritdoc */
		public function getSourceIdByObjectId($id) {
			$id = (int) $id;
			$selectSql = <<<SQL
SELECT `source_id` FROM `cms3_import_objects` WHERE `new_id` = $id LIMIT 0,1
SQL;

			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$sourceId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$sourceId = (int) array_shift($fetchResult);
			}

			return $sourceId;
		}

		/** @inheritdoc */
		public function getSourceIdByTemplateId($id) {
			$id = (int) $id;
			$selectSql = <<<SQL
SELECT `source_id` FROM `cms3_import_templates` WHERE `new_id` = $id LIMIT 0,1
SQL;

			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$sourceId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$sourceId = (int) array_shift($fetchResult);
			}

			return $sourceId;
		}

		/** @inheritdoc */
		public function setTypeIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_types` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_types` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewTypeIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_types` WHERE `old_id` = '{$extId}' AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldTypeIdRelation($sourceId, $id) {
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_types` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isTypeRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_types');
		}

		/** @inheritdoc */
		public function setFieldIdRelation($sourceId, $typeId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$typeId = (int) $typeId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_fields` 
	WHERE `source_id` = $sourceId AND `type_id` = $typeId AND (`field_name` = '{$extId}' OR `new_id` = $id)
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_fields` (`source_id`, `type_id`, `field_name`, `new_id`) 
	VALUES ($sourceId, $typeId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewFieldId($sourceId, $typeId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$typeId = (int) $typeId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_fields` 
	WHERE `source_id` = $sourceId AND `type_id` = $typeId AND `field_name` = '{$extId}' LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldFieldName($sourceId, $typeId, $id) {
			$sourceId = (int) $sourceId;
			$typeId = (int) $typeId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `field_name` FROM `cms3_import_fields` 
	WHERE `source_id` = $sourceId AND `type_id` = $typeId AND `new_id` = $id LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isFieldRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_fields');
		}

		/** @inheritdoc */
		public function setGroupIdRelation($sourceId, $typeId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$typeId = (int) $typeId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_groups` 
	WHERE `source_id` = $sourceId AND `type_id` = $typeId AND (`group_name` = '{$extId}' OR `new_id` = $id)
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_groups` (`source_id`, `type_id`, `group_name`, `new_id`) 
	VALUES ($sourceId, $typeId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewGroupId($sourceId, $typeId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$typeId = (int) $typeId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_groups` 
	WHERE `source_id` = $sourceId AND `type_id` = $typeId AND `group_name` = '{$extId}' LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldGroupName($sourceId, $typeId, $id) {
			$sourceId = (int) $sourceId;
			$typeId = (int) $typeId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `group_name` FROM `cms3_import_groups` 
	WHERE `source_id` = $sourceId AND `type_id` = $typeId AND `new_id` = $id LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isGroupRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_groups');
		}

		/** @inheritdoc */
		public function setDomainIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_domains` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_domains` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewDomainIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_domains` WHERE `old_id` = '{$extId}' AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldDomainIdRelation($sourceId, $id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_domains` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isDomainRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_domains');
		}

		/** @inheritdoc */
		public function setDomainMirrorIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_domain_mirrors` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_domain_mirrors` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewDomainMirrorIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_domain_mirrors` WHERE `old_id` = '{$extId}' AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldDomainMirrorIdRelation($sourceId, $id) {
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_domain_mirrors` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function setLangIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_langs` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_langs` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewLangIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_langs` WHERE `old_id` = '{$extId}' AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldLangIdRelation($sourceId, $id) {
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_langs` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isLangRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_langs');
		}

		/** @inheritdoc */
		public function setTemplateIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_templates` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_templates` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewTemplateIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_templates` WHERE `old_id` = '{$extId}' AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldTemplateIdRelation($sourceId, $id) {
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_templates` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isTemplateRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_templates');
		}

		/** @inheritdoc */
		public function setRestrictionIdRelation($sourceId, $extId, $id) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_import_restrictions` WHERE `source_id` = $sourceId AND (`new_id` = $id OR `old_id` = '{$extId}')
SQL;
			$connection->query($deleteSql);

			$insertSql = <<<SQL
INSERT INTO `cms3_import_restrictions` (`source_id`, `old_id`, `new_id`) VALUES ($sourceId, '{$extId}', $id)
SQL;
			$connection->query($insertSql);

			return true;
		}

		/** @inheritdoc */
		public function getNewRestrictionIdRelation($sourceId, $extId) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$extId = $connection->escape($extId);

			$selectSql = <<<SQL
SELECT `new_id` FROM `cms3_import_restrictions` WHERE `old_id` = '{$extId}' AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$id = (int) array_shift($fetchResult);
			}

			return $id ?: false;
		}

		/** @inheritdoc */
		public function getOldRestrictionIdRelation($sourceId, $id) {
			$sourceId = (int) $sourceId;
			$id = (int) $id;

			$selectSql = <<<SQL
SELECT `old_id` FROM `cms3_import_restrictions` WHERE `new_id` = $id AND `source_id` = $sourceId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$extId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$extId = (string) array_shift($fetchResult);
			}

			return $extId ?: false;
		}

		/** @inheritdoc */
		public function isRestrictionRelatedToAnotherSource($sourceId, $id) {
			return $this->isRelatedToAnotherSource($sourceId, $id, 'cms3_import_restrictions');
		}

		/**
		 * Определяет связана ли импортированная сущность с другими внешними источниками,
		 * то есть обновлялась или создавалась ли она в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @param string $table имя таблицы, где хранится сущность
		 * @return bool
		 */
		private function isRelatedToAnotherSource($sourceId, $id, $table) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sourceId = (int) $sourceId;
			$id = (int) $id;
			$table = $connection->escape($table);

			$selectSql = <<<SQL
SELECT `source_id` FROM `$table` WHERE `source_id` != $sourceId AND `new_id` = $id LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);

			return $result->length() > 0;
		}

		/**
		 * Возвращает ID поля, для которого существует связь с внешней сущностью
		 * @param string $externalId идентификатор внешней сущности
		 * @return mixed|null
		 */
		public function getFieldIdByExternalId($externalId) {
			$queryFormat = <<<SQL
SELECT `%s` FROM `%s` WHERE `%s` = '%s' AND `%s` = '%s'
SQL;

			$connection = $this->getConnection();
			$secureExternalId = $connection->escape($externalId);
			$secureSourceId = $connection->escape($this->getInjectedSourceId());

			$query = sprintf($queryFormat,
				self::FIELD_RELATIONS_INTERNAL_ID_FIELD, self::FIELD_RELATIONS_TABLE,
				self::SOURCE_FIELD, $secureSourceId, self::FIELD_RELATIONS_EXTERNAL_ID_FIELD, $secureExternalId
			);


			$result = $connection->queryResult($query, IQueryResult::FETCH_ASSOC);
			$recordInfo = $result->fetch();

			if ( !is_array($recordInfo) || !isset($recordInfo[self::FIELD_RELATIONS_INTERNAL_ID_FIELD]) ) {
				return null;
			}

			$fieldId = $recordInfo[self::FIELD_RELATIONS_INTERNAL_ID_FIELD];
			return $fieldId;
		}

		/**
		 * Устанавливает связь между внешней сущностью и полем
		 * @param string $externalId идентификатор внешней сущности
		 * @param int $fieldId ID поля системы
		 * @param int $typeId ID типа данных, в котором хранится поле и для которого присутствует связь
		 * @return bool результат установки
		 */
		public function setFieldRelation($externalId, $fieldId, $typeId) {
			try {
				$connection = $this->getConnection();
				$connection->query('START TRANSACTION');
				$this->deleteFieldRelationsByExternalId($externalId);

				$insertQueryFormat = <<<SQL
INSERT INTO `%s` (`%s`, `%s`, `%s`, `%s`) VALUES ('%s', '%s', '%s', '%s')
SQL;
				$secureExternalId = $connection->escape($externalId);
				$secureTypeId = $connection->escape($typeId);
				$secureFieldId = $connection->escape($fieldId);
				$secureSourceId = $connection->escape( $this->getInjectedSourceId() );

				$query = sprintf($insertQueryFormat, self::FIELD_RELATIONS_TABLE,
					self::SOURCE_FIELD, self::FIELD_RELATIONS_EXTERNAL_ID_FIELD, self::FIELD_RELATIONS_TYPE_ID_FIELD, self::FIELD_RELATIONS_INTERNAL_ID_FIELD,
					$secureSourceId, $secureExternalId, $secureTypeId, $secureFieldId);

				$connection->query($query);

				$connection->query('COMMIT');
			} catch (Exception $e) {
				$connection->query('ROLLBACK');
				return false;
			}

			return true;
		}

		/**
		 * Удаляет все связи с внешней сущностью
		 * @param string $externalId идентификатор внешней сущности
		 */
		private function deleteFieldRelationsByExternalId($externalId) {
			$queryFormat = <<<SQL
DELETE FROM `%s` WHERE `%s` = '%s'
SQL;
			$query = sprintf($queryFormat, self::FIELD_RELATIONS_TABLE,
				self::FIELD_RELATIONS_EXTERNAL_ID_FIELD, $this->getConnection()->escape($externalId));

			$this->getConnection()->query($query);
		}

		/**
		 * Устанавливает идентификатор обмена
		 * @param string $newId новый идентификатор обмена
		 */
		public function injectSourceId($newId) {
			$this->sourceId = $newId;
		}

		/**
		 * Возвращает последний установленный идентификатор обмена
		 * @return string
		 * @throws privateException
		 */
		private function getInjectedSourceId () {
			if ( is_null($this->sourceId) ) {
				throw new privateException('Exchange source is not specified');
			}

			return $this->sourceId;
		}

		/**
		 * Возвращает объект соединения с базой данных
		 * @return IConnection
		 */
		private function getConnection() {
			return ConnectionPool::getInstance()->getConnection();
		}

	}
