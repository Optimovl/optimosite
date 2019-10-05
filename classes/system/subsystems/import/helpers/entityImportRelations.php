<?php
	use \iUmiImportRelations as iBaseSourceIdBinder;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Класс служит связующим звеном между импортируемыми сущностями и сущностями в UMI.CMS.
	 * В отдельную таблицу записываются соответствия между идентификаторами
	 * импортируемых сущностей и уже существующих. (@see iUmiImportRelations)
	 * Используется в классе xmlEntityImporter.
	 * @todo: добавить fallback для обычных сущностей системы во все методы
	 */
	class entityImportRelations implements iSourceIdBinder {

		/** @var iBaseSourceIdBinder $baseSourceIdBinder базовый класс управления связями сущностей при обмене данными */
		private $baseSourceIdBinder;

		/** @var \IConnection $connection подключение к бд */
		private $connection;

		/** @var int $sourceId идентификатор ресурса */
		private $sourceId;

		/** @var string OBJECT_IMPORT_TABLE имя таблицы связей для стандартных объектов */
		const OBJECT_IMPORT_TABLE = 'cms3_import_objects';

		/** @var string TYPE_IMPORT_TABLE имя таблицы связей для объектных типов  */
		const TYPE_IMPORT_TABLE = 'cms3_import_types';

		/** @inheritdoc */
		public function __construct(iBaseSourceIdBinder $baseSourceIdBinder,\IConnection $connection) {
			$this->setBaseSourceIdBinder($baseSourceIdBinder)
				->setConnection($connection);
		}

		/** @inheritdoc */
		public function setSourceId($id) {
			if (!is_numeric($id)) {
				throw new InvalidArgumentException('Source id is not numeric');
			}

			$this->sourceId = (int) $id;
			return $this;
		}

		/** @inheritdoc */
		public function setSourceIdByName($name) {
			$id = $this->getBaseSourceIdBinder()
				->getSourceId($name);
			return $this->setSourceId($id);
		}

		/** @inheritdoc */
		public function getSourceId() {
			return $this->sourceId;
		}

		/** @inheritdoc */
		public function defineRelation($externalId, $internalId, $table) {
			$baseSourceIdBinder = $this->getBaseSourceIdBinder();
			$sourceId = (int) $this->getSourceId();

			switch ($table) {
				case self::OBJECT_IMPORT_TABLE : {
					$baseSourceIdBinder->setObjectIdRelation($sourceId, $externalId, $internalId);
					return $this;
				}
				case self::TYPE_IMPORT_TABLE : {
					$baseSourceIdBinder->setTypeIdRelation($sourceId, $externalId, $internalId);
					return $this;
				}
			}

			$connection = $this->getConnection();
			$externalId = $connection->escape($externalId);
			$internalId = (int) $internalId;
			$table = $connection->escape($table);

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$sql = <<<SQL
INSERT INTO `{$table}`
	(`external_id`, `internal_id`, `source_id`) VALUES
	('$externalId', $internalId, $sourceId)
SQL;
			$connection->queryResult($sql);
			return $this;
		}

		/** @inheritdoc */
		public function defineRelationList(array $relationList, $table) {

			foreach ($relationList as $externalId => $internalId) {
				$this->defineRelation($externalId, $internalId, $table);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getInternalId($externalId, $table) {
			$baseSourceIdBinder = $this->getBaseSourceIdBinder();
			$sourceId = (int) $this->getSourceId();

			switch ($table) {
				case self::OBJECT_IMPORT_TABLE : {
					return $baseSourceIdBinder->getNewObjectIdRelation($sourceId, $externalId);
				}
				case self::TYPE_IMPORT_TABLE : {
					return $baseSourceIdBinder->getNewTypeIdRelation($sourceId, $externalId);
				}
			}

			$connection = $this->getConnection();
			$externalId = $connection->escape($externalId);
			$table = $connection->escape($table);

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$sql = <<<SQL
SELECT `internal_id`
FROM `{$table}`
WHERE
	`external_id` = '$externalId' AND `source_id` = $sourceId
LIMIT 0,1
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$internalId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$internalId = (int) array_shift($fetchResult);
			}

			return $internalId;
		}

		/** @inheritdoc */
		public function getExternalId($internalId, $table) {
			$baseSourceIdBinder = $this->getBaseSourceIdBinder();
			$sourceId = (int) $this->getSourceId();

			switch ($table) {
				case self::OBJECT_IMPORT_TABLE : {
					return $baseSourceIdBinder->getOldObjectIdRelation($sourceId, $internalId);
				}
				case self::TYPE_IMPORT_TABLE : {
					return $baseSourceIdBinder->getOldTypeIdRelation($sourceId, $internalId);
				}
			}

			$connection = $this->getConnection();
			$internalId = (int) $internalId;
			$table = $connection->escape($table);

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$sql = <<<SQL
SELECT `external_id`
FROM `{$table}`
WHERE `internal_id` = $internalId AND `source_id` = $sourceId
LIMIT 0,1
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$externalId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$externalId = (string) array_shift($fetchResult);
			}

			return $externalId;
		}

		/** @inheritdoc */
		public function getExternalIdList(array $internalIdList, $table) {
			$internalIdList = array_filter($internalIdList, function($externalId) {
				return is_numeric($externalId);
			});

			if (isEmptyArray($internalIdList)) {
				return [];
			}

			$connection = $this->getConnection();
			$table = $connection->escape($table);

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$internalIdList = array_map('intval', $internalIdList);
			$internalIdListSize = count($internalIdList);
			$internalIdList = implode(', ', $internalIdList);
			$sourceId = (int) $this->getSourceId();

			$sql = <<<SQL
SELECT `internal_id`, `external_id`
FROM `{$table}`
WHERE `internal_id` IN ($internalIdList) AND `source_id` = $sourceId
LIMIT 0, $internalIdListSize
SQL;
			$result = $connection->queryResult($sql)
				->setFetchArray();
			$externalIdList = [];

			foreach ($result as list($internalId, $externalId)) {
				$externalIdList[(int) $internalId] = (string) $externalId;
			}

			return $externalIdList;
		}

		/** @inheritdoc */
		public function getInternalIdList(array $externalIdList, $table) {
			$externalIdList = array_filter($externalIdList, function($externalId) {
				return (is_numeric($externalId) || is_string($externalId));
			});

			if (isEmptyArray($externalIdList)) {
				return [];
			}

			$connection = $this->getConnection();
			$table = $connection->escape($table);

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$externalIdList = array_map([$connection, 'escape'], $externalIdList);
			$externalIdListSize = count($externalIdList);
			$externalIdList = implode('", "', $externalIdList);
			$sourceId = (int) $this->getSourceId();

			$sql = <<<SQL
SELECT `external_id`, `internal_id`
FROM `{$table}`
WHERE `external_id` IN ("$externalIdList") AND `source_id` = $sourceId
LIMIT 0, $externalIdListSize
SQL;
			$result = $connection->queryResult($sql)
				->setFetchArray();
			$internalIdList = [];

			foreach ($result as list($externalId, $internalId)) {
				$internalIdList[(string) $externalId] = (int) $internalId;
			}

			return $internalIdList;
		}


		/** @inheritdoc */
		public function isRelatedToAnotherSource($internalId, $table) {
			$connection = $this->getConnection();
			$internalId = (int) $internalId;
			$table = $connection->escape($table);
			$sourceId = (int) $this->getSourceId();

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$selectSql = <<<SQL
SELECT `external_id` FROM `{$table}` 
	WHERE `source_id` != $sourceId AND `internal_id` = $internalId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);

			return $result->length() > 0;
		}

		/**
		 * Устанавливает экземпляр базового класса управления связями сущностей при обмене данными
		 * @param iUmiImportRelations $baseSourceIdBinder
		 * @return $this
		 */
		private function setBaseSourceIdBinder(iBaseSourceIdBinder $baseSourceIdBinder) {
			$this->baseSourceIdBinder = $baseSourceIdBinder;
			return $this;
		}

		/**
		 * Возвращает экземпляр базового класса управления связями сущностей при обмене данными
		 * @return iUmiImportRelations
		 */
		private function getBaseSourceIdBinder() {
			return $this->baseSourceIdBinder;
		}

		/**
		 * Устанавливает подключение к бд
		 * @param \IConnection $connection подключение к бд
		 * @return $this
		 */
		private function setConnection(\IConnection $connection) {
			$this->connection = $connection;
			return $this;
		}

		/**
		 * Возвращает подключение к бд
		 * @return \IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}
	}
