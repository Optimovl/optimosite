<?php

	/** Этот класс служит для управления свойствами типа данных */
	class umiObjectType extends umiEntinty implements iUmiObjectType {

		/** @const int количество свойств типа, необходимое для корректного инстанцирования */
		const INSTANCE_ATTRIBUTE_COUNT = 9;

		private $name, $parent_id, $is_locked = false;

		private $field_groups = [], $field_all_groups = [];

		private $is_guidable = false, $is_public = false, $hierarchy_type_id;

		private $sortable = false;

		private $guid;

		/** @var null|int идентификатор домена или null, если тип общий */
		private $domainId;

		protected $store_type = 'object_type';

		/** @inheritdoc */
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/** @inheritdoc */
		public function setName($name) {
			if ($this->getName() != $name) {
				$name = $this->translateI18n($name, 'object-type-');
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getIsLocked() {
			return $this->is_locked;
		}

		/** @inheritdoc */
		public function setIsLocked($isLocked) {
			$isLocked = (bool) $isLocked;

			if ($this->getIsLocked() != $isLocked) {
				$this->is_locked = $isLocked;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getParentId() {
			return $this->parent_id;
		}

		/** @inheritdoc */
		public function getIsGuidable() {
			return $this->is_guidable;
		}

		/** @inheritdoc */
		public function setIsGuidable($usedAsGuide) {
			$usedAsGuide = (bool) $usedAsGuide;

			if ($this->getIsGuidable() != $usedAsGuide) {
				$this->is_guidable = $usedAsGuide;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getIsPublic() {
			return $this->is_public;
		}

		/** @inheritdoc */
		public function setIsPublic($isPublic) {
			$isPublic = (bool) $isPublic;

			if ($this->getIsPublic() != $isPublic) {
				$this->is_public = $isPublic;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function isPublicGuide() {
			return $this->getIsGuidable() && $this->getIsPublic();
		}

		/** @inheritdoc */
		public function getHierarchyTypeId() {
			return $this->hierarchy_type_id;
		}

		/** @inheritdoc */
		public function getIsSortable() {
			return $this->sortable;
		}

		/** @inheritdoc */
		public function setIsSortable($isSortable = false) {
			$isSortable = (bool) $isSortable;

			if ($this->getIsSortable() != $isSortable) {
				$this->sortable = $isSortable;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setHierarchyTypeId($hierarchyTypeId) {
			$hierarchyTypeId = (int) $hierarchyTypeId;

			if ($this->getHierarchyTypeId() != $hierarchyTypeId) {
				$this->hierarchy_type_id = $hierarchyTypeId;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setDomainId($domainId) {
			$domainId = is_numeric($domainId) ? (int) $domainId : null;

			if ($this->getDomainId() != $domainId) {
				$this->domainId = $domainId;
				$this->setIsUpdated();
			}

			return $this;
		}

		/** @inheritdoc */
		public function getDomainId() {
			return $this->domainId;
		}

		/** @inheritdoc */
		public function addFieldsGroup($name, $title, $isActive = true, $isVisible = true, $tip = '') {
			$group = $this->getFieldsGroupByName($name);
			if ($group) {
				return $group->getId();
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT MAX(ord) FROM cms3_object_field_groups WHERE type_id = '{$this->id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$ord = 1;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$ord = array_shift($fetchResult) + 5;
			}

			$sql = "INSERT INTO cms3_object_field_groups (type_id, ord) VALUES('{$this->id}', '{$ord}')";
			$connection->query($sql);

			$fieldGroupId = $connection->insertId();
			$fieldGroup = new umiFieldsGroup($fieldGroupId);
			$fieldGroup->setName($name);
			$fieldGroup->setTitle($title);
			$fieldGroup->setIsActive($isActive);
			$fieldGroup->setIsVisible($isVisible);
			$fieldGroup->setTip($tip);
			$fieldGroup->commit();

			$this->field_groups[$fieldGroupId] = $fieldGroup;
			$this->field_all_groups[$fieldGroupId] = $fieldGroup;

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$childTypes = $umiObjectTypes->getSubTypesList($this->id);
			$sz = umiCount($childTypes);

			for ($i = 0; $i < $sz; $i++) {
				$childTypeId = $childTypes[$i];
				$type = $umiObjectTypes->getType($childTypeId);

				if ($type) {
					$type->addFieldsGroup($name, $title, $isActive, $isVisible, $tip);
				} else {
					throw new coreException("Can't load object type #{$childTypeId}");
				}

				$umiObjectTypes->unloadType($childTypeId);
			}

			return $fieldGroupId;
		}

		/** @inheritdoc */
		public function delFieldsGroup($groupId) {
			if (!$this->isFieldsGroupExists($groupId)) {
				return false;
			}

			$groupId = (int) $groupId;
			$group = $this->getFieldsGroup($groupId);

			if (!$group instanceof iUmiFieldsGroup) {
				return false;
			}

			$fields = $group->getFields();

			foreach ($fields as $field) {
				$group->detachField($field->getId());
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "DELETE FROM cms3_object_field_groups WHERE id = '{$groupId}'";
			$connection->query($sql);

			unset($this->field_groups[$groupId]);
			return true;
		}

		/** @inheritdoc */
		public function getFieldsGroupByName($fieldGroupName, $allowDisabled = false) {
			$groups = $this->getFieldsGroupsList($allowDisabled);
			foreach ($groups as $groupId => $group) {
				if ($group->getName() == $fieldGroupName) {
					return $group;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function getFieldsGroup($fieldGroupId, $ignoreIsActive = false) {
			if ($this->isFieldsGroupExists($fieldGroupId)) {

				if ($ignoreIsActive) {
					return $this->field_all_groups[$fieldGroupId];
				}

				if (array_key_exists($fieldGroupId, $this->field_groups)) {
					return $this->field_groups[$fieldGroupId];
				}

				return false;
			}

			return false;
		}

		/** @inheritdoc */
		public function getFieldsGroupsList($showDisabledGroups = false) {
			return $showDisabledGroups ? $this->field_all_groups : $this->field_groups;
		}

		/**
		 * Определяет, существует ли у типа данных группа полей с указанным id
		 * @param int $fieldGroupId id группы полей
		 * @return bool true, если группа полей существует у этого типа данных
		 */
		private function isFieldsGroupExists($fieldGroupId) {
			if (!$fieldGroupId) {
				return false;
			}

			return array_key_exists($fieldGroupId, $this->field_all_groups);
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < self::INSTANCE_ATTRIBUTE_COUNT) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT name, parent_id, is_locked, is_guidable, is_public, hierarchy_type_id, sortable, guid, domain_id
	FROM cms3_object_types WHERE id = $escapedId LIMIT 0,1
SQL;

				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < self::INSTANCE_ATTRIBUTE_COUNT) {
				return false;
			}

			list(
				$name,
				$parentId,
				$isLocked,
				$isGuidable,
				$isPublic,
				$baseTypeId,
				$sortable,
				$guid,
				$domainId
				) = $row;

			$this->name = $name;
			$this->parent_id = (int) $parentId;
			$this->is_locked = (bool) $isLocked;
			$this->is_guidable = (bool) $isGuidable;
			$this->is_public = (bool) $isPublic;
			$this->hierarchy_type_id = (int) $baseTypeId;
			$this->sortable = (bool) $sortable;
			$this->guid = $guid;
			$this->domainId = is_numeric($domainId) ? (int) $domainId : null;

			return $this->loadFieldsGroups();
		}

		/**
		 * Загружает группы полей и поля для типа данных из БД
		 * @return bool true, если не возникло ошибок
		 */
		private function loadFieldsGroups() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT ofg.id as groupId, cof.id, cof.name, cof.title, cof.is_locked,
       cof.is_inheritable, cof.is_visible, cof.field_type_id, cof.guide_id,
       cof.in_search, cof.in_filter, cof.tip, cof.is_required, cof.sortable,
       cof.is_system, cof.restriction_id, cof.is_important
FROM cms3_object_field_groups ofg, cms3_fields_controller cfc, cms3_object_fields cof
WHERE ofg.type_id = '{$this->id}' AND cfc.group_id = ofg.id AND cof.id = cfc.field_id
ORDER BY ofg.ord ASC, cfc.ord ASC
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$fields = [];

			foreach ($result as $row) {
				list(
					$groupId,
					$id,
					$name,
					$title,
					$isLocked,
					$isInheritable,
					$isVisible,
					$fieldTypeId,
					$guideId,
					$inSearch,
					$inFilter,
					$tip,
					$isRequired,
					$isSystem,
					$sortable,
					$restrictionId,
					$isImportant
					) = $row;

				if (!isset($fields[$groupId]) || !is_array($fields[$groupId])) {
					$fields[$groupId] = [];
				}

				$fields[$groupId][] = [
					$id,
					$name,
					$title,
					$isLocked,
					$isInheritable,
					$isVisible,
					$fieldTypeId,
					$guideId,
					$inSearch,
					$inFilter,
					$tip,
					$isRequired,
					$isSystem,
					$sortable,
					$restrictionId,
					$isImportant
				];
			}

			$sql = <<<SQL
SELECT id, name, title, type_id, is_active, is_visible, is_locked, tip, ord
FROM cms3_object_field_groups
WHERE type_id = '{$this->id}'
ORDER BY ord ASC
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$fieldGroupId = $row[0];
				$isActive = $row[4];

				try {
					$fieldGroup = new umiFieldsGroup($fieldGroupId, $row);
				} catch (privateException $exception) {
					$exception->unregister();
					continue;
				}

				if (!isset($fields[$fieldGroupId])) {
					$fields[$fieldGroupId] = [];
				}

				$fieldGroup->loadFields($fields[$fieldGroupId]);
				$this->field_all_groups[$fieldGroupId] = $fieldGroup;

				if ($isActive) {
					$this->field_groups[$fieldGroupId] = $fieldGroup;
				}
			}

			return true;
		}

		/** @inheritdoc */
		protected function save() {
			$name = umiObjectProperty::filterInputString($this->name);
			$guid = umiObjectProperty::filterInputString($this->guid);
			$parentId = (int) $this->parent_id;
			$isLocked = (int) $this->is_locked;
			$isGuidable = (int) $this->is_guidable;
			$isPublic = (int) $this->is_public;
			$baseTypeId = (int) $this->hierarchy_type_id;
			$sortable = (int) $this->sortable;
			$domainId = $this->getDomainId() ?: 'NULL';
			$id = (int) $this->getId();

			$sql = <<<SQL
UPDATE `cms3_object_types`
SET `name` = '$name', `guid` = '$guid', `parent_id` = $parentId,
    `is_locked` = $isLocked, `is_guidable` = $isGuidable, 
	  `is_public` = $isPublic, `hierarchy_type_id` = $baseTypeId,
	  `sortable` = $sortable, `domain_id` = $domainId
WHERE `id` = $id
SQL;
			ConnectionPool::getInstance()
				->getConnection()
				->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function setFieldGroupOrd($groupId, $newOrd, $isLast) {
			$newOrd = (int) $newOrd;
			$groupId = (int) $groupId;
			$connection = ConnectionPool::getInstance()->getConnection();

			if (!$isLast) {
				$sql = "SELECT type_id FROM cms3_object_field_groups WHERE id = '{$groupId}'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				if ($connection->errorOccurred()) {
					throw new coreException($connection->errorDescription($sql));
				}

				if ($result->length() == 0) {
					return false;
				}

				$fetchResult = $result->fetch();
				$type_id = (int) array_shift($fetchResult);
				$sql = <<<SQL
UPDATE cms3_object_field_groups
SET ord = (ord + 1)
WHERE type_id = '{$type_id}' AND ord >= '{$newOrd}'
SQL;
				$connection->query($sql);

				if ($connection->errorOccurred()) {
					throw new coreException($connection->errorDescription($sql));
				}
			}

			$sql = "UPDATE cms3_object_field_groups SET ord = '{$newOrd}' WHERE id = '{$groupId}'";
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function getAllFields($returnOnlyVisibleFields = false) {
			$fields = [];
			$groups = $this->getFieldsGroupsList();

			foreach ($groups as $group) {
				if ($returnOnlyVisibleFields && !$group->getIsVisible()) {
					continue;
				}

				foreach ($group->getFields() as $groupField) {
					$fields[] = $groupField;
				}
			}

			return $fields;
		}

		/** @inheritdoc */
		public function getFieldId($fieldName, $ignoreInactiveGroups = true) {
			$groups = $this->getFieldsGroupsList(!$ignoreInactiveGroups);
			foreach ($groups as $groupId => $group) {
				$fields = $group->getFields();
				foreach ($fields as $fieldId => $field) {
					if ($field->getName() == $fieldName) {
						return $field->getId();
					}
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function getModule() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if ($hierarchyType instanceof iUmiHierarchyType) {
				return $hierarchyType->getName();
			}

			return false;
		}

		/** @inheritdoc */
		public function getMethod() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if ($hierarchyType instanceof iUmiHierarchyType) {
				return $hierarchyType->getExt();
			}

			return false;
		}

		/** @inheritdoc */
		public function getGUID() {
			return $this->guid;
		}

		/** @inheritdoc */
		public function setGUID($guid) {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();

			$id = $umiObjectTypes->getTypeIdByGUID($guid, true);

			if ($id && $id != $this->id) {
				throw new coreException("GUID {$guid} already in use");
			}

			if ($this->getGUID() != $guid) {
				$this->guid = $guid;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getFieldListFromGroup($name) {
			$group = $this->getFieldsGroupByName($name);

			if (!$group instanceof iUmiFieldsGroup) {
				throw new ExpectFieldGroupException('Cannot get field group');
			}

			return $group->getFields();
		}
	}
