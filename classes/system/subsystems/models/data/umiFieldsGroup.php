<?php

	/** Этот класс реализует объединение полей в именованные группы. */
	class umiFieldsGroup extends umiEntinty implements iUmiFieldsGroup {

		private $name;

		private $title;

		private $type_id;

		private $ord;

		private $is_active = true;

		private $is_visible = true;

		private $is_locked = false;

		/** @var string подсказка группы полей */
		private $tip = '';

		private $autoload_fields = false;

		private $fields = [];

		protected $store_type = 'fields_group';

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		/** @inheritdoc */
		public function getTypeId() {
			return $this->type_id;
		}

		/** @inheritdoc */
		public function getOrd() {
			return $this->ord;
		}

		/** @inheritdoc */
		public function getIsActive() {
			return $this->is_active;
		}

		/** @inheritdoc */
		public function getIsVisible() {
			return $this->is_visible;
		}

		/** @inheritdoc */
		public function getIsLocked() {
			return $this->is_locked;
		}

		/** @inheritdoc */
		public function getTip() {
			return $this->translateLabel($this->tip);
		}

		/** @inheritdoc */
		public function setName($name) {
			if ($this->getName() != $name) {
				$name = umiHierarchy::convertAltName($name, '_');
				$name = umiObjectProperty::filterInputString($name);
				$name = $name !== '' ? $name : '_';
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setTitle($title) {
			if ($this->getTitle() != $title) {
				$title = $this->translateI18n($title, 'fields-group');
				$title = umiObjectProperty::filterInputString($title);
				$this->title = $title;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setTypeId($typeId) {
			$typeId = (int) $typeId;

			if ($this->getTypeId() != $typeId) {
				$this->type_id = $typeId;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function setOrd($ord) {
			$ord = (int) $ord;

			if ($this->getOrd() != $ord) {
				$this->ord = $ord;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setIsActive($isActive) {
			$isActive = (bool) $isActive;

			if ($this->getIsActive() != $isActive) {
				$this->is_active = $isActive;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setIsVisible($isVisible) {
			$isVisible = (bool) $isVisible;

			if ($this->getIsVisible() != $isVisible) {
				$this->is_visible = $isVisible;
				$this->setIsUpdated();
			}
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
		public function setTip($newTip) {
			if ($this->getTip() != $newTip) {
				$tip = $this->translateI18n($newTip, 'fields-group');
				$newTip = umiObjectProperty::filterInputString($tip);
				$this->tip = $newTip;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < 9) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT id, name, title, type_id, is_active, is_visible, is_locked, tip, ord 
FROM cms3_object_field_groups WHERE id = $escapedId LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 9) {
				return false;
			}

			list($id, $name, $title, $typeId, $isActive, $isVisible, $isLocked, $tip, $ord) = $row;

			$this->name = $name;
			$this->title = $title;
			$this->type_id = $typeId;
			$this->is_active = (bool) $isActive;
			$this->is_visible = (bool) $isVisible;
			$this->is_locked = (bool) $isLocked;
			$this->tip = (string) $tip;
			$this->ord = (int) $ord;

			if ($this->autoload_fields) {
				return $this->loadFields();
			}

			return true;
		}

		/** @inheritdoc */
		protected function save() {
			$name = (string) $this->name;
			$title = (string) $this->title;
			$typeId = (int) $this->type_id;
			$isActive = (int) $this->is_active;
			$isVisible = (int) $this->is_visible;
			$ord = (int) $this->ord;
			$isLocked = (int) $this->is_locked;
			$tip = (string) $this->tip;

			$sql = <<<QUERY
UPDATE cms3_object_field_groups
SET    NAME = '{$name}',
       title = '{$title}',
       type_id = '{$typeId}',
       is_active = '{$isActive}',
       is_visible = '{$isVisible}',
       ord = '{$ord}',
       is_locked = '{$isLocked}',
       tip = '{$tip}'
WHERE  id = '{$this->id}'
QUERY;
			ConnectionPool::getInstance()
				->getConnection()
				->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function loadFields($rows = false) {
			$fields = umiFieldsCollection::getInstance();

			if ($rows === false) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$sql = <<<SQL
SELECT cof.id, cof.name, cof.title, cof.is_locked, cof.is_inheritable, cof.is_visible, cof.field_type_id,
       cof.guide_id, cof.in_search, cof.in_filter, cof.tip, cof.is_required, cof.sortable, cof.is_system,
       cof.restriction_id, cof.is_important
FROM cms3_fields_controller cfc, cms3_object_fields cof
WHERE cfc.group_id = '{$this->id}' AND cof.id = cfc.field_id ORDER BY cfc.ord ASC
SQL;
				$result = $connection->queryResult($sql);

				$result->setFetchType(IQueryResult::FETCH_ROW);

				foreach ($result as $row) {
					list($field_id) = $row;
					$field = $fields->getField($field_id, $row);
					if ($field instanceof iUmiField) {
						$this->fields[$field_id] = $field;
					}
				}
			} else {
				foreach ($rows as $row) {
					list($field_id) = $row;
					$field = $fields->getField($field_id, $row);

					if ($field) {
						$this->fields[$field_id] = $field;
					}
				}
			}
		}

		/** @inheritdoc */
		public function getFields() {
			return $this->fields;
		}

		/** @inheritdoc */
		public function hasFields() {
			return count($this->fields) > 0;
		}

		/** @inheritdoc */
		public function hasFieldWithName($name) {
			foreach ($this->getFields() as $field) {
				if ($field->getName() == $name) {
					return true;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function getFieldByName($name) {
			foreach ($this->getFields() as $field) {
				if ($field->getName() == $name) {
					return $field;
				}
			}

			return null;
		}

		private function isLoaded($fieldId) {
			return array_key_exists($fieldId, $this->fields);
		}

		/** @inheritdoc */
		public function attachField($fieldId, $ignoreLoaded = false) {
			if ($this->isLoaded($fieldId) && !$ignoreLoaded) {
				return true;
			}

			$fieldId = (int) $fieldId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT MAX(ord) FROM cms3_fields_controller WHERE group_id = '{$this->id}'";
			$result = $connection->queryResult($sql);

			$result->setFetchType(IQueryResult::FETCH_ROW);
			$ord = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$ord = array_shift($fetchResult);
			}

			$ord += 5;

			$sql = <<<SQL
INSERT INTO cms3_fields_controller (field_id, group_id, ord)
VALUES('{$fieldId}', '{$this->id}', '{$ord}')
SQL;
			$connection->query($sql);

			$fields = umiFieldsCollection::getInstance();
			$field = $fields->getField($fieldId);
			$this->fields[$fieldId] = $field;
			umiTypesHelper::getInstance()->unloadObjectType($this->getTypeId());

			return true;
		}

		/** @inheritdoc */
		public function detachField($id) {
			if (!$this->isLoaded($id)) {
				return false;
			}

			$id = (int) $id;
			$groupId = (int) $this->getId();

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sql = "DELETE FROM `cms3_fields_controller` WHERE `field_id` = $id AND `group_id` = $groupId";
			$connection->query($sql);

			unset($this->fields[$id]);
			umiTypesHelper::getInstance()->unloadObjectType($this->getTypeId());

			$sql = "SELECT `group_id` FROM `cms3_fields_controller` WHERE `field_id` = $id LIMIT 0,1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			return ($result->length() == 0) ? umiFieldsCollection::getInstance()->delField($id) : true;
		}

		/** @inheritdoc */
		public function moveFieldAfter($fieldId, $afterFieldId, $groupId, $isLast) {
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($afterFieldId == 0) {
				$newOrd = 0;
			} else {
				$sql = <<<SQL
SELECT ord FROM cms3_fields_controller
WHERE group_id = '{$groupId}' AND field_id = '{$afterFieldId}'
SQL;
				$result = $connection->queryResult($sql);

				$result->setFetchType(IQueryResult::FETCH_ROW);
				$newOrd = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$newOrd = array_shift($fetchResult);
				}
			}

			if ($isLast) {
				$sql = <<<SQL
UPDATE cms3_fields_controller
SET ord = (ord + 1)
WHERE group_id = '{$this->id}' AND ord >= '{$newOrd}'
SQL;
				$connection->query($sql);
			} else {
				$sql = "SELECT MAX(ord) FROM cms3_fields_controller WHERE group_id = '{$groupId}'";
				$result = $connection->queryResult($sql);

				$result->setFetchType(IQueryResult::FETCH_ROW);
				$newOrd = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$newOrd = array_shift($fetchResult);
				}
				++$newOrd;
			}

			$sql = <<<SQL
UPDATE cms3_fields_controller
SET ord = '{$newOrd}', group_id = '$groupId'
WHERE group_id = '{$this->id}' AND field_id = '{$fieldId}'
SQL;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public static function getAllGroupsByName($name) {
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($name) {
				$name = $connection->escape($name);
			} else {
				return false;
			}

			$sql = "SELECT `id` FROM `cms3_object_field_groups` WHERE `name` = '{$name}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$groups = [];

			foreach ($result as $row) {
				try {
					$group = new umiFieldsGroup(array_shift($row));
				} catch (privateException $e) {
					$e->unregister();
					continue;
				}

				if (!$group instanceof iUmiFieldsGroup) {
					continue;
				}

				$groups[] = $group;
			}

			return $groups;
		}
	}
