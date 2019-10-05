<?php

	use UmiCms\Service;

	/**
	 * Класс для фильтрации по индексу. Умеет фильтровать как искомые сущности, так и сами данные фильтров.
	 * Варианты использования:
	 *
	 * 1) Получить данные для построения фильтра:
	 *
	 *   $queriesMaker = new FilterQueriesMaker($indexGenerator);
	 *   $queriesMaker->setFilteredFieldsNames('price', 'weight');
	 *   $queriesMaker->parseFilters();
	 *   $filterData = $queriesMaker->getFiltersData();
	 *
	 * 2) Получить идентификаторы отфильтрованных сущностей:
	 *
	 *   $queriesMaker = new FilterQueriesMaker($indexGenerator);
	 *   $queriesMaker->setFilteredFieldsNames('price', 'weight');
	 *   $queriesMaker->parseFilters();
	 *   $entitiesIds = $queriesMaker->getFilteredEntitiesIds();
	 */
	class FilterQueriesMaker {

		/** @var string $tableName имя таблицы в бд, которая хранит индекс фильтров */
		protected $tableName;

		/** @var string $entitiesType тип индексируемых сущностей (pages|objects) */
		protected $entityType;

		/** @var array|null объекты фильтруемых полей, данные которых составляют индекс */
		protected $allFilteredFields;

		/** @var array данные, по которым производится фильтрация */
		protected $filters = [];

		/** @var array идентификаторы страниц, которые являются родительскими для искомых страниц */
		protected $parentsIds = [];

		/** @var array идентификаторы типов данных, к которым относятся искомые сущности */
		protected $typeIds = [];

		/** @var array идентификаторы языков, к которым относятся искомые страницы */
		protected $langIds = [];

		/** @var array идентификаторы доменов, к которым относятся искомые страницы */
		protected $domainIds = [];

		/** @var bool игнорировать виртуальные копии страниц при фильтрации */
		protected $ignoreVirtualCopies = false;

		/** @var bool игнорировать права на просмотр страниц при фильтрации */
		protected $ignorePermissions;

		/** @var array гуиды полей, по которым необходимо строить фильтр */
		protected $filteredFieldsName = [];

		/** @var int ограничение на количество фильтруемых сущностей */
		protected $limit;

		/** @var int смещение выборки фильтруемых сущностей */
		protected $offset;

		/** @var int количество сущностей, которое будет получено в результате фильтрации */
		protected $filteredEntitiesCount = 0;

		/** @var bool возвращать варианты значений для полей, поддерживающих диапазонные значения */
		protected $showAllValuesInRangedFields = false;

		/** @var bool возвращать информацию о том, какие значения выбраны в фильтре */
		protected $showSelectedValues = true;

		/** @var bool уточнять данные фильтрации, на основе выбранных значений в фильтре */
		protected $updateSelectedFilters = true;

		protected $selectedFilters = [];

		/**
		 * Конструктор
		 * @param FilterIndexGenerator $indexGenerator индекс фильтров
		 * @throws publicAdminException
		 */
		public function __construct(FilterIndexGenerator $indexGenerator) {
			$this->tableName = $indexGenerator->getTableName();
			$this->entityType = $indexGenerator->getEntitiesType();
			$this->allFilteredFields = $indexGenerator->getFilteredFields();
			$this->ignorePermissions = (bool) mainConfiguration::getInstance()
				->get('kernel', 'ignore-permissions-in-filter');
		}

		/**
		 * Устанавливает идентификаторы страниц, которые являются родительскими для искомых страниц
		 * @param array $parentIds идентификаторы страниц
		 */
		public function setParentIds(array $parentIds) {
			$this->parentsIds = array_map('intval', $parentIds);
		}

		/**
		 * Устанавливает идентификаторы типов данных, к которым относятся искомые сущности
		 * @param array $typeIds идентификаторы типов данных
		 */
		public function setTypeIds(array $typeIds) {
			$this->typeIds = array_map('intval', $typeIds);
		}

		/**
		 * Устанавливает идентификаторы языков, к которым относятся искомые страницы
		 * @param array $langIds идентификаторы языков
		 */
		public function setLangIds(array $langIds) {
			$this->langIds = array_map('intval', $langIds);
		}

		/**
		 * Устанавливает идентификаторы доменов, к которым относятся искомые страницы
		 * @param array $domainIds идентификаторы доменов
		 */
		public function setDomainIds(array $domainIds) {
			$this->domainIds = array_map('intval', $domainIds);
		}

		/**
		 * Включает игнорирование виртуальных копий в результате фильтрации страниц
		 */
		public function ignoreVirtualCopies() {
			$this->ignoreVirtualCopies = true;
		}

		/**
		 * Устанавливает статус игнорирования прав на просмотр при фильтрации страниц
		 * @param bool $isIgnored статус
		 */
		public function setIgnorePermissionsStatus($isIgnored) {
			$this->ignorePermissions = (bool) $isIgnored;
		}

		/**
		 * Устанавливает гуиды полей, по которым необходимо строить фильтр
		 * @param array $filteredFieldsNames массив с гуидами полей
		 */
		public function setFilteredFieldsNames(array $filteredFieldsNames) {
			$allFilteredFields = $this->getAllFilteredFields();

			foreach ($filteredFieldsNames as $key => $value) {
				if (!isset($allFilteredFields[$value])) {
					unset($filteredFieldsNames[$key]);
				}
			}

			$this->filteredFieldsName = $filteredFieldsNames;
		}

		/**
		 * Устанавливает ограничение на количество фильтруемых сущностей
		 * @param int $limit ограничение на количество
		 * @throws publicAdminException если передан некорректный $limit
		 */
		public function setLimit($limit) {
			if (!is_numeric($limit)) {
				throw new publicAdminException(__METHOD__ . ': correct limit expected,' . $limit . ' given');
			}
			$this->limit = (int) $limit;
		}

		/**
		 * Устанавливает смещение выборки фильтруемых сущностей
		 * @param int $offset смещение выборки
		 * @throws publicAdminException если передан некорректный $offset
		 */
		public function setOffset($offset) {
			if (!is_numeric($offset)) {
				throw new publicAdminException(__METHOD__ . ': correct offset expected,' . $offset . ' given');
			}
			$this->offset = (int) $offset;
		}

		/**
		 * Включает отдачу вариантов значений для полей, поддерживающих диапазонные значения
		 */
		public function setShowAllValuesInRangedFields() {
			$this->showAllValuesInRangedFields = true;
		}

		/**
		 * Возвращает статус игнорирования прав на просмотр при фильтрации страниц
		 * @return bool
		 */
		public function isPermissionsIgnored() {
			return $this->ignorePermissions;
		}

		/**
		 * Разбирает GET и устанавливает данные, по которым производится фильтрация
		 * @return bool
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function parseFilters() {
			$allFilteredFields = $this->getAllFilteredFields();
			$filters = getRequest('filter');

			$event = new umiEventPoint('parse_filter');
			$event->setMode('before');
			$event->addRef('raw_filter', $filters);
			$event->setParam('queries_maker', $this);
			$event->call();

			if (!$filters || !is_array($filters)) {
				return $this->setFilters([]);
			}

			$isNeedToShowSelectedValues = $this->isNeedToShowSelectedValues();

			foreach ($filters as $key => $value) {
				if (!isset($allFilteredFields[$key])) {
					unset($filters[$key]);
					continue;
				}
				/* @var iUmiField $field */
				$field = $allFilteredFields[$key];
				if ($field->getDataType() == 'date') {
					foreach ($value as $type => $date) {
						if (!is_numeric($date) && is_string($date)) {
							$value[$type] = strtotime($date);
						}
					}
				}
				$condition = $this->getCondition($field, $key, $value);

				if ($condition === null) {
					unset($filters[$key]);
				} else {
					$filters[$key] = $condition;
				}

				if (!$isNeedToShowSelectedValues) {
					continue;
				}
				if (is_array($value)) {
					$value = array_map('htmlspecialchars', $value);
				} else {
					$value = [htmlspecialchars($value)];
				}
				$this->pushSelectedFilterValue($key, $value);
			}

			$event->setMode('after');
			$event->addRef('processed_filter', $filters);
			$event->call();

			return $this->setFilters($filters);
		}

		/**
		 * Возвращает данные, по которым производится фильтрация
		 * @return array
		 */
		public function getFilters() {
			return $this->filters;
		}

		/**
		 * Возвращает гуиды полей, по которым производится фильтрация
		 * @return array
		 */
		public function getFiltersFieldsNames() {
			return array_keys($this->filters);
		}

		/**
		 * Возвращает количество сущностей, которое будет получено в результате фильтрации
		 * @return int
		 */
		public function getFilteredEntitiesCount() {
			return $this->filteredEntitiesCount;
		}

		/**
		 * Отключает отдачу информацию о том, какие значения выбраны в фильтре
		 */
		public function disableShowingSelectedValues() {
			$this->showSelectedValues = false;
		}

		/**
		 * Отключает уточнение данных фильтрации, на основе выбранных значений в фильтре
		 */
		public function disableUpdatingSelectedFilters() {
			$this->updateSelectedFilters = false;
		}

		/**
		 * Возвращает идентификаторы сущностей, найденных в результате фильтрации.
		 * @return array
		 * @throws publicAdminException если запрос к бд завершился ошибкой
		 * @throws Exception
		 */
		public function getFilteredEntitiesIds() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$tableName = $this->getTableName();
			$systemConditions = $this->getSystemConditions();
			$filterConditions = $this->getFiltersConditions();
			$groupCondition = $this->getGroupConditions();
			$limitCondition = $this->getLimitCondition();
			$joinsConditions = $this->getJoinsConditions();
			$whereCommand = 'WHERE';

			if ($systemConditions == '' && $filterConditions == '') {
				$whereCommand = '';
			}

			if ($filterConditions !== '' && $systemConditions !== '') {
				$systemConditions = $systemConditions . ' AND ';
			}

			$sql = <<<SQL
