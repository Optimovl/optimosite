<?php

	/**
	 * Трейт общего функционала коллекций.
	 * Можно применять только в классах, удовлетворяющих интерфейсу iUmiDataBaseInjector.
	 */
	trait tCommonCollection {

		/**
		 * Создает и возвращает сущность, в соответствии с настройками
		 * @param array $params настройки
		 * @return iUmiCollectionItem
		 * @throws Exception
		 */
		public function create(array $params) {
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiCollection|iUmiConstantMapInjector|iClassConfigManager $this */
			$map = $this->getMap();
			/** @var ClassConfig $classConfig */
			$classConfig = self::getConfig();
			$fields = $values = [];
			$fieldsConfig = $this->getFieldsForCreation($classConfig->get('fields'));

			foreach ($fieldsConfig as $fieldConfig) {
				$fieldName = $this->getFieldName($fieldConfig);
				$fieldTitle = $this->getFieldTitle($fieldConfig);
				$this->checkInputForField(
					$fieldName,
					$fieldTitle,
					$params,
					$fieldConfig
				);
				$this->appendField($fieldName, $fields);
				$fieldValue = isset($params[$fieldName]) ? $params[$fieldName] : null;
				$fieldType = $map->get($fieldConfig['type']);
				$this->appendFieldValue(
					$fieldName,
					$fieldType,
					$fieldValue,
					$values,
					$classConfig
				);
			}

			try {
				$this->callback(
					'create-validate-callback',
					$classConfig,
					[
						$fields,
						$values,
						$fieldsConfig
					]
				);
			} catch (callbackNotFoundException $e) {
			}

			$connection = $this->getConnection();
			$tableName = $connection->escape($this->getTableName());
			$countFields = umiCount($fields);
			$countValue = umiCount($values);

			if ($countFields != $countValue) {
				throw new Exception('Wrong data given');
			}

			$fieldsCondition = (umiCount($fields) > 0) ? '(' . implode(', ', $fields) . ')' : '';
			$valuesCondition = '';

			foreach ($values as $value) {
				$valuesCondition .= ($value === null) ? 'NULL, ' : "'" . $value . "', ";
			}

			if (!empty($valuesCondition)) {
				$valuesCondition = rtrim($valuesCondition, ', ');
				$valuesCondition = 'VALUES(' . $valuesCondition . ')';
			}

			$connection->startTransaction("CREATE ENTITY IN $tableName");

			try {
				$sql = <<<SQL
INSERT INTO `$tableName` $fieldsCondition $valuesCondition;
SQL;
				$connection->query($sql);
				$values[$map->get('ID_FIELD_NAME')] = $connection->insertId();

				try {
					$values = $this->callback(
						'create-prepare-instancing-callback',
						$classConfig,
						[
							$fields,
							$values,
							$fieldsConfig,
							$params
						]
					);
				} catch (callbackNotFoundException $e) {
				}

				$itemClass = $this->getCollectionItemClass();
				/** @var iUmiDataBaseInjector|iUmiConstantMapInjector|iUmiCollectionItem $element */
				$element = new $itemClass($values, $map);
				$element->setConnection($connection);
				$element->setMap($map);

				try {
					$this->callback('create-after-callback', $classConfig, [$element]);
				} catch (callbackNotFoundException $e) {
				}
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();
			return $element;
		}

		/**
		 * Возвращает условия выборки
		 * @param array $params параметры выборки такого вида:
		 * $params = [
		 *  'offset' => 0,
		 *  'limit' => 10,
		 *  'calculate_only' => true,
		 *  // массив полей, для которых нужно использовать %like% при выборке
		 *  'like_mode' => [
		 *    'name' => true,
		 *    ...
		 *  ],
		 *  // массив полей, для которых нужно использовать указанный
		 *  // способ сравнения (один из один из ['>=', '<=', '>', '<', '=', '!='] )
		 *  'compare_mode' => [
		 *    'date' => '>=',
		 *    ...
		 *  ],
		 *  // параметры сортировки
		 *  'order' => [
		 *    'name' => 'asc'
		 *  ],
		 *  // значение поля
		 *  'name' => 'testName',
		 *  // массив значений поля
		 *  'id' => ['1', '2', '3']
		 *
		 * ]
		 * @return array
		 * @throws Exception
		 */
		public function getFieldsConditions(array $params) {
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiCollection|iUmiConstantMapInjector|iClassConfigManager $this */
			$map = $this->getMap();
			/** @var ClassConfig $classConfig */
			$classConfig = self::getConfig();
			$likeFields = [];
			$compareFields = [];

			if (isset($params[$map->get('LIKE_MODE_KEY')])) {
				$likeFields = (array) $params[$map->get('LIKE_MODE_KEY')];
			}

			if (isset($params[$map->get('COMPARE_MODE_KEY')])) {
				$compareFields = (array) $params[$map->get('COMPARE_MODE_KEY')];
			}

			$conditions = [];

			foreach ($classConfig->get('fields') as $fieldConfig) {
				$fieldName = $map->get($fieldConfig['name']);
				$fieldType = $map->get($fieldConfig['type']);
				$likeMode = false;
				$compareMode = false;

				if (isset($likeFields[$fieldName])) {
					$likeMode = $likeFields[$fieldName];
				}

				if (isset($fieldConfig['comparable']) && isset($compareFields[$fieldName])) {
					$compareMode = $compareFields[$fieldName];
				}

				if (array_key_exists($fieldName, $params)) {
					$conditions[] = $this->getFieldCondition(
						$fieldName,
						$params[$fieldName],
						$fieldType,
						$likeMode,
						$compareMode
					);
				}
			}

			return $conditions;
		}

		/** @inheritdoc */
		public function getById($id) {
			/** @var tCommonCollection|iUmiConstantMapInjector $this */
			return $this->getBy($this->getMap()->get('ID_FIELD_NAME'), $id);
		}

		/** @inheritdoc */
		public function get(array $params) {
			/** @var iUmiDataBaseInjector|iUmiCollection|tCommonCollection|iUmiConstantMapInjector $this */
			$connection = $this->getConnection();
			$selectionTarget = $this->getSelectionTarget($params);
			$fieldsConditions = $this->getFieldsConditions($params);
			$fieldsConditions = (umiCount($fieldsConditions) == 0) ? '' : 'WHERE ' . implode(' AND ', $fieldsConditions);
			$limitCondition = $this->getLimitCondition($params);
			$orderCondition = $this->getOrderCondition($params);

			$items = [];
			$tableName = $connection->escape($this->getTableName());

			$sql = <<<SQL
SELECT $selectionTarget
FROM `$tableName`
$fieldsConditions
$orderCondition
$limitCondition;
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return $items;
			}

			$itemClassName = $this->getCollectionItemClass();
			$map = $this->getMap();
			/** @var ClassConfig $classConfig */
			$classConfig = self::getConfig();
			$fieldsConfig = $classConfig->get('fields');
			$fieldsNames = array_keys($fieldsConfig);

			foreach ($result as $row) {
				try {
					$row = $this->callback(
						'get-prepare-instancing-callback',
						$classConfig,
						[
							$fieldsNames,
							$row,
							$fieldsConfig,
							$params
						]
					);
				} catch (callbackNotFoundException $e) {
				}
				/** @var iUmiCollectionItem|iUmiDataBaseInjector|iUmiConstantMapInjector $item */
				$item = new $itemClassName($row, $map);
				$item->setConnection($connection);
				$item->setMap($map);

				try {
					$this->callback('get-after-callback', $classConfig, [$item]);
				} catch (callbackNotFoundException $e) {
				}

				$items[] = $item;
			}

			return $items;
		}

		/**
		 * Возвращает количество сущностей, в соответствии с настройками
		 * @param array $params настройки
		 * @return int
		 */
		public function count(array $params) {
			/** @var iUmiDataBaseInjector|iUmiCollection|tCommonCollection|iUmiConstantMapInjector $this */
			if (!isset($params[$this->getMap()->get('CALCULATE_ONLY_KEY')])) {
				if (umiCount($params) > 0) {
					$this->get($params);
				}

				$connection = $this->getConnection();
				$countResult = $connection->queryResult('SELECT FOUND_ROWS()');
				$countResult->setFetchType(IQueryResult::FETCH_ROW);

				if ($countResult->length() > 0) {
					$fetchResult = $countResult->fetch();
					return array_shift($fetchResult);
				}

				return 0;
			}

			$connection = $this->getConnection();
			$fieldsConditions = $this->getFieldsConditions($params);
			$fieldsConditions = (umiCount($fieldsConditions) == 0) ? '' : 'WHERE ' . implode(' AND ', $fieldsConditions);
			$tableName = $connection->escape($this->getTableName());

			$sql = <<<SQL
SELECT count(*)
FROM `$tableName`
$fieldsConditions;
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$count = $result->fetch();

			return (int) array_shift($count);
		}

		/**
		 * Удаляет сущности, в соответствии с настройками
		 * @param array $params настройки
		 * @return bool
		 */
		public function delete(array $params) {
			/** @var iUmiDataBaseInjector|iUmiCollection|tCommonCollection $this */
			$connection = $this->getConnection();
			$fieldsConditions = $this->getFieldsConditions($params);
			$fieldsConditions = (umiCount($fieldsConditions) == 0) ? '' : 'WHERE ' . implode(' AND ', $fieldsConditions);
			$tableName = $connection->escape($this->getTableName());

			$sql = <<<SQL
DELETE FROM `$tableName`
$fieldsConditions;
SQL;

			$connection->query($sql);
			return true;
		}

		/**
		 * Удаляет сущность с заданным идентификатором
		 * @param int $id
		 * @return bool
		 */
		public function deleteById($id) {
			/** @var iUmiDataBaseInjector|iUmiCollection|tCommonCollection|iUmiConstantMapInjector $this */
			return $this->delete(
				[
					$this->getMap()->get('ID_FIELD_NAME') => $id
				]
			);
		}

		/** Удаляет все сущности */
		public function deleteAll() {
			return $this->delete([]);
		}

		/**
		 * Существуют ли сущности, соответствующие настройкам
		 * @param array $params настройки
		 * @return bool
		 */
		public function isExists(array $params) {
			$entities = $this->get($params);
			return umiCount($entities) > 0;
		}

		/**
		 * Возвращает данные сущностей, соответствующих настройкам
		 * @param array $params настройки
		 * @return array
		 */
		public function export(array $params = []) {
			$items = $this->get($params);
			$result = [];

			foreach ($items as $item) {
				$result[] = $item->export();
			}

			return $result;
		}

		/**
		 * Импортирует список сущностей и возвращает результат
		 * @param array $data данные сущностей
		 * @return array
		 */
		public function import(array $data) {
			/** @var iUmiDataBaseInjector|iUmiCollection|tCommonCollection|iUmiConstantMapInjector $this */
			$updateCounter = 0;
			$createdCounter = 0;
			$errors = [];
			$map = $this->getMap();
			$idField = $map->get('ID_FIELD_NAME');
			$createdKey = $map->get('CREATED_COUNTER_KEY');
			$updatedKey = $map->get('UPDATED_COUNTER_KEY');
			$errorsKey = $map->get('IMPORT_ERRORS_KEY');

			foreach ($data as $itemData) {
				$itemId = null;

				if (isset($itemData[$idField])) {
					$itemId = $itemData[$idField];
					unset($itemData[$idField]);
				}

				$item = null;

				try {
					if ($itemId !== null) {
						$items = $this->get(
							[
								$idField => $itemId
							]
						);

						$item = (umiCount($items) > 0) ? array_shift($items) : null;
					}

					if ($item instanceof iUmiCollectionItem) {
						$item->import($itemData);
						$item->commit();
						$updateCounter++;
						continue;
					}

					$this->create($itemData);
					$createdCounter++;
				} catch (Exception $e) {
					$errors[] = $e->getMessage();
					continue;
				}
			}

			return [
				$createdKey => $createdCounter,
				$updatedKey => $updateCounter,
				$errorsKey => $errors
			];
		}

		/**
		 * Возвращает условие выборки для фильтра по полю
		 * @param string $field название столбца
		 * @param mixed $value значение поля
		 * @param string $type тип значения поля
		 * @param bool $likeMode включен режим поиска по вхождению строк
		 * @param bool $compareMode режим сравнения для фильтрации, один из ['>=', '<=', '>', '<', '=', '!=']
		 * @return string
		 * @throws Exception
		 */
		public function getFieldCondition($field, $value, $type, $likeMode = false, $compareMode = false) {
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			$connection = $this->getConnection();
			$isMultiple = is_array($value);
			$map = $this->getMap();

			switch ($type) {
				case $map->get('STRING_FIELD_TYPE') : {
					$value = $isMultiple ? array_map([$connection, 'escape'], $value) : $this->getFieldValue($value, $type);
					break;
				}

				case $map->get('INTEGER_FIELD_TYPE') : {
					$value = $isMultiple ? array_map('intval', $value) : $this->getFieldValue($value, $type);
					break;
				}

				case $map->get('FLOAT_FIELD_TYPE') : {
					$value = $isMultiple ? array_map('floatval', $value) : $this->getFieldValue($value, $type);
					break;
				}

				case $map->get('IMAGE_FIELD_TYPE') : {
					$value = $isMultiple ? array_map([$this, 'getImageValue'], $value) : $this->getImageValue($value);
					break;
				}

				case $map->get('DATE_FIELD_TYPE') : {
					$value = $isMultiple ? array_map([$this, 'getDateValue'], $value) : $this->getDateValue($value);
					break;
				}

				default : {
					throw new Exception('Unsupported type given: ' . $type);
				}
			}

			$condition = $this->getField($field);
			$likeMode = ($likeMode && $type === 'string');
			$isNumeric = in_array(
				$type,
				[
					$map->get('INTEGER_FIELD_TYPE'),
					$map->get('FLOAT_FIELD_TYPE'),
					$map->get('DATE_FIELD_TYPE')
				]
			);

			$compareMode = $this->getCorrectCompareMode($compareMode);

			switch (true) {
				case $value === null && $compareMode == '=' : {
					$condition .= ' IS NULL';
					break;
				}

				case $value === null && $compareMode == '!=' : {
					$condition .= ' IS NOT NULL';
					break;
				}

				case $isMultiple && $likeMode : {
					$fieldCondition = $condition;
					$condition = '(' . $condition;
					$valueItem = array_shift($value);
					$condition .= " LIKE '%$valueItem%' ";

					foreach ($value as $valueItem) {
						$condition .= " OR $fieldCondition LIKE '%$valueItem%' ";
					}

					$condition .= ')';
					break;
				}

				case $isMultiple : {
					$condition .= " IN ('" . implode("', '", $value) . "')";
					break;
				}

				case !$isMultiple && $likeMode : {
					$condition .= " LIKE '%" . $value . "%' ";
					break;
				}

				case $isNumeric : {
					if ($compareMode !== false) {
						$condition .= " {$compareMode} '{$value}'";
						break;
					}

					$condition .= ' = ' . $value;
					break;
				}

				default : {
					$compareMode = ($compareMode == '!=') ? '!=' : '=';
					$condition .= " {$compareMode} '{$value}'";
				}
			}

			return $condition;
		}

		/**
		 * Возвращает режим сравнения, преобразованные для корректной работы с sql,
		 * либо false
		 * @param string $compareMode режим сравнения
		 * @return string|bool
		 */
		private function getCorrectCompareMode($compareMode) {
			$compareModeList = $this->getCompareModeList();

			switch (true) {
				case isset($compareModeList[$compareMode]) : {
					return $compareModeList[$compareMode];
				}
				case $compareModeIndex = array_search($compareMode, $compareModeList): {
					return $compareModeList[$compareModeIndex];
				}
				default : {
					return false;
				}
			}
		}

		/**
		 * Возвразает список режимов сравнения, корректных для работы с sql,
		 * с соответствующими алиасами
		 * @return array
		 */
		private function getCompareModeList() {
			return [
				'ge' => '>=',
				'le' => '<=',
				'gt' => '>',
				'lt' => '<',
				'eq' => '=',
				'ne' => '!='
			];
		}

		/**
		 * Возвращет режим сортировки для выборки
		 * @param mixed $orderMode значение режима сортировки
		 * @return string
		 * @throws Exception
		 */
		public function getOrderMode($orderMode) {
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			if (!is_string($orderMode)) {
				throw new Exception('Corrupted mode given: ' . $orderMode);
			}

			$connection = $this->getConnection();
			$map = $this->getMap();
			$orderMode = $connection->escape(mb_convert_case($orderMode, MB_CASE_UPPER));

			if (!in_array($orderMode, [$map->get('ORDER_DIRECTION_ASC'), $map->get('ORDER_DIRECTION_DESC')])) {
				throw new Exception('Unsupported mode given: ' . $orderMode);
			}

			return $orderMode;
		}

		/**
		 * Возвращает имя столбца таблицы для выборки
		 * @param string $field имя столбца
		 * @return string
		 * @throws Exception
		 */
		public function getField($field) {
			/** @var iUmiDataBaseInjector|tCommonCollection $this */
			$connection = $this->getConnection();
			$field = $connection->escape($field);
			return "`$field`";
		}

		/**
		 * Возвращает экранированное значение поля для выборки
		 * @param mixed $value значение
		 * @param string $type тип значения
		 * @return int|string|null
		 * @throws Exception
		 */
		public function getFieldValue($value, $type) {
			if ($value === null) {
				return $value;
			}

			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			$connection = $this->getConnection();
			$map = $this->getMap();
			switch ($type) {
				case $map->get('STRING_FIELD_TYPE') : {
					return $connection->escape($value);
				}
				case $map->get('INTEGER_FIELD_TYPE') : {
					return (int) $value;
				}
				case $map->get('FLOAT_FIELD_TYPE') : {
					return (float) $value;
				}
				case $map->get('IMAGE_FIELD_TYPE') : {
					if (!$value instanceof umiImageFile && is_string($value)) {
						$image = new umiImageFile($value);

						if ($image->getIsBroken() && !startsWith($value, '.')) {
							$image = new umiImageFile('.' . $value);
						}

						$value = $image;
					}

					if (!$value instanceof umiImageFile || $value->getIsBroken()) {
						throw new Exception('Broken image given');
					}

					return $this->getFieldValue($value->getFilePath(true), $map->get('STRING_FIELD_TYPE'));
				}
				case $map->get('DATE_FIELD_TYPE') : {
					if (!$value instanceof umiDate && is_numeric($value)) {
						$value = new umiDate((int) $value);
					}

					if (!$value instanceof umiDate) {
						throw new Exception('Broken image given');
					}

					return $this->getFieldValue($value->getDateTimeStamp(), $map->get('INTEGER_FIELD_TYPE'));
				}
				default : {
					throw new Exception('Unsupported type given: ' . $type);
				}
			}
		}

		/**
		 * Возвращает определение возвращаемых данных выборки
		 * @param array $params параметры выборки
		 * @return string
		 */
		public function getSelectionTarget(array $params) {
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			return isset($params[$this->getMap()->get('COUNT_KEY')]) ? ' SQL_CALC_FOUND_ROWS * ' : ' * ';
		}

		/**
		 * Возвращает выражение для сортировки результатов выборки
		 * @param array $params параметры выборки
		 * @return string
		 * @throws Exception
		 */
		public function getOrderCondition(array $params) {
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			$orderKey = $this->getMap()->get('ORDER_KEY');

			if (!isset($params[$orderKey]) || !is_array($params[$orderKey])) {
				return '';
			}

			$ordersFields = $params[$orderKey];
			$conditions = [];

			foreach ($ordersFields as $orderFieldKey => $orderFieldMode) {
				$conditions[] = $this->getField($orderFieldKey) . ' ' . $this->getOrderMode($orderFieldMode);
			}

			if (umiCount($conditions) == 0) {
				return '';
			}

			return ' ORDER BY ' . implode(', ', $conditions);
		}

		/**
		 * Возвращает ограничение выборки на количество
		 * @param array $params параметры выборки
		 * @return string
		 */
		public function getLimitCondition(array $params) {
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			$map = $this->getMap();
			$limitKey = $map->get('LIMIT_KEY');
			$offsetKey = $map->get('OFFSET_KEY');

			switch (true) {
				case isset($params[$limitKey], $params[$offsetKey]) : {
					$limit = (int) $params[$limitKey];
					$offset = (int) $params[$offsetKey];
					return "LIMIT $offset, $limit";
				}
				case isset($params[$limitKey]) : {
					$limit = (int) $params[$limitKey];
					return "LIMIT 0, $limit";
				}
				default : {
					return '';
				}
			}
		}

		/**
		 * Обновляет идентификаторы связанных сущостей.
		 * Если сущности были импортированы - у них могут измениться идентификаторы.
		 * @param array $properties данные импортируемой сущности
		 * @param string|int $sourceId идентификатор ресурса
		 * @return array
		 */
		public function updateRelatedId(array $properties, $sourceId) {
			return $properties;
		}

		/**
		 * Экранирует значение поля типа "Дата"
		 * @param mixed $value значение
		 * @return int|null
		 * @throws Exception
		 * @throws publicAdminException
		 */
		protected function getDateValue($value) {
			if ($value === null) {
				return $value;
			}

			if (!is_numeric($value) && !$value instanceof iUmiDate) {
				throw new publicAdminException('Wrong value for date given');
			}

			$value = ($value instanceof iUmiDate) ? $value->getDateTimeStamp() : $value;
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			return $this->getFieldValue($value, $this->getMap()->get('INTEGER_FIELD_TYPE'));
		}

		/**
		 * Экранирует значение поля типа "Изображение"
		 * @param mixed $value значение
		 * @return string|null
		 * @throws Exception
		 * @throws publicAdminException
		 */
		protected function getImageValue($value) {
			if ($value === null) {
				return $value;
			}

			if (!is_string($value) && !$value instanceof umiImageFile) {
				throw new publicAdminException('Wrong value for image given');
			}

			$value = ($value instanceof iUmiImageFile) ? $value : new umiImageFile($value);
			$path = '.' . $value->getFilePath(true);
			/** @var iUmiDataBaseInjector|tCommonCollection|iUmiConstantMapInjector $this */
			return $this->getFieldValue($path, $this->getMap()->get('STRING_FIELD_TYPE'));
		}

		/**
		 * Проверяет входные данные для поля
		 * @param string $fieldName имя поля
		 * @param string $fieldTitle заголовок поля
		 * @param array $params входные параметры
		 * @param array $config конфигурация поля
		 * @throws Exception
		 */
		protected function checkInputForField($fieldName, $fieldTitle, $params, $config) {
			if (
				(!isset($params[$fieldName]) || $params[$fieldName] === '') && isset($config['required']) &&
				$config['required'] === true
			) {
				throw new Exception("{$fieldTitle} expected");
			}
		}

		/**
		 * Добавляет поле в список
		 * @param string $fieldName имя поля
		 * @param array $fieldsList список полей
		 */
		protected function appendField($fieldName, &$fieldsList) {
			$fieldsList[] = $this->getField($fieldName);
		}

		/**
		 * Добавляет значения поля в список, возвращает добавленное значение
		 * @param string $fieldName имя поля
		 * @param string $fieldType тип поля
		 * @param string $fieldValue значение поля
		 * @param array $valuesList список полей
		 * @param array $config конфигурация
		 * @throws Exception
		 * @return mixed
		 */
		protected function appendFieldValue($fieldName, $fieldType, $fieldValue, &$valuesList, $config) {
			$escapedValue = $this->getFieldValue($fieldValue, $fieldType);

			try {
				return $valuesList[$fieldName] = $this->callback(
					'append-field-value-callback',
					$config,
					[
						$fieldName,
						$fieldType,
						$fieldValue,
						$escapedValue
					]
				);
			} catch (callbackNotFoundException $e) {
			}

			return $valuesList[$fieldName] = $escapedValue;
		}

		/**
		 * Возвращает заголовок поля
		 * @param array $config конфигурация поля
		 * @return string
		 */
		protected function getFieldTitle($config) {
			$fieldName = ucfirst($this->getFieldName($config));
			return isset($config['title']) ? $config['title'] : $fieldName;
		}

		/**
		 * Возвращает конфигурацию только тех полей,
		 * которые участвуют в создании элементов коллекции
		 * @param array $config конфигурация полей
		 * @return array
		 */
		protected function getFieldsForCreation($config) {
			$creationDirective = 'used-in-creation';
			return array_filter(
				$config,
				function ($field) use ($creationDirective) {
					return (!isset($field[$creationDirective]) || $field[$creationDirective]);
				}
			);
		}

		/**
		 * Возвращает имя поля
		 * @param array $config конфигурация поля
		 * @return string|null
		 */
		protected function getFieldName($config) {
			return (isset($config['name']) ? $this->getMap()->get($config['name']) : null);
		}

		/**
		 * Вызывает обработчики и возвращает их результаты
		 * @param string $name имя обработчика
		 * @param ClassConfig $config конфигурация
		 * @param array $args аргументны вызова обработчика
		 * @return array
		 * @throws Exception
		 */
		protected function callback($name, ClassConfig $config, array $args = []) {
			$callback = $config->get($name);

			if ($callback === null) {
				throw new callbackNotFoundException('Callback ' . $name . ' not found');
			}

			if (!is_array($callback) && is_callable([$this, $callback])) {
				return call_user_func_array([$this, $callback], $args);
			}

			$result = [];

			foreach ($callback as $method) {
				if (is_callable([$this, $method])) {
					$result[$method] = call_user_func_array([$this, $method], $args);
				}
			}

			return $result;
		}

		/**
		 * Обработчик метода tCommonCollection::create()#create-prepare-instancing-callback.
		 * Конвертирует пути до изображений в объекты umiImageFile в списке параметров для инициализации объекта
		 * класса элемента колллекции.
		 * @param array $fields имена полей
		 * @param array $values значения полей
		 * @param array $fieldsConfig настройки полей
		 * @return array
		 */
		protected function convertFilePathToUmiImage(array $fields, array $values, array $fieldsConfig) {
			/** @var tUmiConstantMapInjector|tUmiImageFileInjector $this */
			$map = $this->getMap();

			foreach ($fieldsConfig as $fieldConfig) {
				$fieldType = $map->get($fieldConfig['type']);
				$fieldName = $map->get($fieldConfig['name']);

				if ($fieldType === $map->get('IMAGE_FIELD_TYPE') && isset($values[$fieldName])) {
					$imageFileHandlerClass = $this->getImageFileHandler();
					$values[$fieldName] = new $imageFileHandlerClass($values[$fieldName]);
				}
			}

			return $values;
		}

		/**
		 * Устанавливает обработчик файлов изображений для сущности
		 * @param iUmiImageFileInjector $entity сущность
		 */
		protected function setImageFileHandlerToEntity(iUmiImageFileInjector $entity) {
			/** @var tUmiImageFileInjector $this */
			$entity->setImageFileHandler(
				$this->getImageFileHandler()
			);
		}

		/**
		 * Устанавливает коллекцию доменов для сущности
		 * @param iUmiDomainsInjector $entity сущность
		 */
		protected function setUmiDomainsInjectorToEntity(iUmiDomainsInjector $entity) {
			/** @var iUmiDomainsInjector $this */
			$entity->setDomainCollection(
				$this->getDomainCollection()
			);
		}

		/**
		 * Возвращает элемент коллекции по значению поля
		 * @param string $field имя поля
		 * @param mixed $value значение поля
		 * @return null
		 */
		protected function getBy($field, $value) {
			$items = $this->get([$field => $value]);

			if (umiCount($items) > 0) {
				return array_shift($items);
			}

			return null;
		}

	}
