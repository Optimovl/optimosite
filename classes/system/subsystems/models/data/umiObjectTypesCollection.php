<?php

	use UmiCms\Service;
	use UmiCms\System\Data\Object\Type\Hierarchy\iRelation;
	use UmiCms\System\Data\Object\Type\Hierarchy\Relation\iRepository;

	/** Коллекция для работы с типами данных (umiObjectType), синглтон. */
	//@todo: вынести работу с группами полей из объектных типов
	class umiObjectTypesCollection extends singleton implements iSingleton, iUmiObjectTypesCollection {

		/** @var iUmiObjectType[] $types список загруженных типов */
		private $types = [];

		/** Конструктор */
		protected function __construct() {
		}

		/**
		 * @inheritdoc
		 * @return iUmiObjectTypesCollection
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function getType($id) {
			if (!$id) {
				return false;
			}

			if (!is_numeric($id)) {
				$id = $this->getTypeIdByGUID($id);
			}

			if ($this->isLoaded($id)) {
				return $this->types[$id];
			}

			$this->loadType($id);
			return getArrayKey($this->types, $id);
		}

		/** @inheritdoc */
		public function getTypeList(array $idList) {
			$idList = array_filter($idList, function ($id) {
				return is_numeric($id);
			});

			if (count($idList) == 0) {
				return [];
			}

			$idList = array_map(function ($id) {
				return (int) $id;
			}, $idList);
			$idList = array_unique($idList);
			$idList = implode(',', $idList);

			$sql = <<<SQL
	SELECT `id`, 
		   `name`, 
		   `parent_id`, 
		   `is_locked`, 
		   `is_guidable`,
		   `is_public`, 
		   `hierarchy_type_id`, 
		   `sortable`,
		   `guid`,
		   `domain_id`
	FROM   `cms3_object_types` 
	WHERE  `id` IN ({$idList})
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			return $this->buildTypeList($result);
		}

		/** @inheritdoc */
		public function getTypeByGUID($guid) {
			$id = $this->getTypeIdByGUID($guid);
			return $this->getType($id);
		}

		/** @inheritdoc */
		public function getTypeIdByFieldId($id) {
			static $cache = [];
			$id = (int) $id;

			if (isset($cache[$id])) {
				return $cache[$id];
			}

			$sql = <<<SQL
SELECT  MIN(fg.type_id)
	FROM cms3_fields_controller fc, cms3_object_field_groups fg
	WHERE fc.field_id = {$id} AND fg.id = fc.group_id
SQL;
			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$objectTypeId = array_shift($fetchResult);
			} else {
				$objectTypeId = false;
			}

			return $cache[$id] = $objectTypeId;
		}

		/** @inheritdoc */
		public function getTypeIdByGUID($guid, $ignoreCache = false) {
			if (!is_string($guid) || empty($guid)) {
				return false;
			}

			foreach ($this->types as $type) {
				if ($type->getGUID() == $guid) {
					return $type->getId();
				}
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$guid = $connection->escape($guid);
			$selectSql = <<<SQL
SELECT `id` FROM `cms3_object_types` WHERE `guid` = '{$guid}' LIMIT 0,1
SQL;
			$result = $connection->queryResult($selectSql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			return (int) array_shift($fetchResult);
		}

		/** @inheritdoc */
		public function addType($parentId, $name, $isLocked = false, $ignoreParentGroups = false) {
			$parentId = (int) $parentId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction(sprintf('Create object type "%s" with parent id = %d', $name, $parentId));

			try {
				$sql = "INSERT INTO cms3_object_types (parent_id) VALUES('{$parentId}')";
				$connection->query($sql);

				$typeId = $connection->insertId();

				if (!$ignoreParentGroups) {
					$sql = "SELECT * FROM cms3_object_field_groups WHERE type_id = '{$parentId}'";
					$result = $connection->queryResult($sql);
					$result->setFetchType(IQueryResult::FETCH_ASSOC);

					foreach ($result as $row) {
						$sql = <<<SQL
INSERT INTO cms3_object_field_groups (name, title, type_id, is_active, is_visible, ord, is_locked)
VALUES (
		'{$connection->escape($row['name'])}',
		'{$connection->escape($row['title'])}',
		'{$typeId}',
		'{$row['is_active']}',
		'{$row['is_visible']}',
		'{$row['ord']}',
		'{$row['is_locked']}'
);
SQL;
						$connection->query($sql);

						$oldGroupId = $row['id'];
						$newGroupId = $connection->insertId();

						$sql = <<<SQL
INSERT INTO cms3_fields_controller
SELECT ord, field_id, '{$newGroupId}' FROM cms3_fields_controller WHERE group_id = '{$oldGroupId}';
SQL;
						$connection->query($sql);
					}
				}

				$parentHierarchyTypeId = false;

				if ($parentId) {
					$parentType = $this->getType($parentId);
					if ($parentType) {
						$parentHierarchyTypeId = $parentType->getHierarchyTypeId();
					}
				}

				$type = new umiObjectType($typeId);
				$type->setName($name);
				$type->setIsLocked($isLocked);

				if ($parentHierarchyTypeId) {
					$type->setHierarchyTypeId($parentHierarchyTypeId);
				}

				$type->commit();
				$this->getHierarchyRelationRepository()
					->createRecursively($type->getParentId(), $type->getId());
			} catch (databaseException $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			$this->types[$typeId] = $type;
			return $typeId;
		}

		/** @inheritdoc */
		public function delType($typeId) {
			$type = $this->getType($typeId);

			if (!$type instanceof iUmiObjectType) {
				return false;
			}

			if ($type->getIsLocked()) {
				throw new publicAdminException(getLabel('error-object-type-locked'));
			}

			$typeIds = $this->getChildTypeIds($typeId);
			$typeIds[] = (int) $typeId;

			foreach ($typeIds as $typeId) {
				$type = $this->getType($typeId);

				if (!$type instanceof iUmiObjectType) {
					continue;
				}

				foreach ($type->getFieldsGroupsList(true) as $group) {
					$type->delFieldsGroup($group->getId());
				}

				$this->unloadType($typeId);
			}

			$typeIds = implode(', ', $typeIds);
			$connection = ConnectionPool::getInstance()->getConnection();

			$sql = "DELETE FROM cms3_objects WHERE type_id IN ({$typeIds})";
			$connection->query($sql);

			$sql = "DELETE FROM cms3_object_types WHERE id IN ({$typeIds})";
			$connection->query($sql);

			$sql = "DELETE FROM cms3_import_types WHERE new_id IN ({$typeIds})";
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function unloadType($id) {
			if ($this->isLoaded($id)) {
				unset($this->types[$id]);
			}

			return $this;
		}

		/**
		 * Проверить, загружен ли тип данных $typeId в коллекцию
		 * @param int $typeId id типа данных
		 * @return bool true, если загружен
		 */
		private function isLoaded($typeId) {
			if (!is_string($typeId) && !is_int($typeId)) {
				return false;
			}

			return array_key_exists($typeId, $this->types);
		}

		/**
		 * Загрузить тип данных в память
		 * @param int $typeId id типа данных
		 * @return bool true, если объект удалось загрузить
		 */
		private function loadType($typeId) {
			if ($this->isLoaded($typeId)) {
				return true;
			}

			try {
				$type = new umiObjectType($typeId);
				$this->types[$typeId] = $type;
				return true;
			} catch (privateException $e) {
				$e->unregister();
				return false;
			}
		}

		/** @inheritdoc */
		public function getSubTypesList($typeId) {
			return $this->getHierarchyRelationRepository()
				->getNearestChildIdList($typeId);
		}

		/** @inheritdoc */
		public function getSubTypeListByDomain($typeId, $domainId) {
			return $this->getHierarchyRelationRepository()
				->getNearestChildIdListWithDomain($typeId, $domainId);
		}

		/** @inheritdoc */
		public function getParentTypeId($typeId) {
			if ($this->isLoaded($typeId)) {
				return $this->getType($typeId)->getParentId();
			}

			return $this->getHierarchyRelationRepository()
				->getNearestAncestorId($typeId);
		}

		/** @inheritdoc */
		public function getChildTypeIds($typeId, $children = false) {
			$childList = $this->getHierarchyRelationRepository()
				->getChildList($typeId);
			return $this->buildChildIdList($childList);
		}

		/** @inheritdoc */
		public function getChildIdListByDomain($typeId, $domainId) {
			$childList = $this->getHierarchyRelationRepository()
				->getChildListWithDomain($typeId, $domainId);
			return $this->buildChildIdList($childList);
		}

		/** @inheritdoc */
		public function getChildIdListByParentIdList(array $idList) {
			$childList = $this->getHierarchyRelationRepository()
				->getChildListByAncestorIdList($idList);
			return $this->buildChildIdList($childList);
		}

		/** @inheritdoc */
		public function getIdListByNameLike($name, $domainId) {
			if (!is_string($name) || isEmptyString($name)) {
				return [];
			}

			$nameCondition = $this->getNameCondition($name);
			$domainIdCondition = $this->getDomainIdCondition($domainId);
			$sql = <<<SQL
SELECT `id`
FROM `cms3_object_types`
WHERE `name` LIKE '%$name%' $nameCondition AND $domainIdCondition
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			return $this->buildTypeIdList($result);
		}

		/** @inheritdoc */
		public function getGuidesList($publicOnly = false, $parentTypeId = null) {
			$publicClause = $publicOnly ? "AND is_public = '1'" : '';
			$sql = "SELECT id, name FROM cms3_object_types WHERE is_guidable = '1' {$publicClause}";
			if ($parentTypeId) {
				$parentTypeId = (int) $parentTypeId;
				$sql .= " AND parent_id = '{$parentTypeId}'";
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$guides = [];
			foreach ($result as $row) {
				list($id, $name) = $row;
				$guides[$id] = $this->translateLabel($name);
			}
			return $guides;
		}

		/** @inheritdoc */
		public function getTypesByHierarchyTypeId($baseTypeId, $ignoreMicroCache = false) {
			static $cache = [];

			$baseTypeId = (int) $baseTypeId;

			if (isset($cache[$baseTypeId]) && !$ignoreMicroCache) {
				return $cache[$baseTypeId];
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT id, name FROM cms3_object_types WHERE hierarchy_type_id = '{$baseTypeId}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$typeIds = [];

			foreach ($result as $row) {
				list($id, $name) = $row;
				$typeIds[$id] = $this->translateLabel($name);
			}

			return $cache[$baseTypeId] = $typeIds;
		}

		/** @inheritdoc */
		public function getListByBaseTypeAndDomain($baseTypeId, $domainId) {
			$baseTypeId = (int) $baseTypeId;
			$domainIdCondition = $this->getDomainIdCondition($domainId);
			$sql = <<<SQL
SELECT `id`, `name`, `guid`, `is_locked`, `parent_id`, `is_guidable`, `is_public`, `hierarchy_type_id`, `sortable`, 
`domain_id`
FROM `cms3_object_types`
WHERE `hierarchy_type_id` = $baseTypeId AND $domainIdCondition
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			return $this->buildTypeList($result);
		}

		/** @inheritdoc */
		public function getTypeIdByHierarchyTypeId($id, $ignoreMicroCache = false) {
			static $cache = [];

			$id = (int) $id;

			if (isset($cache[$id]) && !$ignoreMicroCache && !cmsController::$IGNORE_MICROCACHE) {
				return $cache[$id];
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT id FROM cms3_object_types WHERE hierarchy_type_id = '{$id}' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$typeId = false;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$typeId = array_shift($fetchResult);
			}

			return $cache[$id] = $typeId;
		}

		/** @inheritdoc */
		public function getAllTypes() {
			static $cache = [];

			if (!empty($cache)) {
				return $cache;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT id, name, guid, is_locked, parent_id, is_guidable, is_public, hierarchy_type_id, sortable, domain_id
FROM cms3_object_types;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$types = [];

			foreach ($result as $row) {
				$row['name'] = $this->translateLabel($row['name']);
				$types[$row['id']] = $row;
			}

			return $cache = $types;
		}

		/** @inheritdoc */
		public function getTypeIdByHierarchyTypeName($module, $method = '') {
			$hierarchyType = selector::get('hierarchy-type')->name($module, $method);

			if (!$hierarchyType) {
				return false;
			}

			$hierarchyTypeId = $hierarchyType->getId();
			$typeId = $this->getTypeIdByHierarchyTypeId($hierarchyTypeId);
			return (int) $typeId;
		}

		/** @inheritdoc */
		public function clearCache() {
			$this->types = [];
		}

		/** @inheritdoc */
		public function getHierarchyTypeIdByObjectTypeId($id) {
			static $cache = [];

			$id = (int) $id;

			if (isset($cache[$id])) {
				return $cache[$id];
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT `hierarchy_type_id` as id FROM `cms3_object_types` WHERE `id` = {$id};";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$hierarchyTypeId = false;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$hierarchyTypeId = array_shift($fetchResult);
			}

			return $cache[$id] = $hierarchyTypeId;
		}

		/** @inheritdoc */
		public function getIdList($limit = 15, $offset = 0) {
			$escapedLimit = (int) $limit;
			$escapedOffset = (int) $offset;
			$sql = <<<SQL
SELECT `id`
FROM `cms3_object_types`
LIMIT $escapedOffset, $escapedLimit;
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$idList = [];

			foreach ($result as $row) {
				$idList[] = getFirstValue($row);
			}

			return $idList;
		}

		/**
		 * Возвращает репозиторий иерархических связей объектных типов
		 * @return iRepository
		 */
		private function getHierarchyRelationRepository() {
			return Service::get('ObjectTypeHierarchyRelationRepository');
		}

		/**
		 * Формирует список идентификаторов дочерних типов
		 * @param iRelation[] $relationList список иерархических связей типов
		 * @return int[]
		 */
		private function buildChildIdList(array $relationList) {
			$idList = [];

			foreach ($relationList as $relation) {
				$idList[] = $relation->getChildId();
			}

			return $idList;
		}

		/**
		 * Формирует результат выборки списка идентификаторов типов
		 * @param \IQueryResult $result
		 * @return iRelation[]
		 */
		private function buildTypeIdList(\IQueryResult $result) {
			if ($result->length() === 0) {
				return [];
			}

			$result->setFetchType(\IQueryResult::FETCH_ASSOC);
			$idList = [];

			foreach ($result as $row) {
				$idList[] = array_shift($row);
			}

			return $idList;
		}

		/**
		 * Формирует результат выборки списка типов
		 * @param IQueryResult $result
		 * @return iUmiObjectType[]
		 */
		private function buildTypeList(\IQueryResult $result) {
			if ($result->length() === 0) {
				return [];
			}

			$result->setFetchType(\IQueryResult::FETCH_ROW);
			$typeList = [];

			foreach ($result as $row) {
				try {
					$id = array_shift($row);
					$type = new umiObjectType($id, $row);
					$this->types[$type->getId()] = $type;
					$typeList[] = $type;
				} catch (privateException $exception) {
					$exception->unregister();
					continue;
				}
			}

			return $typeList;
		}

		/**
		 * Возвращает часть sql выражения для фильтрации по названию
		 * @param string $name название
		 * @return string
		 */
		private function getNameCondition($name) {
			$name = ConnectionPool::getInstance()
				->getConnection()
				->escape($name);
			$labelList = ulangStream::getI18n($name, '', true) ?: [];
			$nameConditionFormat = (is_array($labelList) && isEmptyArray($labelList)) ? '' : ' OR `name` %s';
			return sprintf($nameConditionFormat, $this->buildInCondition($labelList));
		}

		/**
		 * Возвращает часть sql выражения для фильтрации по идентификатору домена
		 * @param int $domainId идентификатор домена
		 * @return string
		 */
		private function getDomainIdCondition($domainId) {
			$domainId = (int) $domainId;
			$domainIdFormat = ($domainId > 0) ? '(`domain_id` %s OR `domain_id` IS NULL)' : '`domain_id` %s';
			return sprintf($domainIdFormat, $this->getNullOrIdCondition($domainId));
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
		 * Формирует sql выражение IN ('foo', 'bar', 'baz')
		 * @param array $list список значений
		 * @return string
		 */
		private function buildInCondition(array $list) {
			return "IN ('" . implode("', '", $list) . "')";
		}

		/** @deprecated */
		public function getParentClassId($typeId) {
			return $this->getParentTypeId($typeId);
		}

		/** @deprecated */
		public function getChildClasses($typeId, $children = false) {
			return $this->getChildTypeIds($typeId, $children);
		}

		/** @deprecated */
		public function getTypeByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache = false) {
			return $this->getTypeIdByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache);
		}

		/** @deprecated */
		public function getBaseType($module, $method = '') {
			return $this->getTypeIdByHierarchyTypeName($module, $method);
		}

		/** @deprecated */
		public function isExists($type_id) {
			return true;
		}
	}