SELECT `id` FROM `$tableName` $joinsConditions $whereCommand
$systemConditions $filterConditions $groupCondition $limitCondition ORDER BY `id`;
SQL;

			try {
				$result = $connection->queryResult($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return [];
			}

			$entitiesIds = [];
			foreach ($result as $row) {
				$entitiesIds[] = $row['id'];
			}
			return $entitiesIds;
		}

		/**
		 * Возвращает данные для построения фильтров
		 * @return array
		 * @throws publicAdminException если запрос к бд завершился ошибкой
		 * @throws Exception
		 */
		public function getFiltersData() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$tableName = $this->getTableName();
			$systemConditions = $this->getSystemConditions();
			$filterConditions = $this->getFiltersConditions();
			$joinsConditions = $this->getJoinsConditions();
			$selectConditions = $this->getSelectConditions();
			$groupCondition = $this->getGroupConditions();

			if ($filterConditions !== '' && $systemConditions !== '') {
				$systemConditions = $systemConditions . ' AND ';
			}

			$sql = <<<SQL
SELECT DISTINCT $selectConditions FROM `$tableName` $joinsConditions
WHERE $systemConditions $filterConditions $groupCondition
SQL;
			try {
				$result = $connection->queryResult($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return [];
			}

			$filtersData = [];
			foreach ($result as $row) {
				$filtersData[] = $row;
			}
			return $this->prepareFilterData($filtersData);
		}

		/**
		 * Возвращает условие для запроса, на основе переданных данных фильтрации,
		 * либо null, если условие не удалось построить.
		 * @param iUmiField $field объект фильтруемого поля
		 * @param string $filterName имя фильтруемого поля
		 * @param mixed $filterValue значение фильтруемого поля
		 * @return null|string
		 * @throws publicAdminException если у переданного $field некорректный тип данных
		 * @throws Exception
		 */
		protected function getCondition(iUmiField $field, $filterName, $filterValue) {
			switch ($field->getDataType()) {
				case 'string':
				case 'color':
				case 'password': {
					return $this->getMultiplyCondition($filterValue, $filterName);
				}
				case 'date':
				case 'int':
				case 'price':
				case 'float':
				case 'link_to_object_type':
				case 'counter': {
					return $this->getNumericCondition($filterValue, $filterName);
				}
				case 'boolean':
				case 'file':
				case 'img_file':
				case 'swf_file':
				case 'multiple_image':
				case 'video_file': {
					return $this->getBooleanCondition($filterValue, $filterName);
				}
				case 'optioned':
				case 'tags':
				case 'symlink': {
					return $this->getMultiplyCondition($filterValue, $filterName, true);
				}
				case 'text':
				case 'wysiwyg': {
					return $this->getMultiplyCondition($filterValue, $filterName, true);
				}
				case 'relation': {
					/* @var iUmiFieldType $fieldType */
					$fieldType = $field->getFieldType();
					if ($fieldType->getIsMultiple()) {
						return $this->getMultiplyCondition($filterValue, $filterName, true);
					}

					return $this->getMultiplyCondition($filterValue, $filterName);
				}
				default: {
					throw new publicAdminException(__METHOD__ . ': unsupported field type: ' . $field->getDataType());
				}
			}
		}

		/**
		 * Возвращает условие для запроса, на основе переданных данных фильтрации для числовых полей,
		 * либо null, если условие не удалось построить.
		 * @param mixed $value значение для условия запроса
		 * @param string $column имя столбца таблицы индекса
		 * @return null|string
		 */
		protected function getNumericCondition($value, $column) {
			if (is_numeric($value)) {
				return "`$column` = $value";
			}
			if (is_array($value) && umiCount($value) <= 2) {
				switch (true) {
					case isset($value['from']) && isset($value['to']): {
						$from = $value['from'];
						$to = $value['to'];
						if (is_numeric($from) && is_numeric($to)) {
							return " (`$column` BETWEEN $from AND $to)";
						}
						break;
					}
					case isset($value['from']): {
						$from = $value['from'];
						if (is_numeric($from)) {
							return " `$column` >= $from ";
						}
						break;
					}
					case isset($value['to']): {
						$to = $value['to'];
						if (is_numeric($to)) {
							return " `$column` <= $to ";
						}
						break;
					}
				}
			}
			return null;
		}

		/**
		 * Возвращает условие для запроса, на основе переданных данных фильтрации для строковых полей,
		 * либо null, если условие не удалось построить.
		 * @param mixed $value значение для условия запроса
		 * @param string $column имя столбца таблицы индекса
		 * @param bool $fullText искать значение по вхождению подстроки
		 * @return null|string
		 * @throws Exception
		 */
		protected function getStringCondition($value, $column, $fullText = false) {
			if (!is_string($value) || $value == '') {
				return null;
			}
			$value = $this->prepareString($value, $fullText);

			return $fullText ? " `$column` LIKE $value " : " `$column` = $value ";
		}

		/**
		 * Возвращает условие для запроса, на основе переданных данных фильтрации для булевых полей,
		 * либо null, если условие не удалось построить.
		 * @param mixed $value значение для условия запроса
		 * @param string $column имя столбца таблицы индекса
		 * @return null|string
		 */
		protected function getBooleanCondition($value, $column) {
			if (!is_numeric($value)) {
				return null;
			}
			$value = (int) ((bool) $value);
			return " `$column` = $value ";
		}

		/**
		 * Возвращает условие для запроса, на основе переданных данных фильтрации для полей со множественным значением,
		 * либо null, если условие не удалось построить.
		 * @param mixed $value значение для условия запроса
		 * @param string $column имя столбца таблицы индекса
		 * @param bool $fullText искать значение по вхождению подстроки
		 * @return null|string
		 * @throws Exception
		 */
		protected function getMultiplyCondition($value, $column, $fullText = false) {
			if (is_string($value)) {
				return $this->getStringCondition($value, $column, $fullText);
			}

			if (!is_array($value)) {
				return null;
			}

			if (umiCount($value) === 1) {
				return $this->getStringCondition(array_pop($value), $column, $fullText);
			}

			$valueList = [];
			/** @var array $value */
			foreach ($value as $option) {
				$optionCondition = $this->getStringCondition($option, $column, $fullText);
				if ($optionCondition !== null) {
					$valueList[] = $optionCondition;
				}
			}

			if (umiCount($valueList) === 0) {
				return null;
			}

			return '(' . implode(' OR ', $valueList) . ')';
		}

		/**
		 * Подготавливает строковое значения для его вставки в запрос
		 * @param string $string строковое значение
		 * @param bool $fullText искать значение по вхождению подстроки
		 * @return string
		 * @throws Exception
		 */
		protected function prepareString($string, $fullText = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$string = urldecode($string);
			$string = $connection->escape($string);
			$delimiter = $fullText ? '%' : '';
			$separator = FilterIndexGenerator::MULTIPLE_VALUE_SEPARATOR;
			$string = $fullText ? sprintf('%s%s%s', $separator, $string, $separator) : $string;
			return "'" . $delimiter . $string . $delimiter . "'";
		}

		/**
		 * Подготавливает данные для построения фильтров
		 * @param array $filtersData данные фильтров, полученные из бд
		 * @return array
		 */
		protected function prepareFilterData(array $filtersData) {
			$fieldsData = [];
			$fieldCache = [];
			$filteredEntitiesIds = [];
			$allFilteredFields = $this->getAllFilteredFields();

			foreach ($allFilteredFields as $key => $field) {
				$fieldType = $field->getFieldType();
				$fieldCache[$key] = [
					'field' => $field,
					'fieldType' => $fieldType,
					'dataType' => $field->getDataType(),
					'isMultiple' => $fieldType->getIsMultiple(),
				];
			}

			foreach ($filtersData as $entityData) {
				foreach ($entityData as $key => $value) {
					if ($value === null) {
						continue;
					}

					if ($key === 'id') {
						$filteredEntitiesIds[] = $value;
						continue;
					}

					if (!$fieldCache[$key]['isMultiple']) {
						$fieldsData[$key]['values'][] = $value;
						continue;
					}

					$value = trim($value, FilterIndexGenerator::MULTIPLE_VALUE_SEPARATOR);
					$newValues = explode(FilterIndexGenerator::MULTIPLE_VALUE_SEPARATOR, $value);

					if (isset($fieldsData[$key]['values']) && is_array($fieldsData[$key]['values'])) {

						foreach ($newValues as $newValue) {
							$fieldsData[$key]['values'][] = $newValue;
						}
					} else {
						$fieldsData[$key]['values'] = $newValues;
					}
				}
			}

			$filteredEntitiesIds = array_unique($filteredEntitiesIds);
			$filteredEntitiesCount = umiCount($filteredEntitiesIds);
			$this->setFilteredEntitiesCount($filteredEntitiesCount);

			$rangedFieldsTypes = $this->getRangedFieldsTypes();
			$isNeedToShowAllRangedValues = $this->isNeedToShowAllRangedValues();
			$isNeedToShowSelectedValue = $this->isNeedToShowSelectedValues();
			$selectedFilters = $this->getSelectedFilters();

			foreach ($fieldsData as $key => $value) {
				if (is_array($value['values'])) {
					$fieldsData[$key]['values'] = array_unique($value['values']);
				}

				/* @var iUmiField $filteredField */
				$filteredField = $fieldCache[$key]['field'];

				if (umiCount($fieldsData[$key]['values']) > 1 &&
					in_array($fieldCache[$key]['dataType'], $rangedFieldsTypes)) {
					$values = $fieldsData[$key]['values'];
					if (!$isNeedToShowAllRangedValues) {
						unset($fieldsData[$key]['values']);
					}
					sort($values);
					$fieldsData[$key]['values']['min'] = array_shift($values);
					$fieldsData[$key]['values']['max'] = array_pop($values);
				}

				if ($isNeedToShowSelectedValue && isset($selectedFilters[$key])) {
					$fieldsData[$key]['selected'] = $selectedFilters[$key];
				}

				$fieldsData[$key]['field'] = $filteredField;
				$fieldsData[$key]['type'] = $fieldCache[$key]['fieldType'];
			}

			return $fieldsData;
		}

		/**
		 * Возвращает название типов данных полей, которые поддерживают диапазонные значения
		 * @return array
		 */
		protected function getRangedFieldsTypes() {
			return ['date', 'int', 'price', 'float', 'counter'];
		}

		/**
		 * Возвращает часть запроса, отвечающую за определение набора искомых столбцов
		 * @return string
		 * @throws publicAdminException если не удалось получить гуиды полей, по которым строится фильтр
		 */
		protected function getSelectConditions() {
			$filteredFieldsNames = $this->getFilteredFieldsNames();
			if (umiCount($filteredFieldsNames) == 0) {
				throw new publicAdminException(__METHOD__ . ': filtering fields expected');
			}

			if ($this->isNeedToUpdateSelectedFilters()) {
				$selectedFilters = $this->getFiltersFieldsNames();
				$filteredFieldsNames = array_diff($filteredFieldsNames, $selectedFilters);
			}

			$selectCondition = '`id`';

			if (umiCount($filteredFieldsNames) > 0) {
				$selectCondition .= ', `' . implode('`, `', $filteredFieldsNames) . '`';
			}

			return $selectCondition;
		}

		/**
		 * Возвращает часть запроса, отвечающую за группировку
		 * @return string
		 */
		protected function getGroupConditions() {
			if ($this->getEntityType() == 'pages' && $this->isVirtualCopiesIgnored()) {
				return 'GROUP BY `obj_id`';
			}
			return '';
		}

		/**
		 * Возвращает часть запроса, отвечающую за ограничение по количеству и позициии искомых строк
		 * @return string
		 */
		protected function getLimitCondition() {
			$limit = $this->getLimit();
			$offset = $this->getOffset();
			switch (true) {
				case $limit !== null && $offset !== null: {
					return "LIMIT $offset, $limit";
				}
				case $limit !== null: {
					return "LIMIT 0, $limit";
				}
				default: {
					return '';
				}
			}
		}

		/**
		 * Возвращает часть запроса, отвечающую за условие для столбцов, представляющих системные поля
		 * @return string
		 */
		protected function getSystemConditions() {
			$conditions = [];

			if ($this->getEntityType() == 'pages') {
				$parentIds = $this->getParentIds();
				if (umiCount($parentIds) > 0) {
					$conditions[] = '`parent_id` in (' . implode(', ', $parentIds) . ')';
				}
				$typeIds = $this->getTypeIds();
				if (umiCount($typeIds) > 0) {
					$conditions[] = '`type_id` in (' . implode(', ', $typeIds) . ')';
				}
				$langIds = $this->getLangIds();
				if (umiCount($langIds) > 0) {
					$conditions[] = '`lang_id` in (' . implode(', ', $langIds) . ')';
				}
				$domainIds = $this->getDomainIds();
				if (umiCount($domainIds) > 0) {
					$conditions[] = '`domain_id` in (' . implode(', ', $domainIds) . ')';
				}
				$permissionCondition = $this->getPermissionsCondition();
				if (!$permissionCondition == '') {
					$conditions[] = $permissionCondition;
				}
			} else {
				$typeIds = $this->getTypeIds();
				if (umiCount($typeIds) > 0) {
					$conditions[] = '`type_id` in (' . implode(', ', $typeIds) . ')';
				}
			}

			if (umiCount($conditions) > 0) {
				return implode(' AND ', $conditions);
			}

			return '';
		}

		/**
		 * Возвращает часть запроса, отвечающую за JOIN'ы
		 * @return string
		 */
		protected function getJoinsConditions() {
			if ($this->isPermissionsIgnored()) {
				return '';
			}

			$permissions = permissionsCollection::getInstance();
			$userId = Service::Auth()->getUserId();

			if ($permissions->isSv($userId)) {
				return '';
			}

			$tableName = $this->getTableName();

			return "LEFT JOIN `cms3_permissions` as cp on $tableName.id = cp.rel_id";
		}

		/**
		 * Возвращает часть запроса, отвечающую за условие наличия прав на просмотр для текущего пользователя
		 * @return string
		 */
		protected function getPermissionsCondition() {
			if ($this->isPermissionsIgnored()) {
				return '';
			}

			$permissions = permissionsCollection::getInstance();
			$userId = Service::Auth()->getUserId();

			if ($permissions->isSv($userId)) {
				return '';
			}

			return $permissions->makeSqlWhere($userId);
		}

		/**
		 * Возвращает гуиды полей, по которым необходимо строить фильтр
		 * @return array
		 */
		protected function getFilteredFieldsNames() {
			return $this->filteredFieldsName;
		}

		/**
		 * Возвращает идентификаторы страниц, которые являются родительскими для искомых страниц
		 * @return array
		 */
		protected function getParentIds() {
			return $this->parentsIds;
		}

		/**
		 * Возвращает идентификаторы типов данных, к которым относятся искомые сущности
		 * @return array
		 */
		protected function getTypeIds() {
			return $this->typeIds;
		}

		/**
		 * Возвращает идентификаторы языков, к которым относятся искомые страницы
		 * @return array
		 */
		protected function getLangIds() {
			return $this->langIds;
		}

		/**
		 * Возвращает идентификаторы доменов, к которым относятся искомые страницы
		 * @return array
		 */
		protected function getDomainIds() {
			return $this->domainIds;
		}

		/**
		 * Игнорируются ли виртуальные копии при фильтрации страниц
		 * @return bool
		 */
		protected function isVirtualCopiesIgnored() {
			return $this->ignoreVirtualCopies;
		}

		/**
		 * Нужно ли возвращает варианты значений полей, поддерживающие диапазонные значения
		 * @return bool
		 */
		protected function isNeedToShowAllRangedValues() {
			return $this->showAllValuesInRangedFields;
		}

		/**
		 * Возвращает объекты фильтруемых полей, данные которых составляют индекс
		 * @return iUmiField[]|null
		 */
		protected function getAllFilteredFields() {
			return $this->allFilteredFields;
		}

		/**
		 * Возвращает часть запроса, отвечающую за условие для пераданных значений фильтров
		 * @return array|string
		 */
		protected function getFiltersConditions() {
			$filters = $this->filters;

			if (umiCount($filters) > 0 && $this->isNeedToUpdateSelectedFilters()) {
				return implode(' AND ', $filters);
			}

			return '';
		}

		/**
		 * Устанавливает данные, по которым производится фильтрация
		 * @param array $filters данные, по которым производится фильтрация
		 * @return bool
		 */
		protected function setFilters(array $filters) {
			$this->filters = $filters;
			return true;
		}

		/**
		 * Возвращает тип индексируемых сущностей (pages|objects)
		 * @return string
		 */
		protected function getEntityType() {
			return $this->entityType;
		}

		/**
		 * Возвращает имя таблицы в бд, которая хранит индекс фильтров
		 * @return string
		 */
		protected function getTableName() {
			return $this->tableName;
		}

		/**
		 * Возвращает ограничение на количество фильтруемых сущностей
		 * @return int
		 */
		protected function getLimit() {
			return $this->limit;
		}

		/**
		 * Возвращает смещение выборки фильтруемых сущностей
		 * @return int
		 */
		protected function getOffset() {
			return $this->offset;
		}

		/**
		 * Устанавливает количество сущностей, которое будет получено в результате фильтрации
		 * @param int $count количество сущностей
		 */
		protected function setFilteredEntitiesCount($count) {
			$this->filteredEntitiesCount = (int) $count;
		}

		/**
		 * Нужно ли возвращать данные о том, какие значения выбраны в фильтре
		 * @return bool
		 */
		protected function isNeedToShowSelectedValues() {
			return $this->showSelectedValues;
		}

		/**
		 * Нужно ли обновлять данные фильтров, на основе переданных данных фильтрации
		 * @return bool
		 */
		protected function isNeedToUpdateSelectedFilters() {
			return $this->updateSelectedFilters;
		}

		/**
		 * Возвращает выбранные значения полей в фильтре
		 * @return array
		 */
		protected function getSelectedFilters() {
			return $this->selectedFilters;
		}

		/**
		 * Устанавливает выбранное значения поля в фильтре
		 * @param string $fieldName гуид поля
		 * @param mixed $fieldValue значение поля
		 */
		protected function pushSelectedFilterValue($fieldName, $fieldValue) {
			$this->selectedFilters[$fieldName] = $fieldValue;
		}

		/** @deprecated  */
		protected function getFilterOptionsConditions() {
			return '';
		}
	}
