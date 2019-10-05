<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy\Relation;

	use UmiCms\System\Data\Object\Type\Hierarchy\iRelation;

	/**
	 * Класс репозитория иерахических связей между объектными типами данных
	 * @package UmiCms\System\Data\Object\Type\Hierarchy\Relation
	 */
	class Repository implements iRepository {

		/** @var \IConnection $connection подключение к базе данных */
		private $connection;

		/** @var iFactory $factory фабрика иерархических связей */
		private $factory;

		/** @inheritdoc */
		public function __construct(\IConnection $connection, iFactory $factory) {
			$this->connection = $connection;
			$this->factory = $factory;
		}

		/** @inheritdoc */
		public function getChildList($ancestorId) {
			$parentIdCondition = $this->getNullOrIdCondition($ancestorId);
			$sql = <<<SQL
SELECT `id`, `parent_id`, `child_id`, `level`
FROM `cms3_object_type_tree`
WHERE `parent_id` $parentIdCondition
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildRelationList($result);
		}

		/** @inheritdoc */
		public function getChildListWithDomain($ancestorId, $domainId) {
			$ancestorId = (int) $ancestorId;
			$parentIdCondition = $this->getNullOrIdCondition($ancestorId);
			$domainIdCondition = $this->getDomainIdCondition($domainId, '`cms3_object_types`.`domain_id`');
			$sql = <<<SQL
SELECT `cms3_object_type_tree`.`id`, `cms3_object_type_tree`.`parent_id`, `child_id`, `level`
FROM `cms3_object_type_tree`
LEFT JOIN `cms3_object_types` ON `cms3_object_type_tree`.`child_id` = `cms3_object_types`.`id`
WHERE `cms3_object_type_tree`.`parent_id` $parentIdCondition AND $domainIdCondition
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildRelationList($result);
		}

		/** @inheritdoc */
		public function getNearestChildIdList($ancestorId) {
			$ancestorId = (int) $ancestorId;
			$sql = <<<SQL
SELECT `id` FROM `cms3_object_types` WHERE `parent_id` = $ancestorId
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildIdList($result);
		}

		/** @inheritdoc */
		public function getNearestChildIdListWithDomain($ancestorId, $domainId) {
			$ancestorId = (int) $ancestorId;
			$domainIdCondition = $this->getDomainIdCondition($domainId);
			$sql = <<<SQL
SELECT `id` FROM `cms3_object_types` WHERE `parent_id` = $ancestorId AND $domainIdCondition
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildIdList($result);
		}

		/** @inheritdoc */
		public function getNearestAncestorId($childId) {
			$childId = (int) $childId;
			$sql = <<<SQL
SELECT `parent_id` FROM `cms3_object_types` WHERE `id` = $childId LIMIT 0,1
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildNumberValue($result);
		}

		/** @inheritdoc */
		public function getChildListByAncestorIdList(array $idList) {
			$idList = $this->prepareIdList($idList);

			if (isEmptyString($idList)) {
				return [];
			}

			$sql = <<<SQL
SELECT `id`, `parent_id`, `child_id`, `level`
FROM `cms3_object_type_tree`
WHERE `parent_id` IN ($idList)
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildRelationList($result);
		}

		/** @inheritdoc */
		public function getAncestorList($childId) {
			$childId = (int) $childId;
			$sql = <<<SQL
SELECT `id`, `parent_id`, `child_id`, `level`
FROM `cms3_object_type_tree`
WHERE `child_id` = $childId
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildRelationList($result);
		}

		/** @inheritdoc */
		public function createRecursively($ancestorId, $childId) {
			$connection = $this->getConnection();
			$connection->startTransaction('Create object type relation');

			try {
				$ancestorList = $this->getAncestorList($ancestorId);
				$parentMaxLevel = 0;
				foreach ($ancestorList as $ancestor) {
					$parentMaxLevel = max($parentMaxLevel, $ancestor->getLevel());
				}

				$childLevel = ($ancestorId == 0) ? 0 : $parentMaxLevel + 1;
				foreach ($ancestorList as $ancestor) {
					$this->create($ancestor->getParentId(), $childId, $childLevel);
				}

				$relation = $this->create($ancestorId, $childId, $childLevel);
			} catch (\databaseException $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			return $relation;
		}

		/** @inheritdoc */
		public function create($ancestorId, $childId, $level) {
			$ancestorId = ($ancestorId === 0) ? 'NULL' : (int) $ancestorId;
			$childId = (int) $childId;
			$level = (int) $level;

			$sql = <<<SQL
INSERT INTO `cms3_object_type_tree` (`parent_id`, `child_id`, `level`) VALUES ($ancestorId, $childId, $level) 
ON DUPLICATE KEY UPDATE `parent_id` = $ancestorId, `child_id` = $childId, `level` = $level
SQL;
			$connection = $this->getConnection();
			$connection->query($sql);
			return $this->get($connection->insertId());
		}

		/** @inheritdoc */
		public function deleteInvolving($typeId) {
			$typeId = (int) $typeId;
			$sql = <<<SQL
DELETE FROM `cms3_object_type_tree` WHERE `parent_id` = $typeId OR `child_id` = $typeId
SQL;
			$connection = $this->getConnection();
			$connection->query($sql);
			return $connection->affectedRows() > 0;
		}

		/** @inheritdoc */
		public function deleteAll() {
			$sql = <<<SQL
TRUNCATE TABLE `cms3_object_type_tree`
SQL;
			$this->getConnection()
				->query($sql);
			return $this;
		}

		/**
		 * Возвращает связь по id
		 * @param int $id идентификатор связи
		 * @return iRelation|null
		 * @throws \databaseException
		 */
		private function get($id) {
			$id = (int) $id;
			$sql = <<<SQL
SELECT `id`, `parent_id`, `child_id`, `level`
FROM `cms3_object_type_tree`
WHERE `id` = $id
LIMIT 0, 1
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			return $this->buildRelation($result);
		}

		/**
		 * Возвращает часть sql выражения для фильтрации по идентификатору домена
		 * @param int $domainId идентификатор домена
		 * @param string $field имя поля в бд
		 * @return string
		 */
		private function getDomainIdCondition($domainId, $field = '`domain_id`') {
			$domainId = (int) $domainId;
			$format = ($domainId > 0) ? "($field %s OR $field IS NULL)" : "$field %s";
			return sprintf($format, $this->getNullOrIdCondition($domainId));
		}

		/**
		 * Возвращает условие выборки для фильтрации по id или по null в качестве id
		 * @param int $id идентификатор
		 * @return string
		 */
		private function getNullOrIdCondition($id) {
			$id = (int) $id;
			return ($id === 0) ? 'IS NULL' : "= $id";
		}

		/**
		 * Подготавливает список идентификаторов для вставки в запрос
		 *
		 *  [1, 2, 3] => '1,2,3'
		 *
		 * @param array $idList список идентификаторов
		 * @return string
		 */
		private function prepareIdList(array $idList) {
			$idList = array_map('intval', $idList);
			$idList = array_unique($idList);

			if (isEmptyArray($idList)) {
				return '';
			}

			return implode(',', $idList);
		}

		/**
		 * Формирует результат выборки числового значения
		 * @param \IQueryResult $result результат выборки
		 * @return int
		 */
		private function buildNumberValue(\IQueryResult $result) {
			$row = $result->fetch();
			return (int) is_array($row) ? array_shift($row) : null;
		}

		/**
		 * Формирует результат выборки иерархической связи
		 * @param \IQueryResult $result результат выборки
		 * @return iRelation|null
		 */
		private function buildRelation(\IQueryResult $result) {
			if ($result->length() === 0) {
				return null;
			}

			$row = $result->setFetchType(\IQueryResult::FETCH_ASSOC)
				->fetch();
			return $this->getFactory()
				->create($row);
		}

		/**
		 * Формирует результат выборки списка иерархических связей
		 * @param \IQueryResult $result
		 * @return iRelation[]
		 */
		private function buildRelationList(\IQueryResult $result) {
			if ($result->length() === 0) {
				return [];
			}

			$result->setFetchType(\IQueryResult::FETCH_ASSOC);
			$relationList = [];
			$factory = $this->getFactory();

			foreach ($result as $row) {
				$relationList[] = $factory->create($row);
			}

			return $relationList;
		}

		/**
		 * Формирует результат выборки списка идентификаторов типов
		 * @param \IQueryResult $result
		 * @return int[]
		 */
		private function buildIdList(\IQueryResult $result) {
			if ($result->length() === 0) {
				return [];
			}

			$result->setFetchType(\IQueryResult::FETCH_ROW);
			$idList = [];

			foreach ($result as $row) {
				$idList[] = (int) array_shift($row);
			}

			return $idList;
		}

		/**
		 * Возвращает подключение к базе данных
		 * @return \IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Возвращает фабрику иерархических связей
		 * @return iFactory
		 */
		private function getFactory() {
			return $this->factory;
		}
	}
