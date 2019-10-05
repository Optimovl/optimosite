<?php

	/**
	 * @deprecated
	 * Используйте system/subsystems/import/umiImportRelations.php
	 */
	class umiImportRelations extends singleton implements iUmiImportRelations {

		protected function __construct() {
		}

		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		public function getSourceId($source_name) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_name = $connection->escape($source_name);

			$sql = "SELECT id FROM cms3_import_sources WHERE source_name = '{$source_name}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return array_shift($fetchResult);
		}

		public function addNewSource($source_name) {
			if ($source_id = $this->getSourceId($source_name)) {
				return $source_id;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$source_name = $connection->escape($source_name);

			$sql = "INSERT INTO cms3_import_sources (source_name) VALUES('{$source_name}')";
			$connection->query($sql);

			return $connection->insertId();
		}

		public function setIdRelation($source_id, $old_id, $new_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$old_id = $connection->escape($old_id);
			$new_id = $connection->escape($new_id);

			if (!$new_id) {
				return false;
			}

			$sql =
				"DELETE FROM cms3_import_relations WHERE source_id = '{$source_id}' AND (new_id = '{$new_id}' OR old_id = '{$old_id}')";
			$connection->query($sql);

			$sql =
				"INSERT INTO cms3_import_relations (source_id, old_id, new_id) VALUES('{$source_id}', '{$old_id}', '{$new_id}')";
			$connection->query($sql);

			return true;
		}

		public function getNewIdRelation($source_id, $old_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$old_id = $connection->escape($old_id);

			$sql = "SELECT new_id FROM cms3_import_relations WHERE old_id = '{$old_id}' AND source_id = '{$source_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return (string) array_shift($fetchResult);
		}

		public function getOldIdRelation($source_id, $new_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$new_id = $connection->escape($new_id);

			$sql = "SELECT old_id FROM cms3_import_relations WHERE new_id = '{$new_id}' AND source_id = '{$source_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return (string) array_shift($fetchResult);
		}

		public function setTypeIdRelation($source_id, $old_id, $new_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$old_id = $connection->escape($old_id);
			$new_id = $connection->escape($new_id);

			$sql =
				"DELETE FROM cms3_import_types WHERE source_id = '{$source_id}' AND (new_id = '{$new_id}' OR old_id = '{$old_id}')";
			$connection->query($sql);

			$sql =
				"INSERT INTO cms3_import_types (source_id, old_id, new_id) VALUES('{$source_id}', '{$old_id}', '{$new_id}')";
			$connection->query($sql);

			return true;
		}

		public function getNewTypeIdRelation($source_id, $old_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$old_id = $connection->escape($old_id);

			$sql = "SELECT new_id FROM cms3_import_types WHERE old_id = '{$old_id}' AND source_id = '{$source_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return (string) array_shift($fetchResult);
		}

		public function getOldTypeIdRelation($source_id, $new_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$new_id = $connection->escape($new_id);

			$sql = "SELECT old_id FROM cms3_import_types WHERE new_id = '{$new_id}' AND source_id = '{$source_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return (string) array_shift($fetchResult);
		}

		public function setFieldIdRelation($source_id, $type_id, $old_field_name, $new_field_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$type_id = $connection->escape($type_id);
			$old_field_name = $connection->escape($old_field_name);
			$new_field_id = $connection->escape($new_field_id);

			$umiFields = umiFieldsCollection::getInstance();

			if (!$umiFields->isExists($new_field_id)) {
				throw new publicAdminException(__METHOD__ . ': cant set relation for non-existing field with id = ' .
					$new_field_id);
			}

			$sql =
				"DELETE FROM cms3_import_fields WHERE source_id = '{$source_id}' AND type_id = '{$type_id}' AND (field_name = '{$old_field_name}' OR new_id = '{$new_field_id}')";
			$connection->query($sql);

			$sql =
				"INSERT INTO cms3_import_fields (source_id, type_id, field_name, new_id) VALUES('{$source_id}', '{$type_id}', '{$old_field_name}', '{$new_field_id}')";
			$connection->query($sql);

			return (string) $new_field_id;
		}

		public function getNewFieldId($source_id, $type_id, $old_field_name) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$type_id = $connection->escape($type_id);
			$old_field_name = $connection->escape($old_field_name);

			$sql =
				"SELECT new_id FROM cms3_import_fields WHERE source_id = '{$source_id}' AND type_id = '{$type_id}' AND field_name = '{$old_field_name}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return (string) array_shift($fetchResult);
		}

		public function getOldFieldName($source_id, $type_id, $new_field_id) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$source_id = $connection->escape($source_id);
			$type_id = $connection->escape($type_id);
			$new_field_id = $connection->escape($new_field_id);

			$sql =
				"SELECT field_name FROM cms3_import_fields WHERE source_id = '{$source_id}' AND type_id = '{$type_id}' AND new_id = '{$new_field_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return (string) array_shift($fetchResult);
		}
	}

	;
?>
