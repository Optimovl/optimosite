<?php

	use UmiCms\Service;

	/**
	 * Этот класс служит для управления/получения доступа к объектам.
	 * Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
	 */
	class umiObjectsCollection extends singleton implements iSingleton, iUmiObjectsCollection {

		/** @var iUmiObject[] список загруженных объектов */
		private $objects = [];

		private $updatedObjects = [];

		/** Конструктор */
		protected function __construct() {
		}

		/**
		 * @inheritdoc
		 * @return iUmiObjectsCollection
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public static function isGuideItemsOrderedById() {
			$registrySort = Service::Registry()->get('//settings/ignore_guides_sort');

			if ($registrySort) {
				return true;
			}

			return (bool) mainConfiguration::getInstance()->get('kernel', 'order-guide-items-by-id');
		}

		/** @inheritdoc */
		public function isLoaded($id) {
			if (!is_int($id) && !is_string($id)) {
				return false;
			}

			return array_key_exists($id, $this->objects);
		}

		/** @inheritdoc */
		public function isExists($id, $typeId = false) {
			if (!is_numeric($id)) {
				return false;
			}

			$isTypeNumeric = is_numeric($typeId);

			if (!$isTypeNumeric && func_num_args() > 1) {
				return false;
			}

			$id = (int) $id;
			$condition = " `id` = $id";

			if ($isTypeNumeric) {
				$typeId = (int) $typeId;
				$condition .= " AND `type_id` = $typeId";
			}

			$query = <<<SQL
SELECT `id` FROM `cms3_objects` WHERE $condition LIMIT 0,1
SQL;
			return ConnectionPool::getInstance()
				->getConnection()
				->queryResult($query)
				->length() === 1;
		}

		/** @inheritdoc */
		public function isUmiObject($object) {
			return ($object instanceof iUmiObject);
		}

		/** @inheritdoc */
		public function getObjectByName($name, $typeId = false) {
			if ($typeId !== false && !is_numeric($typeId)) {
				return false;
			}

			$translatedLabel = ulangStream::getI18n($name);
			$label = ($translatedLabel === null) ? $name : $translatedLabel;
			$dbConnection = ConnectionPool::getInstance()->getConnection();
			$labelAndNameDiff = ($name !== $label);

			$label = $dbConnection->escape($label);
			$name = $dbConnection->escape($name);
			$escapedTypeId = $dbConnection->escape($typeId);

			$namePart = $labelAndNameDiff ? "(`name` = '{$name}' or `name` = '{$label}')" : "`name` = '{$label}'";
			$typePart = $typeId !== false ? "AND `type_id` = '${escapedTypeId}'" : '';
			$query = <<<QUERY
SELECT id
FROM   `cms3_objects`
WHERE  ${namePart}
       ${typePart}
LIMIT  1
QUERY;
			$result = $dbConnection->queryResult($query);

			if ($result instanceof IQueryResult) {
				$result->setFetchType(IQueryResult::FETCH_ASSOC);
				$row = $result->fetch();
				if (isset($row['id']) && is_numeric($row['id'])) {
					return $this->getObject($row['id']);
				}

				return false;
			}

			return false;
		}

		/** @inheritdoc */
		public function getObject($id, $data = false) {
			if (!is_numeric($id) || $id === 0) {
				return false;
			}

			$id = (int) $id;

			if ($this->isLoaded($id)) {
				return $this->objects[$id];
			}

			$factory = Service::DataObjectFactory();

			try {
				if (is_array($data)) {
					array_unshift($data, $id);
					$object = $factory->createByData($data);
				} else {
					$object = $factory->createById($id);
				}

				$this->objects[$id] = $object;
			} catch (privateException $e) {
				$e->unregister();
				return false;
			}

			return $this->objects[$id];
		}

		/** @inheritdoc */
		public function getById($id) {
			return $this->getObject($id);
		}

		/** @inheritdoc */
		public function getListByType($id) {
			if (!is_numeric($id)) {
				return [];
			}

			$id = (int) $id;

			if ($id === 0) {
				return [];
			}

			$sql = <<<SQL
SELECT o.id,
	   o.name,
	   o.type_id,
	   o.is_locked,
	   o.owner_id,
	   o.guid AS `guid`,
	   t.guid AS `type_guid`,
	   o.updatetime,
	   o.ord
FROM   cms3_objects `o`,
	   cms3_object_types `t`
WHERE  o.type_id = $id
	   AND o.type_id = t.id
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			return $this->buildObjectList($result);
		}

		/** @inheritdoc */
		public function getObjectByGUID($guid) {
			$id = $this->getObjectIdByGUID($guid);
			return $this->getObject($id);
		}

		/** @inheritdoc */
		public function getObjectIdByGUID($guid) {
			if (!is_string($guid) || empty($guid)) {
				return false;
			}

			foreach ($this->objects as $object) {
				if ($object->getGUID() == $guid) {
					return $object->getId();
				}
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$guid = $connection->escape($guid);
			$selectSql = <<<SQL
SELECT `id` FROM `cms3_objects` WHERE `guid` = '{$guid}' LIMIT 0,1
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
		public function getObjectList(array $idList) {
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

			$sql = <<<QUERY
	SELECT o.id,
		   o.name,
		   o.type_id,
		   o.is_locked,
		   o.owner_id,
		   o.guid AS `guid`,
		   t.guid AS `type_guid`,
		   o.updatetime,
		   o.ord
	FROM   cms3_objects `o`,
		   cms3_object_types `t`
	WHERE  o.id IN ({$idList})
		   AND o.type_id = t.id
QUERY;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			return $this->buildObjectList($result);
		}

		/** @inheritdoc */
		public function delObject($id) {
			if (!is_numeric($id) || $id === 0) {
				return false;
			}

			$id = (int) $id;
			$systemUsersPermissions = Service::SystemUsersPermissions();

			if (in_array($id, $systemUsersPermissions->getIdList())) {
				throw new coreException("You are not allowed to delete object #{$id}. Never. Don't even try.");
			}

			//Make sure, we don't will not try to commit it later
			$object = $this->getObject($id);

			if (!$object instanceof iUmiObject) {
				return false;
			}

			$object->commit();

			if ($object->getIsLocked()) {
				throw new coreException('Deleting an object is locked.');
			}

			$event = new umiEventPoint('collectionDeleteObject');
			$event->setParam('object_id', $id);
			$event->setMode('before');
			$event->call();

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "DELETE FROM cms3_objects WHERE id = '{$id}'";
			$connection->query($sql);

			$this->unloadObject($id);
			unset($object);

			$event->setMode('after');
			$event->call();

			return true;
		}

		/** @inheritdoc */
		public function addObject($name, $typeId, $isLocked = false) {
			$typeId = (int) $typeId;

			if (!$typeId) {
				throw new coreException("Can't create object without object type id (null given)");
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$connection->startTransaction(sprintf('Create data object with type %d', $typeId));

			try {
				$sql = <<<SQL
INSERT INTO cms3_objects VALUES(NULL, NULL, NULL, NULL, $typeId, NULL, 0, NULL)
SQL;
				$connection->query($sql);

				$object = Service::DataObjectFactory()
					->createById($connection->insertId());
				$object->setName($name);
				$object->setIsLocked($isLocked);
				$object->setOwnerId(
					$this->getOwnerId()
				);

				$maxOrder = $this->getMaxOrderByTypeId(
					$object->getTypeId()
				);

				$object->setOrder($maxOrder + 1);
				$object->commit();
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			$this->objects[$object->getId()] = $object;
			return $object->getId();
		}

		/** @inheritdoc */
		public function cloneObject($id) {
			$id = (int) $id;
			$object = $this->getObject($id);

			if (!$object instanceof iUmiObject) {
				return false;
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$schema = Service::ObjectPropertyValueTableSchema();
			$connection->startTransaction(sprintf('Clone object with id = "%d"', $id));

			try {
				$objectInsertSql = <<<SQL
INSERT INTO `cms3_objects` (`name`, `guid`, `is_locked`, `type_id`, `owner_id`, `ord`, `updatetime`)
SELECT `name`, `guid`, `is_locked`, `type_id`, `owner_id`, `ord`, `updatetime`
FROM `cms3_objects` WHERE `id` = $id
SQL;
				$connection->query($objectInsertSql);
				$copyId = (int) $connection->insertId();

				$defaultPropsTable = $schema->getDefaultTable();
				$defaultPropsInsertSql = <<<SQL
INSERT INTO `$defaultPropsTable` (`obj_id`, `field_id`, `int_val`, `varchar_val`, `text_val`, `rel_val`, `tree_val`, `float_val`)
SELECT $copyId as `obj_id`, `field_id`, `int_val`, `varchar_val`, `text_val`, `rel_val`, `tree_val`, `float_val`
FROM `$defaultPropsTable` WHERE `obj_id` = $id
SQL;
				$connection->query($defaultPropsInsertSql);

				$imagePropsTable = $schema->getImagesTable();
				$imagePropsInsertSql = <<<SQL
INSERT INTO `$imagePropsTable` (`obj_id`, `field_id`, `src`, `alt`, `title`, `ord`)
SELECT $copyId as `obj_id`, `field_id`, `src`, `alt`, `title`, `ord`
FROM `$imagePropsTable` WHERE `obj_id` = $id
SQL;
				$connection->query($imagePropsInsertSql);

				$domainIdPropsTable = $schema->getDomainIdTable();
				$domainIdPropsInsertSql = <<<SQL
INSERT INTO `$domainIdPropsTable` (`obj_id`, `field_id`, `domain_id`)
SELECT $copyId as `obj_id`, `field_id`, `domain_id`
FROM `$domainIdPropsTable` WHERE `obj_id` = $id
SQL;
				$connection->query($domainIdPropsInsertSql);

				$counterPropsTable = $schema->getCounterTable();
				$counterPropsInsertSql = <<<SQL
INSERT INTO `$counterPropsTable` (`obj_id`, `field_id`, `cnt`)
SELECT $copyId as `obj_id`, `field_id`, `cnt`
FROM `$counterPropsTable` WHERE `obj_id` = $id
SQL;
				$connection->query($counterPropsInsertSql);

				$offerIdPropsTable = $schema->getOfferIdTable();
				$offerIdPropsInsertSql = <<<SQL
INSERT INTO `$offerIdPropsTable` (`obj_id`, `field_id`, `offer_id`)
SELECT $copyId as `obj_id`, `field_id`, `offer_id`
FROM `$offerIdPropsTable` WHERE `obj_id` = $id
SQL;
				$connection->query($offerIdPropsInsertSql);

			} catch (databaseException $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			return $copyId;
		}

		/** @inheritdoc */
		public function getGuidedItems($guideId) {
			$connection = ConnectionPool::getInstance()->getConnection();

			if (is_numeric($guideId)) {
				$guideId = (int) $guideId;
			} else {
				$guideId = $connection->escape($guideId);
				$query = "SELECT `id` FROM `cms3_object_types` WHERE `guid`='" . $guideId . "' LIMIT 1";
				$result = $connection->queryResult($query);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$guideId = (int) $guideId;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$guideId = array_shift($fetchResult);
				}
			}

			$sql = "SELECT id, name FROM cms3_objects WHERE type_id = '{$guideId}'";

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($connection->errorOccurred()) {
				throw new coreException($connection->errorDescription($sql));
			}

			$guidedItems = [];

			foreach ($result as $row) {
				list($id, $name) = $row;
				$guidedItems[$id] = $this->translateLabel($name);
			}

			if (!self::isGuideItemsOrderedById()) {
				natsort($guidedItems);
			}

			return $guidedItems;
		}

		/** @inheritdoc */
		public function getCountByTypeId($typeId) {
			$typeId = (int) $typeId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT COUNT(id) FROM cms3_objects WHERE type_id = '{$typeId}'";
			$result = $connection->queryResult($sql);

			if ($connection->errorOccurred()) {
				throw new databaseException($connection->errorDescription($sql));
			}

			list($count) = $result->fetch();

			return $count;
		}

		/** @inheritdoc */
		public function unloadObject($id) {
			if ($this->isLoaded($id)) {
				unset($this->objects[$id]);
				umiObjectProperty::unloadPropData($id);
			} else {
				return false;
			}
		}

		/** @inheritdoc */
		public function unloadAllObjects() {

			foreach ($this->objects as $object_id => $v) {
				unset($this->objects[$object_id]);
				umiObjectProperty::unloadPropData($object_id);
			}
		}

		/** @inheritdoc */
		public function getCollectedObjects() {
			return array_keys($this->objects);
		}

		/** @inheritdoc */
		public function addUpdatedObjectId($id) {
			if (!in_array($id, $this->updatedObjects)) {
				$this->updatedObjects[] = $id;
			}
		}

		/** @inheritdoc */
		public function getUpdatedObjects() {
			return $this->updatedObjects;
		}

		/** @inheritdoc */
		public function getObjectsLastUpdateTime() {
			$maxUpdateTime = 0;

			/** @var iUmiObject $object */
			foreach ($this->objects as $object) {
				if (!$object instanceof iUmiObject) {
					continue;
				}

				if ($maxUpdateTime < $object->getUpdateTime()) {
					$maxUpdateTime = $object->getUpdateTime();
				}
			}

			return $maxUpdateTime;
		}

		/** Деструктор коллекции. Явно вызывать его не нужно никогда. */
		public function __destruct() {
			if (umiCount($this->updatedObjects) && function_exists('deleteObjectsRelatedPages')) {
				deleteObjectsRelatedPages();
			}
		}

		/** @inheritdoc */
		public function clearCache() {
			$keys = array_keys($this->objects);
			foreach ($keys as $key) {
				unset($this->objects[$key]);
			}
			$this->objects = [];
		}

		/** @inheritdoc */
		public function changeOrder(iUmiObject $firstObject, iUmiObject $secondObject, $mode) {
			/* @var iUmiObject $firstObject */
			$firstObjectOrder = $firstObject->getOrder();

			switch ($mode) {
				case 'child':
				case 'after': {
					/* @var iUmiObject $secondObject */
					$secondObject->setOrder($firstObjectOrder + 1);
					$secondObject->commit();

					$this->reBuildOrder($secondObject, 'greed');
					break;
				}
				case 'before': {
					$this->reBuildOrder($firstObject, 'greed');

					/* @var iUmiObject $secondObject */
					$secondObject->setOrder($firstObjectOrder);
					$secondObject->commit();
					break;
				}
				default: {
					return false;
				}
			}

			return true;
		}

		/** @inheritdoc */
		public function reBuildOrder(iUmiObject $firstObject, $mode) {
			/* @var iUmiObject $firstObject */
			$firstObjectOrder = (int) $firstObject->getOrder();
			$objectTypeId = (int) $firstObject->getTypeId();
			$objectTypeId = $this->getOrderTargetTypeId($objectTypeId);

			switch (true) {
				case is_numeric($objectTypeId): {
					$typeCondition = "`type_id` = $objectTypeId";
					break;
				}
				case is_array($objectTypeId): {
					$objectTypeId = implode(', ', $objectTypeId);
					$typeCondition = "`type_id` IN ($objectTypeId)";
					break;
				}
				default: {
					throw new coreException(getLabel('error-wrong-type-id-given'));
				}
			}

			$connection = ConnectionPool::getInstance()->getConnection();

			switch ($mode) {
				case 'greed': {
					$sql = <<<SQL
UPDATE `cms3_objects`
SET `ord` = `ord` + 1
WHERE $typeCondition
AND `ord` >= $firstObjectOrder;
SQL;
					break;
				}
				case 'normal':
				default: {
					$sql = <<<SQL
UPDATE `cms3_objects`
SET `ord` = `ord` + 1
WHERE $typeCondition
AND `ord` > $firstObjectOrder;
SQL;
				}
			}

			$connection->query($sql);
			return true;
		}

		/** @inheritdoc */
		public function getMaxOrderByTypeId($objectTypeId) {
			$objectTypeId = $this->getOrderTargetTypeId($objectTypeId);
			$connection = ConnectionPool::getInstance()->getConnection();

			switch (true) {
				case is_numeric($objectTypeId): {
					$typeCondition = "`type_id` = $objectTypeId";
					break;
				}
				case is_array($objectTypeId): {
					$objectTypeIdList = implode(', ', $objectTypeId);
					$typeCondition = "`type_id` IN ($objectTypeIdList)";
					break;
				}
				default: {
					throw new coreException(getLabel('error-wrong-type-id-given'));
				}
			}

			$sql = <<<SQL
SELECT `ord` FROM `cms3_objects` WHERE $typeCondition ORDER BY `ord` DESC LIMIT 0,1
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$result = $result->getIterator();
			/* @var mysqliQueryResultIterator $result */
			$row = $result->current();
			return (int) $row['ord'];
		}

		/**
		 * Формирует список объектов из результата запроса
		 * @param IQueryResult $result результат запроса
		 * @return iUmiObject[]
		 */
		private function buildObjectList(IQueryResult $result) {
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return [];
			}

			$objectList = [];

			foreach ($result as $row) {
				$id = array_shift($row);
				$object = $this->getObject($id, $row);

				if ($object instanceof iUmiObject) {
					$objectList[] = $object;
				}
			}

			return $objectList;
		}

		/**
		 * Возвращает идентификатор объектного типа данных,
		 * среди объекто которого нужно произвести перестройку
		 * индекса сортировки.
		 * @param int $objectTypeId идентификатор объектного типа данных
		 * @return int|array
		 */
		private function getOrderTargetTypeId($objectTypeId) {
			$umiObjectsTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = (int) $objectTypeId;
			$parentObjectTypeId = $umiObjectsTypes->getParentTypeId($objectTypeId);

			if (!is_numeric($parentObjectTypeId)) {
				return $objectTypeId;
			}

			$objectTypeChildren = $umiObjectsTypes->getChildTypeIds($objectTypeId);
			$parentObjectType = $umiObjectsTypes->getType($parentObjectTypeId);

			switch (true) {
				case !$parentObjectType instanceof iUmiObjectType && umiCount($objectTypeChildren) == 0: {
					return $objectTypeId;
				}
				case !$parentObjectType instanceof iUmiObjectType: {
					$objectTypeChildren[] = $objectTypeId;

					return $objectTypeChildren;
				}
			}

			/* @var iUmiObjectType $parentObjectType */
			if ($parentObjectType->getGUID() == 'root-guides-type') {
				return $objectTypeId;
			}

			return $umiObjectsTypes->getChildTypeIds($parentObjectTypeId);
		}

		/**
		 * Возвращает идентификатор владельца создаваемых объектов
		 * @return int|null
		 */
		private function getOwnerId() {

			try {
				$auth = Service::Auth();
			} catch (Exception $e) {
				return null;
			}

			return $auth->isAuthorized() ? $auth->getUserId() : null;
		}

		/** @deprecated */
		public function checkObjectById($objectId) {
			return true;
		}
	}
