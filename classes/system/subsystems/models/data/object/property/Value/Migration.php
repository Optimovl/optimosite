<?php

	namespace UmiCms\System\Data\Object\Property\Value;

	use UmiCms\System\Data\Object\Property\Value\Table\iSchema;

	/**
	 * Абстрактный класс миграции значений полей
	 * @package UmiCms\System\Data\Object\Property\Value
	 */
	abstract class Migration implements iMigration {

		/** @var iSchema $schema схема таблиц значений свойств объектов */
		private $schema;

		/** @var \IConnection $connection подключение к бд */
		private $connection;

		/** @inheritdoc */
		public function __construct(iSchema $schema, \IConnection $connection) {
			$this->schema = $schema;
			$this->connection = $connection;
		}

		/**
		 * Переносит значение поля из одной таблицы в другую
		 * @param \iUmiObjectProperty $property мигрируемое значение свойства объекта
		 * @param string $sourceDataType исходный тип данных поля
		 * @param string $targetDataType целевой тип данных поля
		 * @return $this
		 * @throws \RuntimeException
		 * @throws \databaseException
		 */
		abstract protected function moveValues(\iUmiObjectProperty $property, $sourceDataType, $targetDataType);

		/**
		 * Переносит строки из исходной таблицы в целевую
		 * @param \iUmiObjectProperty $property мигрируемое значение свойства объекта
		 * @param string $sourceTable имя исходной таблицы
		 * @param string $sourceColumn ячейка исходной таблицы
		 * @param string $targetTable имя целевой таблицы
		 * @param string $targetColumn ячейка целевой таблицы
		 * @return $this
		 * @throws \databaseException
		 */
		protected function moveRowsToTarget(
			\iUmiObjectProperty $property, $sourceTable, $sourceColumn, $targetTable, $targetColumn
		) {
			$objectId = $property->getObjectId();
			$fieldId = $property->getFieldId();
			$selectSql = <<<SQL
INSERT INTO {$targetTable}
(`obj_id`, `field_id`, `$targetColumn`)
	SELECT `obj_id`, `field_id`, `$sourceColumn`
		FROM {$sourceTable}
			WHERE `obj_id` = $objectId AND `field_id` = $fieldId
SQL;
			$this->getConnection()
				->query($selectSql);
			return $this;
		}

		/**
		 * Удаляет перенесенные строки из исходной таблицы
		 * @param \iUmiObjectProperty $property мигрируемое значение свойства объекта
		 * @param string $sourceTable имя исходной таблицы
		 * @return $this
		 * @throws \databaseException
		 */
		protected function deleteSourceRows(\iUmiObjectProperty $property, $sourceTable) {
			$objectId = $property->getObjectId();
			$fieldId = $property->getFieldId();
			$deleteSql = <<<SQL
DELETE FROM `$sourceTable` WHERE `obj_id` = $objectId AND `field_id` = $fieldId 
SQL;
			$this->getConnection()
				->query($deleteSql);
			return $this;
		}

		/**
		 * Возвращает название столбца таблицы для хранения свойства заданного типа данных
		 * @param string $dataType тип данных
		 * @return string
		 * @throws \RuntimeException
		 */
		protected function getColumnByDataType($dataType) {
			switch ($dataType) {
				case 'domain_id' : {
					return 'domain_id';
				}
				case 'string' : {
					return 'varchar_val';
				}
				case 'int' : {
					return 'int_val';
				}
				case 'file' : {
					return 'text_val';
				}
				case 'img_file' : {
					return 'src';
				}
				default : {
					throw new \RuntimeException('Incorrect data type given: ' . var_export($dataType, true));
				}
			}
		}

		/**
		 * Возвращает схему таблиц значений свойств объектов
		 * @return iSchema
		 */
		protected function getSchema() {
			return $this->schema;
		}

		/**
		 * Возвращает подключение к базе данных
		 * @return \IConnection
		 */
		protected function getConnection() {
			return $this->connection;
		}
	}