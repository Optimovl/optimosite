<?php

	use UmiCms\Service;

	/** Общий класс для взаимодействия с объектами системы. */
	class umiObject extends umiEntinty implements iUmiObject {

		/** @const int количество свойств объекта, необходимое для корректного инстанцирования */
		const INSTANCE_ATTRIBUTE_COUNT = 8;

		/** @inheritdoc */
		protected $store_type = 'object';

		/** @var string $name название */
		private $name;

		/** @var int $type_id идентификатор типа данных */
		private $type_id;

		/** @var bool $is_locked статус блокировки удаления объекта */
		private $is_locked;

		/** @var int|bool|null $owner_id идентификатор пользователя, создавшего данный объект */
		private $owner_id = false;

		/** @var string|null $guid строковой идентификатор */
		private $guid;

		/** @var int|null $updateTime время последнего обновления объекта */
		private $updateTime;

		/** @var int|null $ord порядок вывода данного объекта с списке однотипных объектов */
		private $ord;

		/** @var string|null $type_guid строковой идентификатор типа данных */
		private $type_guid;

		/**
		 * @var iUmiObjectProperty[] $properties список полей
		 *
		 * [
		 *       iUmiObjectProperty->getId() => iUmiObjectProperty
		 * ]
		 */
		private $properties = [];

		/**
		 * @var array $invertedProperties список названий полей
		 *
		 * [
		 *       iUmiObjectProperty->getName() => iUmiObjectProperty->getId()
		 * ]
		 */
		private $invertedProperties = [];

		/**
		 * @var array $prop_groups список групп полей
		 *
		 * [
		 *      iUmiFieldsGroup->getId() => [
		 *          iUmiField->getId()
		 *      ]
		 * ]
		 */
		private $prop_groups = [];

		/** @var bool $propertiesLoaded флаг того, что поля были загружены */
		private $propertiesLoaded;

		/** @inheritdoc */
		public function __construct($id, $row = false, $instantLoad = true, $savingInDestructor = true) {
			parent::__construct($id, $row, $instantLoad, $savingInDestructor);
			umiObjectProperty::unloadPropData($this->id);
		}

		/** @inheritdoc */
		public function getName($ignoreTranslation = false) {
			$name = $this->name;

			if ($name !== null) {
				$name = Service::EmojiTranslator()
					->fromShortNameToUnicode($name);
			}

			return $ignoreTranslation ? $name : $this->translateLabel($name);
		}

		/** @inheritdoc */
		public function setName($name) {

			if ($name !== null) {
				$name = Service::EmojiTranslator()
					->fromUnicodeToShortName($name);
			}

			if ($this->getName() != $name) {
				$name = preg_replace('/([\x01-\x08]|[\x0B-\x0C]|[\x0E-\x1F])/', '', $name);
				$name = $this->translateI18n($name, 'object-');
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setTypeId($typeId) {
			if ($this->getTypeId() !== $typeId) {
				$this->type_id = $typeId;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function getTypeId() {
			return $this->type_id;
		}

		/** @inheritdoc */
		public function getIsLocked() {
			return $this->is_locked;
		}

		/** @inheritdoc */
		public function setIsLocked($isLocked) {
			$isLocked = (bool) $isLocked;

			if ($this->getIsLocked() !== $isLocked) {
				$this->is_locked = $isLocked;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getOwnerId() {
			return $this->owner_id;
		}

		/** @inheritdoc */
		public function setOwnerId($ownerId) {
			if (!Service::ObjectsCollection()->isExists($ownerId)) {
				return false;
			}

			if ($this->getOwnerId() !== $ownerId) {
				$this->owner_id = $ownerId;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function getGUID() {
			return $this->guid;
		}

		/** @inheritdoc */
		public function setGUID($guid) {
			$id = Service::ObjectsCollection()
				->getObjectIdByGUID($guid);

			if ($id && $id != $this->getId()) {
				throw new coreException("GUID {$guid} already in use");
			}

			if ($this->getGUID() != $guid) {
				$this->guid = $guid;
				$this->setIsUpdated();
			}

			return $this;
		}

		/** @inheritdoc */
		public function getUpdateTime() {
			return $this->updateTime;
		}

		/** @inheritdoc */
		public function setUpdateTime($updateTime) {
			$updateTimeStamp = (int) $updateTime;

			if ($this->getUpdateTime() !== $updateTimeStamp) {
				$this->updateTime = $updateTimeStamp;
				parent::setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function getOrder() {
			return $this->ord;
		}

		/** @inheritdoc */
		public function setOrder($order) {
			$order = (int) $order;

			if ($this->getOrder() !== $order) {
				$this->ord = $order;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function setIsUpdated($isUpdated = true) {
			parent::setIsUpdated($isUpdated);

			if ($isUpdated) {
				$this->updateTime = time();
			}

			Service::ObjectsCollection()
				->addUpdatedObjectId($this->getId());
		}

		/** @inheritdoc */
		public function getTypeGUID() {
			return $this->type_guid;
		}

		/** @inheritdoc */
		public function getXlink() {
			return 'uobject://' . $this->id;
		}

		/** @inheritdoc */
		public function getType() {
			$type = Service::ObjectsTypesCollection()
				->getType($this->type_id);

			if (!$type instanceof iUmiObjectType) {
				throw new coreException(sprintf('Cannot load iUmiObjectType by id "%s"', $this->type_id));
			}

			return $type;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function isFilled() {
			$fields = $this->getType()->getAllFields();

			/** @var iUmiField[] $fields */
			foreach ($fields as $field) {
				if ($field->getIsRequired() && $this->getValue($field->getName()) === null) {
					return false;
				}
			}

			return true;
		}

		/** @inheritdoc */
		public function loadFields() {
			$typeId = $this->getTypeId();
			$objectTypeFieldList = Service::TypesHelper()
				->getFieldsByObjectTypeIds($typeId);

			if (isset($objectTypeFieldList[$typeId])) {
				$fieldList = $objectTypeFieldList[$typeId];
				$invertedProperties = $fieldList;
				$properties = array_flip($invertedProperties);

				if (count($fieldList) > 1) {
					umiFieldsCollection::getInstance()
						->getFieldList($fieldList);
				}
			} else {
				$invertedProperties = $properties = [];
			}

			$this->setInvertedProperties($invertedProperties)
				->setProperties($properties);

			return true;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getPropByName($name) {
			$name = mb_strtolower($name);

			if (!$this->isPropertyNameExist($name)) {
				return null;
			}

			$this->loadPropertiesIfNotLoaded();
			$propertyId = (int) $this->invertedProperties[$name];
			return $this->getPropById($propertyId);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getPropById($id) {
			if (!$this->isPropertyExists($id)) {
				return null;
			}

			$this->loadPropertiesIfNotLoaded();

			if (!$this->properties[$id] instanceof iUmiObjectProperty) {
				$this->properties[$id] = Service::ObjectPropertyFactory()
					->create($this->getId(), $id);
			}

			return $this->properties[$id];
		}

		/** @inheritdoc */
		public function isPropertyExists($id) {
			if (!is_string($id) && !is_int($id)) {
				return false;
			}

			$this->loadPropertiesIfNotLoaded();

			return isset($this->properties[$id]);
		}

		/** @inheritdoc */
		public function isPropertyNameExist($name) {
			if (!is_string($name) && !is_int($name)) {
				return false;
			}

			$this->loadPropertiesIfNotLoaded();

			return isset($this->invertedProperties[$name]);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function isPropGroupExists($id) {
			if (umiCount($this->prop_groups) == 0) {
				$this->loadGroups();
			}

			if (!is_string($id) && !is_int($id)) {
				return false;
			}

			return isset($this->prop_groups[$id]);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getPropGroupId($name) {
			$groupList = $this->getType()
				->getFieldsGroupsList();

			foreach ($groupList as $group) {
				if ($group->getName() == $name) {
					return $group->getId();
				}
			}

			return false;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getPropGroupByName($name) {
			$id = $this->getPropGroupId($name);
			if ($id) {
				return $this->getPropGroupById($id);
			}

			return false;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getPropGroupById($id) {

			if ($this->isPropGroupExists($id)) {
				return $this->prop_groups[$id];
			}

			$group = $this->getType()
				->getFieldsGroup($id);

			if (!$group instanceof iUmiFieldsGroup) {
				return false;
			}

			$groupFieldList = $group->getFields();
			$fieldIdList = [];

			foreach ($groupFieldList as $field) {
				if (!$field instanceof iUmiField) {
					continue;
				}

				$fieldIdList[] = $field->getId();
			}

			return $this->prop_groups[$id] = $fieldIdList;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getPropList($onlyVisible = false) {
			return $this->getType()->getAllFields($onlyVisible);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getValue($name, $params = null) {
			$property = $this->getPropByName($name);

			if ($property instanceof iUmiObjectProperty) {
				return $property->getValue($params);
			}

			return false;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getValueById($fieldId, $params = null) {
			$property = $this->getPropById($fieldId);

			if (!$property instanceof iUmiObjectProperty) {
				return false;
			}

			return $property->getValue($params);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function setValue($name, $value) {
			$property = $this->getPropByName($name);

			if ($property instanceof iUmiObjectProperty) {

				$property->setValue($value);

				if ($property->getIsUpdated()) {
					$this->setIsUpdated();
				}

				return true;
			}

			return false;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function delete() {
			Service::ObjectsCollection()
				->delObject($this->getId());
		}

		/**
		 * Возвращает значение свойства или поля
		 * @param string $name имя свойства или поля
		 * @return mixed
		 * @throws coreException
		 */
		public function __get($name) {
			switch ($name) {
				case 'id':
					return $this->id;
				case 'name':
					return $this->getName();
				case 'ownerId':
					return $this->getOwnerId();
				case 'typeId':
					return $this->getTypeId();
				case 'GUID':
					return $this->getGUID();
				case 'typeGUID':
					return $this->getTypeGUID();
				case 'xlink':
					return $this->getXlink();
				default:
					return $this->getValue($name);
			}
		}

		/**
		 * Определяет наличие свойства или поля
		 * @param string $name имя свойства или поля
		 * @return bool
		 * @throws coreException
		 */
		public function __isset($name) {
			switch ($name) {
				case 'id':
				case 'name':
				case 'ownerId':
				case 'typeId':
				case 'GUID':
				case 'typeGUID':
				case 'xlink': {
					return true;
				}
				default : {
					return ($this->getPropByName($name) instanceof iUmiObjectProperty);
				}
			}
		}

		/**
		 * Устанавливает значение свойства или поля
		 * @param string $name имя свойства или поля
		 * @param mixed $value значение
		 * @return mixed
		 * @throws coreException
		 */
		public function __set($name, $value) {
			switch ($name) {
				case 'id':
					throw new coreException('Object id could not be changed');
				case 'name':
					return $this->setName($value);
				case 'ownerId':
					return $this->setOwnerId($value);
				default:
					return $this->setValue($name, $value);
			}
		}

		/** @inheritdoc */
		public function getModule() {
			$hierarchyTypeId = Service::ObjectsTypesCollection()
				->getHierarchyTypeIdByObjectTypeId($this->getTypeId());

			$hierarchyType = Service::HierarchyTypesCollection()
				->getType($hierarchyTypeId);

			if ($hierarchyType instanceof iUmiHierarchyType) {
				return $hierarchyType->getName();
			}

			return false;
		}

		/** @inheritdoc */
		public function getMethod() {
			$hierarchyTypeId = Service::ObjectsTypesCollection()
				->getHierarchyTypeIdByObjectTypeId($this->getTypeId());

			$hierarchyType = Service::HierarchyTypesCollection()
				->getType($hierarchyTypeId);

			if ($hierarchyType instanceof iUmiHierarchyType) {
				return $hierarchyType->getExt();
			}

			return false;
		}

		/** @inheritdoc */
		public function update() {
			parent::update();
			$this->setPropertiesNotLoaded();
			umiObjectProperty::unloadPropData($this->id);
		}

		/**
		 * Сохраняет изменения объекта и его свойств
		 * @return bool
		 * @throws Exception
		 */
		protected function save() {
			$ignoreI18n = true;
			$name = umiObjectProperty::filterInputString($this->getName($ignoreI18n));
			$name = $name ? "'$name'" : 'NULL';
			$typeId = (int) $this->getTypeId();
			$isLocked = (int) $this->getIsLocked();
			$ownerId = (int) $this->getOwnerId();
			$guid = umiObjectProperty::filterInputString($this->getGUID());
			$updateTime = $this->getUpdateTime() === null ? 'NULL' : $this->getUpdateTime();
			$ord = (int) $this->getOrder();
			$objectId = (int) $this->getId();

			$connection = Service::ConnectionPool()
				->getConnection();
			$connection->startTransaction("Saving object #$objectId");

			$sql = <<<QUERY
	UPDATE `cms3_objects`
	SET `name` = $name, `type_id` = $typeId, `is_locked` = $isLocked, `owner_id` = $ownerId, `guid` = '$guid',
	`updatetime` = $updateTime, `ord` = $ord
	WHERE `id` = $objectId
QUERY;
			try {
				$connection->query($sql);

				$transactionModeEnabled = umiObjectProperty::isTransactionModeEnabled();

				if ($transactionModeEnabled) {
					umiObjectProperty::disableTransactionMode();
				}

				foreach ($this->getProperties() as $prop) {
					if ($prop instanceof iUmiObjectProperty && $prop->getIsUpdated()) {
						$prop->commit();
					}
				}

				if ($transactionModeEnabled) {
					umiObjectProperty::enableTransactionMode();
				}
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();

			return true;
		}

		/**
		 * Инициализирует объект переданными данными или данными из бд
		 * @param array|bool $data полный набор свойств объекта или false
		 *
		 * [
		 *      0 => 'name',
		 *      1 => 'type_id',
		 *      2 => 'is_locked',
		 *      3 => 'owner_id',
		 *      4 => 'guid',
		 *      5 => 'type_guid',
		 *      6 => 'updateTime',
		 *      7 => 'ord'
		 * ]
		 *
		 * @return bool
		 * @throws databaseException
		 */
		protected function loadInfo($data = false) {
			if (!is_array($data) || count($data) !== self::INSTANCE_ATTRIBUTE_COUNT) {
				$connection = Service::ConnectionPool()
					->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<QUERY
	SELECT o.name,
		   o.type_id,
		   o.is_locked,
		   o.owner_id,
		   o.guid AS `guid`,
		   t.guid AS `type_guid`,
		   o.updatetime,
		   o.ord
	FROM   cms3_objects `o`,
		   cms3_object_types `t`
	WHERE  o.id = $escapedId
		   AND o.type_id = t.id
	LIMIT 0,1
QUERY;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$data = $result->fetch();
			}

			if (!is_array($data) || count($data) !== self::INSTANCE_ATTRIBUTE_COUNT) {
				return false;
			}

			list($name, $typeId, $isLocked, $ownerId, $guid, $typeGuid, $updateTime, $ord) = $data;
			$this->name = $name;
			$this->type_id = (int) $typeId;
			$this->is_locked = (bool) $isLocked;
			$this->owner_id = (int) $ownerId;
			$this->guid = $guid;
			$this->type_guid = $typeGuid;
			$this->updateTime = $updateTime;
			$this->ord = (int) $ord;
			return true;
		}

		/**
		 * Загружает идентификаторы групп и их полей
		 * @throws coreException
		 */
		private function loadGroups() {
			$groups = $this->getType()
				->getFieldsGroupsList();

			foreach ($groups as $group) {
				if (!$group instanceof iUmiFieldsGroup || !$group->getIsActive()) {
					continue;
				}

				$fields = $group->getFields();

				$this->prop_groups[$group->getId()] = [];

				foreach ($fields as $field) {
					$this->prop_groups[$group->getId()][] = $field->getId();
				}
			}
		}

		/**
		 * Возвращает загруженные поля объекта
		 * @return iUmiObjectProperty[]
		 */
		private function getProperties() {
			$this->loadPropertiesIfNotLoaded();
			return $this->properties;
		}

		/**
		 * Инициирует загрузку полей, если они еще не были загружены
		 * @return bool
		 */
		private function loadPropertiesIfNotLoaded() {
			if ($this->isPropertiesLoaded()) {
				return false;
			}

			$this->loadFields();
			$this->setPropertiesLoaded();
			return true;
		}

		/**
		 * Определяет, что поля уже были загружены
		 * @return bool
		 */
		private function isPropertiesLoaded() {
			return $this->propertiesLoaded;
		}

		/**
		 * Устанавливает, что поля уже были загружены
		 * @return $this
		 */
		private function setPropertiesLoaded() {
			$this->propertiesLoaded = true;
			return $this;
		}

		/**
		 * Устанавливает, что поля не были загружены
		 * @return $this
		 */
		private function setPropertiesNotLoaded() {
			$this->propertiesLoaded = false;
			return $this;
		}

		/**
		 * Устанавливает список полей
		 * @param string[] $properties
		 *
		 * [
		 *       iUmiObjectProperty->getId() => iUmiObjectProperty->getName()
		 * ]
		 *
		 * @return $this
		 */
		private function setProperties(array $properties) {
			$this->properties = $properties;
			return $this;
		}

		/**
		 * Устанавливает список названий полей
		 * @param string[] $invertedProperties
		 *
		 * [
		 *       iUmiObjectProperty->getName() => iUmiObjectProperty->getId()
		 * ]
		 *
		 * @return $this
		 */
		private function setInvertedProperties(array $invertedProperties) {
			$this->invertedProperties = $invertedProperties;
			return $this;
		}

		/** @deprecated */
		public function checkSelf() {
			return null;
		}
	}
