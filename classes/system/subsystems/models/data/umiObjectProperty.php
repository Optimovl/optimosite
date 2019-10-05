<?php

	use UmiCms\Service;
	use UmiCms\System\Data\Object\Property\Value\Table\iSchema;

	/** Абстрактный класс значения поля объекта */
	abstract class umiObjectProperty extends umiEntinty implements iUmiObjectProperty {

		/** @var string $store_type тип кешируемой сущности */
		public $store_type = 'property';

		/** todo: delete it */
		public static $USE_FORCE_OBJECTS_CREATION = false;

		/** todo: delete it */
		public static $IGNORE_FILTER_INPUT_STRING = false;

		/** todo: delete it */
		public static $IGNORE_FILTER_OUTPUT_STRING = false;

		/** todo: delete it */
		public static $USE_TRANSACTIONS = true;

		/** todo: delete it */
		public static $IGNORE_CACHE = false;

		/** @var int $object_id идентификатор объекта */
		protected $object_id;

		/** @var int $field_id идентификатор поля */
		protected $field_id;

		/** @var null|mixed $value значение */
		protected $value;

		/**
		 * todo: move it from here
		 * @var array $dataCache кеш значений
		 */
		protected static $dataCache = [];

		/**
		 * todo: move it from here
		 * @var array $dataCacheOrder порядок попадания в кеш значений полей объектов
		 */
		protected static $dataCacheOrder = [];

		/** @var int DEFAULT_OBJECT_PROPS_CACHE_LIMIT размер кеша значений по умолчанию */
		const DEFAULT_OBJECT_PROPS_CACHE_LIMIT = 200;

		/**
		 * Конструктор класса
		 * @param int $objectId идентификатор объекта (umiObject), с которым связано значение
		 * @param int $fieldId идентификатор поля (umiField), с которым связано значение
		 * @throws Exception
		 */
		public function __construct($id, $row = false) {
			$args = func_get_args();

			$objectId = array_shift($args);

			if (!is_numeric($objectId)) {
				throw new Exception('Object id expected for creating property');
			}

			$fieldId = array_shift($args);

			if (!is_numeric($fieldId)) {
				throw new Exception('Field id expected for creating property');
			}

			$this->object_id = (int) $objectId;
			$this->field_id = (int) $fieldId;
			$this->setId($objectId);
		}

		/** @inheritdoc */
		public function getId() {
			return $this->id . '.' . $this->field_id;
		}

		/** @inheritdoc */
		public function getValue(array $params = null) {
			if ($this->value === null) {
				$this->value = $this->loadValue();
			}

			if ($this->getIsMultiple()) {
				$value = $this->value;
			} else {
				if (umiCount($this->value) > 0) {
					list($value) = $this->value;
				} else {
					$value = null;
				}
			}

			if ($params !== null) {
				$value = $this->applyParams($value, $params);
			}

			$field = $this->getField();
			$restrictionId = $field->getRestrictionId();

			if ($restrictionId) {
				$restriction = baseRestriction::get($restrictionId);

				if ($restriction instanceof iNormalizeOutRestriction) {
					$value = $restriction->normalizeOut($value, $this->object_id);
				}
			}

			return $value;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->getField()->getName();
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->getField()->getTitle();
		}

		/** @inheritdoc */
		public function setValue($value) {
			if ($this->value === null) {
				$this->value = $this->loadValue();
			}

			$value = $this->validateValue($value);

			if (!is_array($value)) {
				$value = [$value];
			}

			foreach ($value as &$v) {
				if (is_string($v)) {
					$v = preg_replace('/([\x01-\x08]|[\x0B-\x0C]|[\x0E-\x1F])/', '', $v);
				}
			}

			$data_type = $this->getDataType();

			if ($data_type === 'date') {
				foreach ($value as $vKey => $vVal) {
					if (!($vVal instanceof umiDate)) {
						$value[$vKey] = new umiDate((int) $vVal);
					}
				}
			}

			$valueWillBeChange = $this->isNeedToSave($value);

			if ($valueWillBeChange) {
				$this->value = $value;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function resetValue() {
			$this->value = [];
			$this->setIsUpdated();
		}

		/** Выключает использование транзакций */
		public static function disableTransactionMode() {
			self::$USE_TRANSACTIONS = false;
		}

		/** Включает использование транзакций */
		public static function enableTransactionMode() {
			self::$USE_TRANSACTIONS = true;
		}

		/**
		 * Проверяет включен ли режим транзакций
		 * @return bool
		 */
		public static function isTransactionModeEnabled() {
			return (bool) self::$USE_TRANSACTIONS;
		}

		/** @inheritdoc */
		public function getIsMultiple() {
			return $this->getFieldType()->getIsMultiple();
		}

		/** @inheritdoc */
		public function getIsUnsigned() {
			return $this->getFieldType()->getIsUnsigned();
		}

		/** @inheritdoc */
		public function getDataType() {
			return $this->getFieldType()->getDataType();
		}

		/** @inheritdoc */
		public function getIsLocked() {
			return $this->getField()->getIsLocked();
		}

		/** @inheritdoc */
		public function getIsInheritable() {
			return $this->getField()->getIsInheritable();
		}

		/** @inheritdoc */
		public function getIsVisible() {
			return $this->getField()->getIsVisible();
		}

		/** @inheritdoc */
		public static function filterInputString($string) {
			$string = parent::filterInputString($string);
			$isAdminMode = Service::Request()->isAdmin();
			$isFilterIgnored = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;

			if (isset($_SERVER['DOCUMENT_ROOT']) && !$isAdminMode && !$isFilterIgnored && !isCronMode()) {
				$string = str_replace(['&#037;', '&#37;'], '%', $string);
				$string = htmlspecialchars(htmlspecialchars_decode($string), ENT_NOQUOTES);
				$string = preg_replace('/([^\\\])\"/', '$1\"', $string);
				$string = str_replace('%', '&#37;', $string);
			}

			return $string;
		}

		/** @inheritdoc */
		public function refresh() {
			$this->value = null;
			$this->loadValue();
			return $this;
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			//nothing
		}

		/**
		 * Возвращает название таблицы, в которой хранится значение свойства
		 * @return string
		 */
		protected function getTableName() {
			return Service::ObjectPropertyValueTableSchema()
				->getTable($this);
		}

		/** @inheritdoc */
		protected function save() {
			if ($this->value === null) {
				return false;
			}

			$connection = $this->getConnection();
			$transactionModeEnabled = $this->isTransactionModeEnabled();
			$objectId = $this->getObjectId();

			if ($transactionModeEnabled) {
				$connection->startTransaction("Saving property for object #$objectId");
			}

			try {
				$result = $this->saveValue();
			} catch (Exception $e) {
				if ($transactionModeEnabled) {
					$connection->rollbackTransaction();
				}

				throw $e;
			}

			if ($transactionModeEnabled) {
				$connection->commitTransaction();
			}

			self::unloadPropData($objectId);
			$umiPropertiesHelper = umiPropertiesHelper::getInstance();
			$umiPropertiesHelper->resetPropertyCache($objectId, $this->getName());

			return $result;
		}

		/**
		 * Сохраняет значение свойства
		 * @return mixed
		 * @throws databaseException
		 */
		abstract protected function saveValue();

		/**
		 * Загружает значение свойства
		 * @return array массив со значением
		 * @throws databaseException
		 */
		abstract protected function loadValue();

		/**
		 * Определяет нужно ли сохранять значение, то есть было ли оно изменено.
		 * @param array $newValue новое значение
		 * @return bool
		 */
		abstract protected function isNeedToSave(array $newValue);

		/**
		 * todo: move it from here
		 * Загружает значение полей объекта
		 * @return array
		 * @throws Exception
		 */
		protected function getPropData() {

			$fieldId = $this->getFieldId();
			$objectId = $this->getObjectId();
			$cache = &umiObjectProperty::$dataCache;

			if (isset($cache[$objectId])) {

				if (isset($cache[$objectId][$fieldId])) {
					return $cache[$objectId][$fieldId];
				}

				return [
					'int_val' => [],
					'varchar_val' => [],
					'text_val' => [],
					'rel_val' => [],
					'tree_val' => [],
					'float_val' => [],
					'img_val' => []
				];
			}

			if (self::loadPropsData([$objectId])) {
				return $this->getPropData();
			}

			return [];
		}

		/**
		 * Валидирует значение согласно настройкам поля
		 * @param mixed $value проверяемое начение
		 * @return mixed проверенное (возможно, модифицированное) значение поля
		 * @throws valueRequiredException
		 * @throws wrongValueException
		 */
		protected function validateValue($value) {
			$field = $this->getField();

			if (!$field instanceof iUmiField) {
				return $value;
			}

			if (($value === null || $value === false || $value === '') && $field->getIsRequired()) {
				throw new valueRequiredException(getLabel('error-value-required', null, $this->getTitle()));
			}

			if ($value && $restrictionId = $field->getRestrictionId()) {
				$restriction = baseRestriction::get($restrictionId);
				if ($restriction instanceof baseRestriction) {
					if ($restriction instanceof iNormalizeInRestriction) {
						$value = $restriction->normalizeIn($value, $this->object_id);
					}

					if ($restriction->validate($value, $this->object_id) === false) {
						throw new wrongValueException(getLabel($restriction->getErrorMessage(), null, $this->getTitle()));
					}
				}
			}

			return $value;
		}

		/**
		 * @internal
		 * todo: move it from here
		 * Загружает значение полей объектов
		 * @param array $objectIdList массив с идентификаторами объектов
		 * @param bool $hierarchyTypeIds оставлен для обратной совместимости
		 * @return bool
		 * @throws Exception
		 */
		public static function loadPropsData(array $objectIdList, $hierarchyTypeIds = false) {
			if (isEmptyArray($objectIdList)) {
				return false;
			}

			$objectIdList = array_map('intval', $objectIdList);
			$cache = &umiObjectProperty::$dataCache;

			$objectIdList = array_filter($objectIdList, function($id) use ($cache) {
				return !isset($cache[$id]);
			});

			if (isEmptyArray($objectIdList)) {
				return false;
			}

			self::clearOverflowObjectPropsCache();

			foreach ($objectIdList as $id) {
				$cache[$id] = [];
				umiObjectProperty::$dataCacheOrder[] = $id;
			}

			self::loadCommonFieldValues($objectIdList);
			self::loadImageFieldValues($objectIdList);

			return true;
		}

		/**
		 * todo: move it from here
		 * Загружает значения обычных полей объектов
		 * @param int[] $objectIdList список идентификаторов объектов
		 * @throws databaseException
		 * @throws Exception
		 */
		private static function loadCommonFieldValues(array $objectIdList) {
			$tableName = Service::ObjectPropertyValueTableSchema()
				->getDefaultTable();
			$objectsIdCondition = implode(',', $objectIdList);
			$sql = <<<SQL
SELECT `obj_id`, `field_id`, `int_val`, `varchar_val`, `text_val`, `rel_val`, `tree_val`, `float_val` 
FROM `$tableName` 
WHERE `obj_id` IN ($objectsIdCondition)
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql)
				->setFetchType(IQueryResult::FETCH_ASSOC);
			$cache = &umiObjectProperty::$dataCache;

			foreach ($result as $row) {
				$cache[$row['obj_id']][$row['field_id']]['int_val'][] = $row['int_val'];
				$cache[$row['obj_id']][$row['field_id']]['varchar_val'][] = $row['varchar_val'];
				$cache[$row['obj_id']][$row['field_id']]['text_val'][] = $row['text_val'];
				$cache[$row['obj_id']][$row['field_id']]['rel_val'][] = $row['rel_val'];
				$cache[$row['obj_id']][$row['field_id']]['tree_val'][] = $row['tree_val'];
				$cache[$row['obj_id']][$row['field_id']]['float_val'][] = $row['float_val'];
			}
		}

		/**
		 * todo: move it from here
		 * Загружает значения полей изображений объектов
		 * @param int[] $objectIdList список идентификаторов объектов
		 * @throws databaseException
		 * @throws Exception
		 */
		private static function loadImageFieldValues(array $objectIdList) {
			$tableName = Service::ObjectPropertyValueTableSchema()
				->getImagesTable();
			$objectsIdCondition = implode(',', $objectIdList);
			$sql = <<<SQL
SELECT `id`, `obj_id`, `field_id`, `src`, `alt`, `title`, `ord`
FROM `$tableName` 
WHERE `obj_id` IN ($objectsIdCondition)
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql)
				->setFetchType(IQueryResult::FETCH_ASSOC);
			$cache = &umiObjectProperty::$dataCache;

			foreach ($result as $row) {
				$cache[$row['obj_id']][$row['field_id']]['img_val'][] = [
					'id' => $row['id'],
					'src' => $row['src'],
					'alt' => $row['alt'],
					'title' => $row['title'],
					'ord' => $row['ord']
				];
			}
		}

		/**
		 * @internal
		 * todo: move it from here
		 * Выгружает из памяти контент полей объекта.
		 * @param int $objectId ид объекта
		 * @return bool
		 */
		public static function unloadPropData($objectId) {
			if (!is_numeric($objectId)) {
				return false;
			}

			$cache = &umiObjectProperty::$dataCache;

			if (isset($cache[$objectId])) {
				unset($cache[$objectId]);

				$index = array_search($objectId, umiObjectProperty::$dataCacheOrder);

				if ($index) {
					unset(umiObjectProperty::$dataCacheOrder[$index]);
				}

				return true;
			}

			return false;
		}

		/**
		 * @internal
		 * Возвращает кешированнные данные полей
		 * todo: move it from here
		 * @return array
		 */
		public static function getCachedPropData() {
			return umiObjectProperty::$dataCache;
		}

		/**
		 * @internal
		 * Устанавливает кешированнные данные полей
		 * todo: move it from here
		 * @param array $data
		 */
		public static function setCachedPropData(array $data) {
			umiObjectProperty::$dataCache = $data;

			foreach ($data as $id => $cache) {
				umiObjectProperty::$dataCacheOrder[] = $id;
			}
		}

		/** @inheritdoc */
		public function getObjectId() {
			return $this->object_id;
		}

		/** @inheritdoc */
		public function getFieldId() {
			return $this->field_id;
		}

		/**
		 * Удаляет текущие значения поля
		 * todo: внедрить update|insert вместо delete + insert для полей с одним значением
		 */
		protected function deleteCurrentRows() {
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$tableName = $this->getTableName();

			$sql = <<<SQL
DELETE FROM `$tableName` WHERE `obj_id` = $objectId AND `field_id` = $fieldId;
SQL;
			$this->getConnection()
				->query($sql);
		}

		/**
		 * TODO PHPDoc
		 * @param $values
		 * @param null $params
		 * @return mixed
		 */
		protected function applyParams($values, $params = null) {
			return $values;
		}

		/**
		 * TODO PHPDoc
		 * @param mixed $value устанавливаемое значение свойства
		 * @return int ID связанного элемента или null если элемент не существует, и не удалось добавить новый
		 * @throws coreException
		 */
		protected function prepareRelationValue($value) {
			if (!$value) {
				return false;
			}

			$objectsCollection = umiObjectsCollection::getInstance();
			$forceObjectsCreation = self::$USE_FORCE_OBJECTS_CREATION;

			if ($objectsCollection->isUmiObject($value)) {
				return $value->getId();
			}

			$field = $this->getField();

			if ($field->hasGuide()) {
				$guideId = $field->getGuideId();

				$object = $objectsCollection->getObjectByName($value, $guideId);

				if ($objectsCollection->isUmiObject($object)) {
					return $object->getId();
				}

				if ($objectsCollection->isExists($value) && !$forceObjectsCreation) {
					return (int) $value;
				}

				if (!$forceObjectsCreation) {
					$type = umiObjectTypesCollection::getInstance()->getType($guideId);

					if (!$type->getIsGuidable() || !$type->getIsPublic()) {
						return null;
					}
				}

				$label = ulangStream::getI18n($value);
				$value = $label === null ? $value : $label;
				$newObjectId = $objectsCollection->addObject($value, $guideId);

				if ($newObjectId > 0) {
					return $newObjectId;
				}

				throw new coreException("Can't create guide item");
			}

			return null;
		}

		/**
		 * Возвращает подключение к базе данных
		 * @return \IConnection
		 */
		protected function getConnection() {
			return Service::ConnectionPool()
				->getConnection();
		}

		/**
		 * @deprecated
		 * @param int $objectId
		 * @param int $fieldId
		 * @return iUmiObjectProperty
		 */
		public static function getProperty($objectId, $fieldId) {
			return Service::ObjectPropertyFactory()
				->create($objectId, $fieldId);
		}

		/** @inheritdoc */
		public function getField() {
			$field = umiFieldsCollection::getInstance()
				->getField($this->field_id);

			if (!$field instanceof iUmiField) {
				throw new Exception(sprintf('Cannot get umiField by "%s"', $this->field_id));
			}

			return $field;
		}

		/**
		 * todo: move it from here
		 * Возвращает значение ограничения на размер кэша значений полей
		 * @return int
		 */
		private static function getObjectPropsCacheSizeLimit() {
			$mainConfigs = mainConfiguration::getInstance();
			$configSize = (int) $mainConfigs->get('kernel', 'objects-props-cash-size');

			if ($configSize > self::DEFAULT_OBJECT_PROPS_CACHE_LIMIT) {
				return (int) $configSize;
			}

			return self::DEFAULT_OBJECT_PROPS_CACHE_LIMIT;
		}

		/**
		 * todo: move it from here
		 * Удаляет кэш значений полей объектов, который вышел за отведенный лимит
		 * @return bool
		 */
		private static function clearOverflowObjectPropsCache() {
			$cache = &umiObjectProperty::$dataCache;
			$cacheSize = count($cache);
			$cacheSizeLimit = self::getObjectPropsCacheSizeLimit();

			if ($cacheSize <= $cacheSizeLimit) {
				return true;
			}

			$overflowSize = $cacheSize - $cacheSizeLimit;

			for ($i = 0; $i < $overflowSize; $i++ ) {
				$id = array_shift(umiObjectProperty::$dataCacheOrder);

				if ($id !== null) {
					self::unloadPropData($id);
				}
			}

			return true;
		}

		/**
		 * @deprecated
		 * @return iUmiFieldType
		 * @throws Exception
		 */
		protected function getFieldType() {
			$fieldTypeId = $this->getField()
				->getFieldTypeId();
			$fieldType = umiFieldTypesCollection::getInstance()
				->getFieldType($fieldTypeId);

			if (!$fieldType instanceof iUmiFieldType) {
				throw new Exception(sprintf('Cannot get umiFieldType by "%s"', $fieldTypeId));
			}

			return $fieldType;
		}

		/** @deprecated */
		public static function filterOutputString($string) {
			return $string;
		}

		/** @deprecated */
		public static function filterCDATA($string) {
			return $string;
		}

		/** @inheritdoc */
		public function getObject() {
			$object = umiObjectsCollection::getInstance()
				->getObject($this->object_id);

			if (!$object instanceof iUmiObject) {
				throw new Exception(sprintf('Cannot get umiObject by "%s"', $this->object_id));
			}

			return $object;
		}

		/**
		 * @deprecated
		 * todo: move it or remove
		 */
		protected static function unescapeFilePath($filepath) {
			return str_replace("\\\\", '/', $filepath);
		}

		/**
		 * @deprecated
		 * todo: move it or remove
		 */
		public static function objectsByValue(
			$i_field_id,
			$arr_value = null,
			$b_elements = false,
			$b_stat = true,
			$arr_domains = null
		) {
			$arr_answer = [];

			// ==== validate input : =======================

			if (!($arr_value === null || is_array($arr_value) || (int) $arr_value === -1 || (string) $arr_value === 'all' ||
				(string) $arr_value == 'Все')) {
				$arr_value = [$arr_value];
			}

			// h.domain_id
			$arr_domain_ids = null;
			if ($b_elements) {
				if ($arr_domains === null) { // current domain
					$arr_domain_ids = [Service::DomainDetector()->detectId()];
				} elseif ((int) $arr_domains === -1 || (string) $arr_domains === 'all' || (string) $arr_domains == 'Все') {
					$arr_domain_ids = [];
				} elseif (is_array($arr_domains)) {
					$arr_domain_ids = array_map('intval', $arr_domains);
				} else {
					$arr_domain_ids = [(int) $arr_domains];
				}
			}

			$field = umiFieldsCollection::getInstance()->getField($i_field_id);
			if ($field instanceof iUmiField) {
				$fieldDataType = $field->getFieldType()->getDataType();
				$s_col_name = umiFieldType::getDataTypeDB($fieldDataType);
			} else {
				throw new coreException("Field #{$i_field_id} not found");
			}

			// ==== construct sql queries : ================

			$objectTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByFieldId($i_field_id);
			$tableName = umiBranch::getBranchedTableByTypeId($objectTypeId);

			$s_from = "{$tableName} `o`";
			if ($b_elements) {
				$s_from .= ', cms3_hierarchy `h`';
			}

			if ($b_elements) {
				$s_count_field = 'h.id';
			} else {
				$s_count_field = 'o.obj_id';
			}

			$s_where_tail = ($b_elements ? ' AND h.obj_id = o.obj_id AND h.is_active=1 AND h.is_deleted=0' : '');

			if ($b_elements && is_array($arr_domain_ids) && umiCount($arr_domain_ids)) {
				$s_where_tail .= " AND h.domain_id IN ('" . implode("', '", $arr_domain_ids) . "')";
			}

			$s_values_filter = '';
			if (!((int) $arr_value === -1 || (string) $arr_value === 'all' || (string) $arr_value === 'Âñå')) {
				$s_values_filter =
					" AND o.{$s_col_name} " .
					($arr_value === null ? 'IS NULL' : "IN ('" . implode("', '", $arr_value) . "')");
			}

			if ($b_stat) {
				$s_query =
					'SELECT o.' . $s_col_name . ' as `value`, COUNT(' . $s_count_field . ') as `items` FROM ' . $s_from .
					' WHERE o.field_id = ' . $i_field_id . $s_values_filter . $s_where_tail . ' GROUP BY o.' . $s_col_name .
					' ORDER BY `items`';
			} else {
				$s_query = 'SELECT DISTINCT ' . $s_count_field . ' as `item` FROM ' . $s_from . ' WHERE o.field_id = ' .
					$i_field_id .
					$s_values_filter . $s_where_tail;
			}

			// ==== execute sql query : ====================

			$arr_query = [];
			$connection = ConnectionPool::getInstance()->getConnection();
			$rs_query = $connection->queryResult($s_query);

			if ($connection->errorOccurred()) {
				throw new coreException(
					'Error executing db query (errno ' . $connection->errorNumber() . ', error ' .
					$connection->errorDescription($s_query) . ')'
				);
			}

			$rs_query->setFetchType(IQueryResult::FETCH_ASSOC);

			foreach ($rs_query as $arr_next_row) {
				$arr_query[] = $arr_next_row;
			}

			// ==== construct returning answer : ===========

			if ($b_stat) {
				$arr_answer['values'] = [];
				$i_max = 0;
				$i_summ = 0;
				foreach ($arr_query as $arr_row) {
					$i_cnt = (int) $arr_row['items'];

					$arr_answer['values'][] = [
						'value' => $arr_row['value'],
						'cnt' => $i_cnt
					];

					if ($i_cnt > $i_max) {
						$i_max = $i_cnt;
					}
					$i_summ += $i_cnt;
				}
				$arr_answer['max'] = $i_max;
				$arr_answer['sum'] = $i_summ;
			} else {
				foreach ($arr_query as $arr_row) {
					$arr_answer[] = $arr_row['item'];
				}
			}

			return $arr_answer;
		}
	}
