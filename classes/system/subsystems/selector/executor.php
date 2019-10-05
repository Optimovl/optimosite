<?php

	use UmiCms\Service;
	use UmiCms\System\Data\Object\Property\Value;

	class selectorExecutor {

		public $length;

		protected
			$selector,
			$queryColumns = [],
			$queryTables = [],
			$queryJoinTables = [],
			$queryLimit = [],
			$queryFields = [],
			$orderFields = [],
			$groupFields = [],
			$queryOptions = [],
			$skipExecutedCheck = false;

		/** @var int количество иерархических типов, по которым идет выборка */
		private $hierarchyTypesCount = 0;

		/** @var array иерархические типы, по которым идет выборка */
		private $hierarchyTypeIds = [];

		/** @var array объектные типы, по которым идет выборка */
		private $objectTypeIds = [];

		/** @var array(table => condition) $leftJoins таблицы которые необходимо подключить по заданным условиям */
		private $leftJoins = [];

		/**
		 * Конструктор
		 * @param selector $selector
		 */
		public function __construct(selector $selector) {
			$this->selector = $selector;
			$this->analyze();
		}

		/**
		 * Возвращает sql запрос
		 * @return string
		 */
		public function query() {
			return $this->buildQuery('result');
		}

		/**
		 * Возвращает результат выборки
		 * @return array
		 */
		public function result() {
			$sql = $this->buildQuery('result');
			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($sql);

			if (!$this->selector->option('no-length')->value) {
				$countResult = $connection->queryResult('SELECT FOUND_ROWS()');
				$countResult->setFetchType(IQueryResult::FETCH_ROW);

				if ($countResult->length() > 0) {
					$fetchResult = $countResult->fetch();
					$count = array_shift($fetchResult);
				} else {
					$count = 0;
				}

				$countResult->freeResult();
				$this->length = (int) $count;
			}

			if ($this->selector->__get('mode') == 'objects') {
				return $this->getObjectResult($result);
			}

			$idList = [];
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			foreach ($result as $row) {
				$id = (int) $row['id'];
				$parentId = isset($row['pid']) ? (int) $row['pid'] : 0;
				$idList[$id] = $parentId;
			}

			if ($this->selector->option('exclude-nested')->value) {
				$filteredIdList = $this->excludeNestedPages($idList);
				$this->length = count($filteredIdList);

				$limit = $this->selector->__get('limit');
				$offset = $this->selector->__get('offset');

				if ($limit || $offset) {
					$filteredIdList = array_slice($filteredIdList, $offset, $limit);
				}
			} else {
				$filteredIdList = array_keys($idList);
			}

			$filteredIdList = array_unique($filteredIdList);
			return $this->getPageResult($filteredIdList);
		}

		public function length() {
			if ($this->length !== null) {
				return $this->length;
			}

			$this->skipExecutedCheck = true;
			if (umiCount($this->selector->groupSysProps) || umiCount($this->selector->groupFieldProps)) {
				$sql = $this->buildQuery('result');
			} else {
				$sql = $this->buildQuery('count');
			}

			$this->skipExecutedCheck = false;
			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if (umiCount($this->selector->groupSysProps) || umiCount($this->selector->groupFieldProps)) {
				$count = $result->length();
			} else {
				$count = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$count = (int) array_shift($fetchResult);
				}
			}
			return $this->length = $count;
		}

		public static function getContentTableName(selector $selector, $fieldId) {
			$column = ($fieldId !== null) ? self::getFieldColumn($fieldId) : null;
			return Service::ObjectPropertyValueTableSchema()
				->getTableByDataType($column);
		}

		public function getSkipExecutedCheckState() {
			return $this->skipExecutedCheck;
		}

		protected function analyze() {
			$selector = $this->selector;
			switch ($selector->mode) {
				case 'objects':
					$this->requireTable('o', 'cms3_objects');
					$this->requireTable('t', 'cms3_object_types');
					break;
				case 'pages':
					$this->requireTable('h', 'cms3_hierarchy');
					break;
			}
			$this->analyzeFields();
			$this->analyzeLimit();
		}

		protected function requireTable($alias, $tableName) {
			$this->queryTables[$alias] = $tableName;
		}

		protected function requireSysProp($propName) {
			$propTable = [];
			$propTable['name'] = ['o.name', 'table' => ['o', 'cms3_objects']];
			$propTable['guid'] = ['o.guid', 'table' => ['o', 'cms3_objects']];
			$propTable['owner'] = ['o.owner_id', 'table' => ['o', 'cms3_objects']];
			$propTable['domain'] = ['h.domain_id'];
			$propTable['lang'] = ['h.lang_id'];
			$propTable['obj_id'] = ['h.obj_id'];
			$propTable['obj_type_id'] = ['o.type_id', 'table' => ['o', 'cms3_objects']];
			$propTable['is_deleted'] = ['h.is_deleted'];
			$propTable['is_default'] = ['h.is_default'];
			$propTable['is_visible'] = ['h.is_visible'];
			$propTable['is_active'] = ['h.is_active'];
			$propTable['domain'] = ['h.domain_id'];
			$propTable['rand'] = ['RAND()'];
			$propTable['template_id'] = ['h.tpl_id'];
			$propTable['alt_name'] = ['h.alt_name'];

			if ($this->selector->mode == 'pages') {
				$propTable['updatetime'] = ['h.updatetime'];
				$propTable['ord'] = ['h.ord', 'table' => ['h', 'cms3_hierarchy']];
				$propTable['id'] = ['h.id'];
			} else {
				$propTable['updatetime'] = ['o.updatetime'];
				$propTable['ord'] = ['o.ord', 'table' => ['o', 'cms3_objects']];
				$propTable['id'] = ['o.id', 'table' => ['o', 'cms3_objects']];
			}

			if (isset($propTable[$propName])) {
				$info = $propTable[$propName];
				if (isset($info['table'])) {
					$this->requireTable($info['table'][0], $info['table'][1]);
				}
				return $info[0];
			}

			throw new selectorException("Not supported property \"{$propName}\"");
		}

		protected function analyzeFields() {
			$selector = $this->selector;

			$selectorFields = $selector->whereFieldProps;
			$fields = [];

			foreach ($selectorFields as $whereField) {
				if (!$whereField instanceof selectorWhereFieldProp) {
					continue;
				}

				foreach ($whereField->getFieldIdList() as $fieldId) {
					$fields[] = $fieldId;
				}
			}

			$fields = array_unique($fields);

			foreach ($fields as $fieldId) {
				$tableName = self::getContentTableName($selector, $fieldId);
				$this->requireTable('oc_' . $fieldId, $tableName);
				$this->queryFields[] = $fieldId;
			}

			$selectorOrderFields = $this->selector->orderFieldProps;
			$orderFields = [];

			foreach ($selectorOrderFields as $orderField) {
				if (!$orderField instanceof selectorOrderFieldProp) {
					continue;
				}

				foreach ($orderField->getFieldIdList() as $fieldId) {
					$orderFields[] = $fieldId;
				}
			}

			if (umiCount($orderFields) > 0) {
				$this->orderFields = $orderFields;
			}

			$selectorGroupFields = $this->selector->groupFieldProps;
			$groupFields = [];

			foreach ($selectorGroupFields as $groupField) {
				if (!$groupField instanceof selectorGroupFieldProp) {
					continue;
				}

				foreach ($groupField->getFieldIdList() as $fieldId) {
					$groupFields[] = $fieldId;
				}
			}

			if (umiCount($groupFields) > 0) {
				$this->groupFields = $groupFields;
			}

			//TODO: Attach tables, required by sys props
			//$selectorSysProps = array_merge($selector->whereSysProps, $selector->orderSysProps);
		}

		protected function analyzeLimit() {
			$selector = $this->selector;

			if ($selector->option('exclude-nested')->value) {
				return;
			}

			if ($selector->limit || $selector->offset) {
				$this->queryLimit = [(int) $selector->offset, (int) $selector->limit];
			}
		}

		protected function buildQuery($mode) {

			if ($this->selector->option('root')->value) {
				return $this->buildRootQuery($mode);
			}

			$limitSql = $orderSql = '';
			if ($mode != 'count') {
				$limitSql = $this->buildLimit();
				$orderSql = $this->buildOrder();
			}

			$groupSql = $this->buildGroup();
			$whereSql = $this->buildWhere();
			$ljoinSql = $this->buildLeftJoins();
			$tablesSql = $this->buildTables();
			$optionsSql = $this->buildOptions($mode);

			if ($mode == 'result') {
				if ($this->selector->mode == 'objects') {
					$this->queryColumns = [
						'o.id as id',
						'o.name as name',
						'o.type_id as type_id',
						'o.is_locked as is_locked',
						'o.owner_id as owner_id',
						'o.guid as guid',
						't.guid as type_guid',
						'o.updatetime as updatetime',
						'o.ord as ord'
					];
				} else {
					$this->queryColumns = ['h.id as id', 'h.rel as pid'];
				}
			} else {
				$distinct = in_array('DISTINCT', $this->queryOptions);
				if ($this->selector->mode == 'objects') {
					$this->queryColumns = $distinct ? ['COUNT(DISTINCT o.id)'] : ['COUNT(o.id)'];
				} else {
					$this->queryColumns = $distinct ? ['COUNT(DISTINCT h.id)'] : ['COUNT(h.id)'];
				}
			}

			$columnsSql = $this->buildColumns();

			return <<<SQL
SELECT {$optionsSql} {$columnsSql}
	FROM {$tablesSql}
	{$ljoinSql}
	{$whereSql}
	{$groupSql}
	{$orderSql}
	{$limitSql}
SQL;
		}

		protected function buildOptions($mode) {
			$queryOptions = $this->queryOptions;
			$queryOptions = array_unique($queryOptions);

			if (MAX_SELECTION_TABLE_JOINS > 0 && MAX_SELECTION_TABLE_JOINS < umiCount($this->queryJoinTables)) {
				$queryOptions[] = 'STRAIGHT_JOIN';
			}

			if ($mode == 'result' && !$this->selector->option('no-length')->value) {
				$queryOptions[] = 'SQL_CALC_FOUND_ROWS';
			}

			return implode(' ', $queryOptions);
		}

		protected function buildLeftJoins() {
			$joins = [];
			$fieldsId = [];
			$fields = [];

			$data_joins = array_merge(
				$this->selector->orderFieldProps,
				$this->selector->whereFieldProps,
				$this->selector->groupFieldProps
			);

			/** @var selectorGroupFieldProp|selectorOrderFieldProp|selectorWhereFieldProp $data_join */
			foreach ($data_joins as $data_join) {
				if (!is_callable([$data_join, 'getFieldIdList'])) {
					continue;
				}

				$fields = array_merge($fields, $data_join->getFieldIdList());
			}

			$fields = array_unique($fields);

			foreach ($fields as $fieldId) {
				if (in_array($fieldId, $fieldsId)) {
					continue;
				}

				$this->requireTable('o', 'cms3_objects');
				$tableName = self::getContentTableName($this->selector, $fieldId);
				$join = <<<SQL
LEFT JOIN {$tableName} oc_{$fieldId}_lj ON oc_{$fieldId}_lj.obj_id=o.id AND oc_{$fieldId}_lj.field_id = '{$fieldId}'
SQL;
				$joins[] = $join;
				$fieldsId[] = $fieldId;
				$this->queryJoinTables[] = $tableName;
			}

			foreach ($this->getLeftJoins() as $tableName => $condition) {
				$join = <<<SQL
LEFT JOIN $tableName ON $condition
SQL;
				$joins[] = $join;
				$this->queryJoinTables[] = $tableName;
			}

			return empty($joins) ? '' : implode(' ', $joins);
		}

		protected function buildColumns() {
			return implode(', ', $this->queryColumns);
		}

		protected function buildTables() {
			$tables = [];
			$joinObjectsTable = false;

			foreach ($this->queryTables as $alias => $name) {
				if ($name == 'cms3_objects' && $joinObjectsTable === false) {
					$joinObjectsTable = $alias;
					continue;
				}
				if ($this->isContentTable($name) && ($alias != 'o_asteriks')) {
					continue;
				}
				$tables[] = $name . ' ' . $alias;
			}
			if ($joinObjectsTable !== false) {
				$tables[] = $this->queryTables[$joinObjectsTable] . ' ' . $joinObjectsTable;
			}
			return implode(', ', $tables);
		}

		/**
		 * Оформляет результат выборки страниц.
		 * Формат результата зависит от опции 'return'
		 * @param array $pageIdList список идентификаторов страниц
		 * @return array iUmiHierarchyElement[] или [['id' => 123]] или [['id' => 123, 'field' => 'value']]
		 */
		private function getPageResult(array $pageIdList) {
			if (empty($pageIdList)) {
				return [];
			}

			umiLinksHelper::getInstance()
				->loadLinkPartForPages($pageIdList);

			$queryResult = $this->getPageRowList($pageIdList);
			$fieldListToReturn = (array) $this->selector->option('return')->value;
			$fieldListToReturnCount = count($fieldListToReturn);
			$idRequired = $fieldListToReturnCount == 1 && getFirstValue($fieldListToReturn) == 'id';
			$outputList = [];

			if ($idRequired) {
				foreach ($queryResult as $row) {
					$id = getFirstValue($row);
					$outputList[$id] = [
						'id' => $id
					];
				}

				$outputList = array_values($outputList);
				return def_module::sortObjects($pageIdList, $outputList);
			}

			$pageCollection = umiHierarchy::getInstance();
			$pageList = [];
			$objectIdList = [];

			foreach ($queryResult as $row) {
				$id = getFirstValue($row);
				$page = $pageCollection->getElement($id, true, true, array_slice($row, 1));

				if ($page instanceof iUmiHierarchyElement) {
					$pageList[] = $page;
					$objectIdList[] = $page->getObjectId();
				}
			}

			$fieldListRequired = $fieldListToReturnCount > 0;

			if (!empty($objectIdList) && ($fieldListRequired || $this->selector->option('load-all-props')->value)) {
				umiObjectProperty::loadPropsData($objectIdList);
			}

			if (!$fieldListRequired) {
				$pageList = array_values($pageList);
				return def_module::sortObjects($pageIdList, $pageList);
			}

			/** @var iUmiHierarchyElement $page */
			foreach ($pageList as $page) {
				$fieldList = [];

				foreach ($fieldListToReturn as $fieldName) {
					$fieldList[$fieldName] = $this->getPageValue($fieldName, $page);
				}

				if (!isset($fieldList['id'])) {
					$fieldList['id'] = $page->getId();
				}

				$outputList[$page->getId()] = $fieldList;
			}

			$outputList = array_values($outputList);
			return def_module::sortObjects($pageIdList, $outputList);
		}

		/**
		 * Возвращает список данных страниц
		 * @param array $pageIdList список идентификаторов страниц
		 * @return IQueryResult
		 */
		private function getPageRowList(array $pageIdList) {
			$sqlIdList = implode(',', $pageIdList);
			$limit = count($pageIdList);
			$sql = <<<SQL
SELECT
	h.id,
	h.rel,
	h.type_id,
	h.lang_id,
	h.domain_id,
	h.tpl_id,
	h.obj_id,
	h.ord,
	h.alt_name,
	h.is_active,
	h.is_visible,
	h.is_deleted,
	h.updatetime,
	h.is_default,
	o.name,
	o.type_id,
	o.is_locked,
	o.owner_id,
	o.guid,
	t.guid,
	o.updatetime,
	o.ord
FROM cms3_hierarchy h, cms3_objects o, cms3_object_types t
WHERE 
	h.id IN ($sqlIdList)
	AND o.id = h.obj_id
	AND o.type_id = t.id
LIMIT 0, $limit
SQL;
			$queryResult = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			$queryResult->setFetchType(IQueryResult::FETCH_ROW);
			return $queryResult;
		}

		/**
		 * Оформляет результат выборки объектов.
		 * Формат результата зависит от опции 'return'
		 * @param IQueryResult $result результат выборки
		 * @return array iUmiObject[] или [['id' => 123]] или [['id' => 123, 'field' => 'value']]
		 */
		private function getObjectResult(IQueryResult $result) {

			$result->setFetchType(IQueryResult::FETCH_ROW);
			$idList = [];
			$rowList = [];

			foreach ($result as $row) {
				$id = getFirstValue($row);
				$idList[] = $id;
				$rowList[$id] = $row;
			}

			$idList = array_unique($idList);
			$fieldListToReturn = (array) $this->selector->option('return')->value;
			$fieldListToReturnCount = count($fieldListToReturn);
			$idRequired = $fieldListToReturnCount == 1 && getFirstValue($fieldListToReturn) == 'id';
			$fieldListRequired = $fieldListToReturnCount > 0 && !$idRequired;

			if (!empty($idList) && ($fieldListRequired || $this->selector->option('load-all-props')->value)) {
				umiObjectProperty::loadPropsData($idList);
			}

			$result = [];
			$objectCollection = umiObjectsCollection::getInstance();

			foreach ($idList as $id) {

				$row = $rowList[$id];

				switch (true) {
					case $idRequired : {
						$result[] = [
							'id' => $id
						];
						break;
					}
					case $fieldListRequired : {
						$object = $objectCollection->getObject($id, array_slice($row, 1));

						if (!$object instanceof iUmiObject) {
							break;
						}

						$fieldList = [];

						foreach ($fieldListToReturn as $fieldName) {
							$fieldList[$fieldName] = $this->getObjectValue($fieldName, $object);
						}

						if (!isset($fieldList['id'])) {
							$fieldList['id'] = $id;
						}

						$result[] = $fieldList;
						break;
					}
					default : {
						$object = $objectCollection->getObject($id, array_slice($row, 1));

						if ($object instanceof iUmiObject) {
							$result[] = $object;
						}
					}
				}
			}

			return def_module::sortObjects($idList, $result);
		}

		/**
		 * Определяет является ли таблица контентной
		 * @param string $table проверяемая таблица
		 * @return bool
		 */
		private function isContentTable($table) {
			$contentTableList = Service::ObjectPropertyValueTableSchema()
				->getTableList();

			foreach ($contentTableList as $contentTable) {
				if (contains($table, $contentTable)) {
					return true;
				}
			}

			return false;
		}

		protected function buildLimit() {
			if (umiCount($this->queryLimit)) {
				return " LIMIT {$this->queryLimit[0]}, {$this->queryLimit[1]}";
			}

			return '';
		}

		/**
		 * WHERE-часть SQL-запроса
		 * @return string
		 */
		protected function buildWhere() {
			$sql = '';
			$conditions = [];

			foreach ($this->selector->types as $type) {
				if ($type->objectTypeIds !== null) {
					foreach ($type->objectTypeIds as $typeId) {
						$this->objectTypeIds[] = $typeId;
					}
				}

				if ($type->hierarchyTypeIds !== null) {
					foreach ($type->hierarchyTypeIds as $typeId) {
						$this->hierarchyTypeIds[] = $typeId;
					}
				}
			}

			$this->hierarchyTypesCount = umiCount($this->hierarchyTypeIds);

			if (umiCount($this->objectTypeIds)) {
				$objectTypeIdList = array_unique($this->objectTypeIds);
				$this->requireTable('o', 'cms3_objects');
				$this->requireTable('t', 'cms3_object_types');

				if (!$this->selector->option('ignore-children-types')->value) {
					$childrenTypeIdList = umiObjectTypesCollection::getInstance()
						->getChildIdListByParentIdList($objectTypeIdList);
					$objectTypeIdList = array_merge($objectTypeIdList, $childrenTypeIdList);
				}

				$this->objectTypeIds = array_unique($objectTypeIdList);

				$conditions[] = 'o.type_id IN (' . implode(', ', $this->objectTypeIds) . ')';
				$conditions[] = 't.id = o.type_id';
			}

			if (umiCount($this->hierarchyTypeIds) && $this->selector->mode == 'pages') {
				$this->hierarchyTypeIds = array_unique($this->hierarchyTypeIds);
				$conditions[] = 'h.type_id IN (' . implode(', ', $this->hierarchyTypeIds) . ')';
			}

			if (umiCount($this->hierarchyTypeIds) && $this->selector->mode == 'objects') {
				$this->hierarchyTypeIds = array_unique($this->hierarchyTypeIds);
				$conditions[] = 't.hierarchy_type_id IN (' . implode(', ', $this->hierarchyTypeIds) . ')';
				$conditions[] = 't.id = o.type_id';
			}

			if ($this->selector->mode == 'objects' && !in_array('t.id = o.type_id', $conditions)) {
				$conditions[] = 't.id = o.type_id';
			}

			if (umiCount($this->queryFields) || umiCount($this->orderFields) || umiCount($this->groupFields)) {
				$this->requireTable('o', 'cms3_objects');
			}

			$umiFields = umiFieldsCollection::getInstance();
			$orMode = $this->selector->option('or-mode');
			$whereConds = [];
			$whereOrConds = [];
			$whereConditions = '';
			$orModeFieldList = isset($orMode->value['fields']) ? $orMode->value['fields'] : [];

			foreach ($this->queryFields as $fieldId) {
				if (in_array($umiFields->getField($fieldId)->getName(), $orModeFieldList)) {
					$whereOrConds[] = $this->buildWhereValue($fieldId);
				} else {
					$whereConds[] = $this->buildWhereValue($fieldId);
				}
			}

			$sysProps = $this->selector->whereSysProps;
			/* @var selectorWhereSysProp $sysProp */
			foreach ($sysProps as $sysProp) {
				$propName = $sysProp->name;
				if (in_array($propName, $orModeFieldList)) {
					$whereOrConds[$propName] = $this->buildSysProp($sysProp);
				}
			}

			if (count($whereConds) || count($whereOrConds)) {
				if (isset($orMode->value['all'])) {
					$whereConds = array_merge($whereConds, $whereOrConds);
					$whereConditions = implode(' OR ', $whereConds);
				} else {
					if (count($whereConds) > 0) {

						$whereConditions .= implode(' AND ', $whereConds);

						if (count($whereOrConds)) {
							$whereConditions .= ' AND ';
						}
					}

					$whereConditions .= $this->getOrModeCondition($whereOrConds);
				}

				$conditions[] = '(' . $whereConditions . ')';
			}

			$sysWhereOrConds = [];

			/* @var selectorWhereSysProp $sysProp */
			foreach ($sysProps as $sysProp) {
				$condition = $this->buildSysProp($sysProp);
				$propName = $sysProp->name;
				$sysWhereOrConds[$propName] = isset($sysWhereOrConds[$propName]) ? $sysWhereOrConds[$propName] : [];
				$isSystemOrMode = (isset($orMode->value['field']) && in_array($propName, $orMode->value['field']));

				switch (true) {
					case isset($whereOrConds[$propName]): {
						break;
					}
					case $isSystemOrMode && !$condition: {
						$sysWhereOrConds[$propName] = [];
						break;
					}
					case $isSystemOrMode: {
						$sysWhereOrConds[$propName][] = $condition;
						break;
					}
					case !$isSystemOrMode && !$condition: {
						unset($conditions[$propName]);
						break;
					}
					case !$isSystemOrMode: {
						$conditions[$propName] = $condition;
						break;
					}
				}
			}

			if ($this->selector->mode == 'pages') {
				$permConds = $this->buildPermissions();
				if ($permConds) {
					$conditions[] = $permConds;
				}

				$hierarchyConds = $this->buildHierarchy($conditions);
				if ($hierarchyConds) {
					$conditions[] = $hierarchyConds;
				}

				if (isset($this->queryTables['o'])) {
					$conditions[] = 'h.obj_id = o.id';
				}
			}

			$sql .= implode(' AND ', $conditions);
			if ($sql) {
				$sql = 'WHERE ' . $sql;
			}

			if (umiCount($sysWhereOrConds) > 0) {
				foreach ($sysWhereOrConds as $sysWhereOrCond) {
					if (umiCount($sysWhereOrCond) > 1) {
						$sql .= ' AND (' . implode(' OR ', $sysWhereOrCond) . ')';
					}
				}
			}

			return $sql;
		}

		/**
		 * Возвращает часть запроса с фильтром по значениям полей в режиме "или"
		 * @param string[] $conditionList список частей запросов с фильтром по значениям полей
		 * @return string
		 */
		private function getOrModeCondition(array $conditionList) {
			$conditionCount = count($conditionList);

			if ($conditionCount === 0) {
				return '';
			}

			if ($conditionList === 1) {
				return getFirstValue($conditionList);
			}

			$conditionWithNullCount = 0;

			foreach ($conditionList as $condition) {
				if (contains($condition, 'NULL')) {
					$conditionWithNullCount++;
				}
			}

			$orModeFieldList = (array) $this->selector->option('or-mode')->value['fields'];
			$haveSeveralFieldsWithSameName = count($this->queryFields) !== count($orModeFieldList);
			$logicCombiner = ' AND ';

			if ($conditionWithNullCount !==  $conditionCount || !$haveSeveralFieldsWithSameName) {
				$logicCombiner = ' OR ';
			}

			return '(' . implode($logicCombiner, $conditionList) . ')';
		}

		protected function buildWhereValue($fieldId) {
			$wheres = $this->selector->whereFieldProps;
			$current = [];
			foreach ($wheres as $where) {
				if (!$where instanceof selectorWhereFieldProp) {
					continue;
				}

				if (in_array($fieldId, $where->getFieldIdList())) {
					$current[] = $where;
				}
			}

			$column = self::getFieldColumn($fieldId);
			$conds = [];

			foreach ($current as $where) {
				switch ($column) {
					case false: {
						if (umiCount($where->value) == 1) {
							$keys = array_keys($where->value);
							$column = array_pop($keys) . '_val';
							break;
						}

						continue 2;
					}
					case 'img_file' :
					case 'multiple_image' : {

						if (is_array($where->value) && count($where->value) == 1) {
							$keys = array_keys($where->value);
							$column = array_pop($keys);
							break;
						}

						$column = 'src';
						break;

						continue 2;
					}
				}

				$field = umiFieldsCollection::getInstance()->getField($fieldId);

				if ($field->getDataType() === 'file' || ($field->getDataType() === 'img_file' && $column === 'src')) {
					$where->value = $this->prepareFileValue($where->value, $where->mode);
				}

				$condition = $this->parseValue($where->mode, $where->value, "oc_{$fieldId}_lj.{$column}", $fieldId);
				$conds[] = ($where->mode == 'notequals')
					? "(oc_{$fieldId}_lj.{$column}{$condition})"
					: "oc_{$fieldId}_lj.{$column}{$condition}";
			}
			$field = umiFieldsCollection::getInstance()->getField($fieldId);
			$or_mode = $this->selector->option('or-mode');
			if (isset($or_mode->value['all']) ||
				(isset($or_mode->value['field']) && in_array($field->getName(), $or_mode->value['field']))) {
				$quantificator = ' OR ';
				$this->queryOptions[] = 'DISTINCT';
			} else {
				$quantificator = ' AND ';
			}
			$sql = implode($quantificator, array_unique($conds));
			return $sql ? (umiCount($conds) > 1 ? '(' . $sql . ')' : $sql) : '';
		}

		/**
		 * Подготавливает значения для файловых полей
		 * @param array|string $value искомое значение
		 * @param string $mode режим выборки
		 * @return mixed
		 * @throws Exception
		 */
		protected function prepareFileValue($value, $mode) {
			if (!in_array($mode, ['equals', 'notequals'])) {
				return $value;
			}

			$givenFilePathList = [];

			switch (true) {
				case (is_array($value) && count($value) === 1 && isset($value['src'])) : {
					$givenFilePathList[] = $value['src'];
					break;
				}
				case is_array($value) : {
					$givenFilePathList = $value;
					break;
				}
				case is_string($value) : {
					$givenFilePathList[] = $value;
					break;
				}
			}

			$fullPathList = [];

			foreach ($givenFilePathList as $path) {
				$file = Service::FileFactory()->createSecure($path);
				$filePathList = [
					$file->getFilePath(),
					'.' . $file->getFilePath(true),
				];
				$fullPathList += $filePathList;
			}

			return $fullPathList;
		}

		protected function parseValue($mode, $value, $column = false, $fieldId = false) {
			switch ($mode) {
				case 'equals':
					switch (true) {
						case (is_array($value) || is_object($value)): {
							$value = $this->escapeValue($value);
							if (umiCount($value)) {
								return ' IN(' . implode(', ', $value) . ')';
							}

							return ' = 0 = 1';  //Impossible value to reset query result to zero
						}
						case ($value == 0 && $fieldId): {
							$field = umiFieldsCollection::getInstance()->getField($fieldId);
							if ($field->getDataType() == 'boolean') {
								return ' IS NULL';
							}

							return ' = ' . $this->escapeValue($value);
						}
						default: {
							return ' = ' . $this->escapeValue($value);
						}
					}
					break;

				case 'notequals':
					if (is_array($value) || is_object($value)) {
						$value = $this->escapeValue($value);
						if (umiCount($value)) {
							return ' NOT IN(' . implode(', ', $value) . ')' . ($column ? " OR {$column} IS NULL" : '');
						}

						return ' = 0 = 1';  //Impossible value to reset query result to zero
					}

					return ' != ' . $this->escapeValue($value) . ($column ? " OR {$column} IS NULL" : '');
					break;

				case 'like':
					if (is_array($value)) {
						$conditionList = [];

						foreach ($value as $item) {
							$conditionList[] = $column . ' ' . $this->parseValue($mode, $item, $column, $fieldId);
						}

						if (count($conditionList) > 0) {
							return ltrim(implode(' AND ', $conditionList), $column);
						}

						return ' = 0  AND ' . $column . ' = 1'; //Impossible value to reset query result to zero
					}

					return ' LIKE ' . $this->escapeValue($value);

				case 'ilike':
					return $this->parseValue('like', $value, $column, $fieldId);

				case 'more':
					if (is_array($value)) {
						throw new selectorException("Method \"{$mode}\" can't accept array");
					}

					return ' > ' . $this->escapeValue($value);

				case 'eqmore':
					if (is_array($value)) {
						throw new selectorException("Method \"{$mode}\" can't accept array");
					}

					return ' >= ' . $this->escapeValue($value);

				case 'less':
					if (is_array($value)) {
						throw new selectorException("Method \"{$mode}\" can't accept array");
					}

					return ' < ' . $this->escapeValue($value);

				case 'eqless':
					if (is_array($value)) {
						throw new selectorException("Method \"{$mode}\" can't accept array");
					}

					return ' <= ' . $this->escapeValue($value);

				case 'between':
					return ' BETWEEN ' . $this->escapeValue($value[0]) . ' AND ' . $this->escapeValue($value[1]);

				case 'isnotnull':
					$value = ($value === null) ? true : $value;
					return !$value ? ' IS NULL' : ' IS NOT NULL';

				case 'isnull':
					$value = ($value === null) ? true : $value;
					return $value ? ' IS NULL' : ' IS NOT NULL';

				default:
					throw new selectorException("Unsupported field mode \"{$mode}\"");
			}
		}

		/**
		 * Формирует часть sql запроса с фильтром по значению системного поля
		 * @param selectorWhereSysProp $prop фильтр по значению системного поля
		 * @return string
		 * @throws selectorException
		 */
		protected function buildSysProp(selectorWhereSysProp $prop) {
			if ($prop->name == 'domain' || $prop->name == 'lang') {
				if ($prop->__get('value') === false) {
					return false;
				}
			}

			if ($prop->name == 'domain') {
				$arr_hierarchy = $this->selector->__get('hierarchy');
				if (umiCount($arr_hierarchy) && $arr_hierarchy[0]->elementId) {
					return false;
				}
			}

			if ($prop->name == '*') {
				$this->requireTable('o', 'cms3_objects');

				$alias = self::getContentTableName($this->selector, null);
				$tables = ['o_asteriks'];

				if ($alias != 'cms3_object_content') {
					$tables[] = 'o_asteriks_branched';
				}

				$values = $prop->__get('value');

				if (!is_array($values)) {
					$values = [$values];
				}

				$this->queryOptions[] = 'DISTINCT';
				$leftJoins = [];
				$conditions = [];

				foreach ($tables as $tableName) {
					$stringTableName = "{$tableName}_varchar";
					$textTableName = "{$tableName}_text";

					$leftJoins["cms3_object_content {$stringTableName}"] =
						"{$stringTableName}.obj_id=o.id AND {$stringTableName}.varchar_val IS NOT NULL";
					$leftJoins["cms3_object_content {$textTableName}"] =
						"{$textTableName}.obj_id=o.id AND {$textTableName}.text_val IS NOT NULL";

					foreach ($values as $value) {
						$escapedValue = $this->escapeValue($value);
						$escapedLikeValue = $this->escapeValue('%' . $value . '%');

						$conditions[] = "{$stringTableName}.varchar_val LIKE $escapedLikeValue";
						$conditions[] = "{$textTableName}.text_val LIKE $escapedLikeValue";
						$conditions[] = "o.name LIKE $escapedLikeValue";

						if (is_numeric($value)) {
							$floatTableName = "{$tableName}_float";
							$intTableName = "{$tableName}_int";

							$leftJoins["cms3_object_content {$floatTableName}"] =
								"{$floatTableName}.obj_id=o.id AND {$floatTableName}.float_val IS NOT NULL";
							$leftJoins["cms3_object_content {$intTableName}"] =
								"{$intTableName}.obj_id=o.id AND {$intTableName}.int_val IS NOT NULL";

							$conditions[] = "{$floatTableName}.float_val = $escapedValue";
							$conditions[] = "{$intTableName}.int_val = $escapedValue";
						}
					}
				}

				$this->setLeftJoins($leftJoins);
				return '(' . implode(' OR ', $conditions) . ')';
			}

			$isTranslate = !$this->selector->option('ignore-translate')->value;

			if ($prop->name == 'name' && $isTranslate) {
				$prop = $this->append18nMarksToPropValue($prop);
			}

			$name = $this->requireSysProp($prop->name);
			$sql = "{$name}" . $this->parseValue($prop->__get('mode'), $prop->__get('value'), $name);
			return ($prop->__get('mode') == 'notequals') ? '(' . $sql . ')' : $sql;
		}

		/**
		 * Добавляет к значениям фильтра по полю их языковые метки
		 * @param selectorWhereProp $property фильтр по значению поля
		 * @return selectorWhereProp
		 */
		protected function append18nMarksToPropValue(selectorWhereProp $property) {

			if (!in_array($property->__get('mode'), ['equals', 'notequals'])) {
				return $property;
			}

			$labelList = (array) $property->__get('value');
			$markList = [];

			foreach ($labelList as $value) {
				if (!is_string($value) || $value === '') {
					continue;
				}

				$mark = getI18n($value);

				if (!is_string($value) || $value === '') {
					continue;
				}

				$markList[] = $mark;
			}

			return $property->__set('value', array_merge($labelList, $markList));
		}

		protected function buildOrder() {
			$conds = [];

			foreach ($this->selector->orderFieldProps as $order) {
				if (!$order instanceof selectorOrderFieldProp) {
					continue;
				}

				foreach ($order->getFieldIdList() as $fieldId) {
					$column = self::getFieldColumn($fieldId);
					$conds[] = "oc_{$fieldId}_lj.{$column} " . ($order->asc ? 'ASC' : 'DESC');
				}
			}

			foreach ($this->selector->orderSysProps as $order) {
				$name = $this->requireSysProp($order->name);
				$conds[] = $name . ' ' . ($order->asc ? 'ASC' : 'DESC');
			}

			$sql = implode(', ', $conds);
			return $sql ? 'ORDER BY ' . $sql : '';
		}

		protected function buildGroup() {
			$conds = [];

			foreach ($this->selector->groupFieldProps as $group) {
				if (!$group instanceof selectorGroupFieldProp) {
					continue;
				}

				foreach ($group->getFieldIdList() as $fieldId) {
					$column = self::getFieldColumn($fieldId);
					$conds[] = "oc_{$fieldId}_lj.{$column}";
				}
			}

			foreach ($this->selector->groupSysProps as $group) {
				$name = $this->requireSysProp($group->name);
				$conds[] = $name;
			}

			$sql = implode(', ', $conds);
			return $sql ? 'GROUP BY ' . $sql : '';
		}

		protected function buildPermissions() {
			$this->queryOptions[] = 'DISTINCT';

			if ($this->selector->option('no-permissions')->value) {
				return '';
			}

			$permissions = $this->selector->permissions;
			$owners = $permissions->owners;

			if ($permissions && umiCount($owners)) {
				$this->requireTable('p', 'cms3_permissions');
				$systemUsersPermissions = Service::SystemUsersPermissions();
				$guestId = $systemUsersPermissions->getGuestUserId();
				if (!in_array($guestId, $owners)) {
					$owners[] = $guestId;
				}
				$owners = implode(', ', $owners);
				return "(p.rel_id = h.id AND p.level & {$permissions->level} AND p.owner_id IN({$owners}))";
			}

			return '';
		}

		protected function buildHierarchy($conds) {
			$hierarchy = $this->selector->hierarchy;

			if (umiCount($hierarchy) == 0) {
				return '';
			}

			$childrenQuery = <<<SQL
SELECT
h.id as id,
hr.level as level,
h.rel as rel,
h.ord as ord,
h.is_active as active
FROM
cms3_hierarchy h,
cms3_hierarchy_relations hr
where /**/
SQL;
			$sql = 'h.id = hr.child_id AND ';
			$harr = [];
			foreach ($hierarchy as $condition) {
				if ($condition->elementId > 0) {
					$hsql = "(hr.level <= {$condition->level} AND hr.rel_id";
				} else {
					$hsql = "(hr.level < {$condition->level} AND hr.rel_id";
				}
				$hsql .= ($condition->elementId > 0) ? " = '{$condition->elementId}'" : ' IS NULL';
				$hsql .= ')';
				$harr[] = $hsql;
			}
			if (umiCount($harr) > 1) {
				$sql .= '(';
			}
			$sql .= implode(' OR ', $harr);
			if (umiCount($harr) > 1) {
				$sql .= ')';
			}

			$childrenQuery .= $sql;
			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($childrenQuery);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$rows = [];

			foreach ($result as $row) {
				$rows[] = $row;
			}

			$rows = umiHierarchy::getInstance()->sortByHierarchy($rows);
			$parentsLevelToIds = [];
			$pagesIds = [];
			$minLevel = null;
			$filterActivityMode = null;

			switch (true) {
				case !isset($conds['is_active']) : {
					break;
				}
				case (is_numeric(mb_strpos($conds['is_active'], '1')) &&
					is_numeric(mb_strpos($conds['is_active'], '0'))) : {
					break;
				}
				case (is_numeric(mb_strpos($conds['is_active'], '1'))) : {
					$filterActivityMode = true;
					break;
				}
				case (is_numeric(mb_strpos($conds['is_active'], '0'))) : {
					$filterActivityMode = false;
					break;
				}
			}

			foreach ($rows as $row) {
				$id = $row['id'];
				$parent = $row['rel'];
				$level = $row['level'];
				$active = $row['active'];
				$isAvailable = true;

				switch (true) {
					case ($filterActivityMode === true && $active == 0) : {
						$isAvailable = false;
						break;
					}
					case ($filterActivityMode === false && $active == 1) : {
						$isAvailable = false;
						break;
					}
				}

				if ($minLevel === null || $level < $minLevel) {
					$minLevel = $level;
				}

				if (!isset($parentsLevelToIds[$level])) {
					$parentsLevelToIds[$level] = [];
				}

				if ((isset($parentsLevelToIds[$level - 1][$parent]) || $minLevel == $level) && $isAvailable) {
					$parentsLevelToIds[$level][$id] = $parent;
					$pagesIds[] = $id;
				}
			}

			return (umiCount($pagesIds) > 0) ? 'h.id in (' . implode(', ', $pagesIds) . ')' : '1 = 2';
		}

		protected static function getFieldColumn($fieldId) {
			static $cache = [];

			if (is_array($fieldId) && umiCount($fieldId) > 0) {
				$fieldId = array_shift($fieldId);
			}

			if (isset($cache[$fieldId])) {
				return $cache[$fieldId];
			}

			$field = umiFieldsCollection::getInstance()->getField($fieldId);
			switch ($field->getDataType()) {
				case 'string':
				case 'password':
				case 'color':
				case 'tags':
					return $cache[$fieldId] = 'varchar_val';

				case 'int':
				case 'boolean':
				case 'date':
				case 'link_to_object_type':
					return $cache[$fieldId] = 'int_val';

				case 'counter':
					return $cache[$fieldId] = 'cnt';

				case 'price':
				case 'float':
					return $cache[$fieldId] = 'float_val';
				case 'img_file':
					return $cache[$fieldId] = 'img_file';
				case 'text':
				case 'wysiwyg':
				case 'file':
				case 'swf_file':
				case 'video_file':
					return $cache[$fieldId] = 'text_val';

				case 'relation':
					return $cache[$fieldId] = 'rel_val';

				case 'symlink':
					return $cache[$fieldId] = 'tree_val';

				case 'multiple_image':
					return $cache[$fieldId] = 'multiple_image';

				case 'domain_id':
				case 'domain_id_list':
					return $cache[$fieldId] = 'domain_id';

				case 'offer_id':
				case 'offer_id_list':
					return $cache[$fieldId] = 'offer_id';

				case 'optioned':
					return false;

				default:
					throw new selectorException("Unsupported field type \"{$field->getDataType()}\"");
			}
		}

		protected function escapeValue($value) {
			if (is_array($value)) {
				foreach ($value as $i => $val) {
					$value[$i] = $this->escapeValue($val);
				}
				return $value;
			}
			if ($value instanceof selector) {
				return $this->escapeValue($value->result());
			}
			if ($value instanceof iUmiObject || $value instanceof iUmiHierarchyElement) {
				return $value->id;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			return "'" . $connection->escape($value) . "'";
		}

		protected function buildRootQuery($mode) {

			$limitSql = $this->buildLimit();
			$orderSql = $this->buildOrder();
			$whereSql = $this->buildWhere();
			$tablesSql = $this->buildTables();
			$optionsSql = $this->buildOptions($mode);

			$types = [];
			foreach ($this->selector->types as $type) {
				if ($type->hierarchyType) {
					$types[] = $type->hierarchyType->getId();
				}
			}
			$typesSql = implode(', ', $types);

			$columnsSql = ($mode == 'result') ? 'DISTINCT h.id' : 'COUNT(DISTINCT h.id)';

			$sql = <<<SQL
SELECT $columnsSql
	FROM cms3_hierarchy hp, {$tablesSql}
	{$whereSql}
	AND (h.rel = 0 OR (h.rel = hp.id AND hp.type_id NOT IN ({$typesSql})))
		{$orderSql}
		{$limitSql}
SQL;
			return $sql;
		}

		/**
		 * Удаляет вложенные страницы
		 * @param array $idList массив идентификаторов
		 *
		 * [
		 *      'id страницы' => 'id родительской страницы'
		 * ]
		 *
		 * @example:
		 *
		 * [
		 *      1 => 0,
		 *      2 => 0,
		 *      3 => 2
		 * ]
		 *
		 * =>
		 *
		 * [
		 *      1,
		 *      2
		 * ]
		 *
		 * @return array
		 */
		protected function excludeNestedPages(array $idList) {
			$parentList = [];

			foreach ($idList as $id => $parentId) {
				if (!isset($idList[$parentId])) {
					$parentList[] = $id;
				}
			}

			return $parentList;
		}

		/**
		 * Добавляет таблицы, которые нужно подключить к выборке по заданным условиям
		 * @param array (table => condition) $leftJoins таблицы с условиями
		 */
		private function setLeftJoins(array $leftJoins) {
			$this->leftJoins = array_merge($this->leftJoins, $leftJoins);
		}

		/**
		 * Возвращает таблицы, которые нужно подключить к выборке по заданным условиям
		 * @return array(table => condition)
		 */
		private function getLeftJoins() {
			return $this->leftJoins;
		}

		/**
		 * Возвращает значение свойства или поля объекта
		 * @param string $propertyName идентификатор поля или обозначение свойства
		 * @param iUmiObject $object объект
		 * @return mixed
		 */
		private function getObjectValue($propertyName, iUmiObject $object) {
			switch ($propertyName) {
				case 'id': {
					return $object->getId();
				}
				case 'guid': {
					return $object->getGUID();
				}
				case 'name': {
					return $object->getName();
				}
				case 'is_locked': {
					return $object->getIsLocked();
				}
				case 'type_id': {
					return $object->getTypeId();
				}
				case 'owner_id': {
					return $object->getOwnerId();
				}
				case 'ord': {
					return $object->getOrder();
				}
				case 'update_time': {
					return $object->getUpdateTime();
				}
				default : {
					$value = $object->getValue($propertyName);
					return $value ?: false;
				}
			}
		}

		/**
		 * Возвращает значение свойства или поля страницы
		 * @param string $propertyName идентификатор поля или обозначение свойства
		 * @param iUmiHierarchyElement $page страница
		 * @return mixed
		 */
		private function getPageValue($propertyName, iUmiHierarchyElement $page) {
			switch ($propertyName) {
				case 'id': {
					return $page->getId();
				}
				case 'parent_id': {
					return $page->getParentId();
				}
				case 'type_id': {
					return $page->getTypeId();
				}
				case 'lang_id': {
					return $page->getLangId();
				}
				case 'domain_id': {
					return $page->getDomainId();
				}
				case 'obj_id': {
					return $page->getObjectId();
				}
				case 'ord': {
					return $page->getOrd();
				}
				case 'tpl_id': {
					return $page->getTplId();
				}
				case 'alt_name': {
					return $page->getAltName();
				}
				case 'is_active': {
					return $page->getIsActive();
				}
				case 'is_deleted': {
					return $page->getIsDeleted();
				}
				case 'is_visible': {
					return $page->getIsVisible();
				}
				case 'update_time': {
					return $page->getUpdateTime();
				}
				case 'is_default': {
					return $page->getIsDefault();
				}
				case 'name': {
					return $page->getName();
				}
				case 'obj_type_id': {
					return $page->getObjectTypeId();
				}
				default : {
					$value = $page->getValue($propertyName);
					return $value ?: false;
				}
			}
		}
	}
