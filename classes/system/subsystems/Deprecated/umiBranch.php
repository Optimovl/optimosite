<?php

	/** @deprecated  */
	class umiBranch {

		/** @const string DEFAULT_TABLE_NAME имя таблицы для хранения значений полей объектов по умолчанию */
		const DEFAULT_TABLE_NAME = 'cms3_object_content';

		static protected $branchedObjectTypes = false;

		/**
		 * Проанализировать текущее состояние контентных таблиц и сохранить в кеш
		 * @return array список типов данных, которых затронули изменения
		 * @throws coreException
		 */
		public static function saveBranchedTablesRelations() {
			$cacheDirPath = self::getCacheDirPath();
			$cacheFilePath = $cacheDirPath . self::getCacheFileName();
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			self::$branchedObjectTypes = [];

			clearstatcache();

			if (file_exists($cacheFilePath)) {
				unlink($cacheFilePath);
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SHOW TABLES LIKE 'cms3_object_content%'";
			$result = $connection->queryResult($sql);

			$branchedHierarchyTypes = [];

			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				if (preg_match('/cms3_object_content_([0-9]+)/', array_shift($row), $out)) {
					$branchedHierarchyTypes[] = (int) $out[1];
				}
			}

			$branchedObjectTypes = [];

			foreach ($branchedHierarchyTypes as $hierarchyTypeId) {
				$objectTypes = array_keys($objectTypesCollection->getTypesByHierarchyTypeId($hierarchyTypeId));
				if (is_array($objectTypes)) {
					foreach ($objectTypes as $objectTypeId) {
						$branchedObjectTypes[$objectTypeId] = $hierarchyTypeId;
					}
				}
			}

			if (is_dir($cacheDirPath) && is_writable($cacheDirPath)) {
				file_put_contents($cacheFilePath, serialize($branchedObjectTypes));
			}

			if (is_file($cacheFilePath)) {
				chmod($cacheFilePath, 0777);
			}

			return self::$branchedObjectTypes = $branchedObjectTypes;
		}

		/**
		 * Узнать, таблице с каким названием лежат данные для типа данных $objectTypeId
		 * @param int $objectTypeId id типа данных
		 * @return string название mysql-таблицы
		 */
		public static function getBranchedTableByTypeId($objectTypeId) {
			$branchedObjectTypes = self::$branchedObjectTypes;

			if (!is_array($branchedObjectTypes)) {
				$branchedObjectTypes = self::getBranchedTablesRelations();
			}

			if (isset($branchedObjectTypes[$objectTypeId])) {
				$hierarchyTypeId = $branchedObjectTypes[$objectTypeId];
				return 'cms3_object_content_' . $hierarchyTypeId;
			}

			return self::DEFAULT_TABLE_NAME;
		}

		/**
		 * Возвращает название таблицы, в которой хранятся значения полей объекта
		 * @param int $id идентификатор объекта
		 * @return string
		 * @throws Exception
		 */
		public static function getBranchedTableByObjectId($id) {
			$object = umiObjectsCollection::getInstance()
				->getObject($id);

			if ($object instanceof iUmiObject) {
				return self::getBranchedTableByTypeId($object->getTypeId());
			}

			return self::DEFAULT_TABLE_NAME;
		}

		public static function getBranchedTableByHierarchyTypeId($hierarchyTypeId) {
			$hierarchyTypeId = (int) $hierarchyTypeId;
			$branchedObjectTypesIds = self::$branchedObjectTypes;

			if (is_array($branchedObjectTypesIds) && in_array($hierarchyTypeId, $branchedObjectTypesIds)) {
				return 'cms3_object_content_' . $hierarchyTypeId;
			}

			return self::DEFAULT_TABLE_NAME;
		}

		/**
		 * Узнать, разделены ли данные с иерархическим типом $hierarchyTypeId
		 * @param int $hierarchyTypeId
		 * @return bool true, если разделены, false если данные лежат в общей таблице
		 */
		public static function checkIfBranchedByHierarchyTypeId($hierarchyTypeId) {
			$branchedObjectTypes = self::$branchedObjectTypes;

			if (!is_array($branchedObjectTypes)) {
				$branchedObjectTypes = self::getBranchedTablesRelations();
			}
			return (bool) in_array($hierarchyTypeId, $branchedObjectTypes);
		}

		/**
		 * Получить текущее состояние базы данных для принятия решения о необходимости branch/merge таблиц.
		 * @return array ассоциативный массив с распределением объектов (count) по hierarchy-type-id.
		 */
		public static function getDatabaseStatus() {
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$hierarchyTypesList = $umiHierarchyTypes->getTypesList();

			$result = [];

			/** @var iUmiHierarchyType $hierarchyType */
			foreach ($hierarchyTypesList as $hierarchyType) {
				$hierarchyTypeId = $hierarchyType->getId();
				$isBranched = self::checkIfBranchedByHierarchyTypeId($hierarchyTypeId);

				$sel = new selector('pages');
				$sel->types('hierarchy-type')->id($hierarchyTypeId);
				$count = $sel->length();

				$result[] = [
					'id' => $hierarchyTypeId,
					'isBranched' => $isBranched,
					'count' => $count
				];
			}

			return $result;
		}

		/**
		 * Загрузить из кеша данные о состоянии таблиц данных
		 * @return array список типов данных, которых затронули изменения
		 */
		protected static function getBranchedTablesRelations() {
			$filePath = self::getCacheDirPath() . self::getCacheFileName();

			if (is_file($filePath)) {
				$branchedObjectTypes = unserialize(file_get_contents($filePath));
				if (is_array($branchedObjectTypes)) {
					return self::$branchedObjectTypes = $branchedObjectTypes;
				}
			}

			return self::saveBranchedTablesRelations();
		}

		/**
		 * Возвращает имя файла с кешем
		 * @return string
		 */
		protected static function getCacheFileName() {
			return 'branchedTablesRelations.rel';
		}

		/**
		 * Возвращает путь до директории с кешем
		 * @return string
		 */
		protected static function getCacheDirPath() {
			return mainConfiguration::getInstance()
				->includeParam('system.runtime-cache');
		}
	}
