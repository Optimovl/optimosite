<?php

	namespace UmiCms\System\Data\Object\Property;

	use UmiCms\System\Data\Object\Property\Value\Table\iSchema;

	/**
	 * Класс репозитория значений полей объектов
	 * @package UmiCms\System\Data\Object\Property
	 */
	class Repository implements iRepository {

		/** @var \IConnection $connection подключение к бд */
		private $connection;

		/** @var iFactory $factory фабрика значений полей объектов */
		private $factory;

		/** @var \iUmiFieldsCollection $fieldCollection коллекция полей */
		private $fieldCollection;

		/** @var iSchema $schema схема таблиц значений свойств объектов */
		private $schema;

		/** @inheritdoc */
		public function __construct(
			\IConnection $connection,
			iFactory $factory,
			\iUmiFieldsCollection $fieldCollection,
			iSchema $schema
		) {
			$this->connection = $connection;
			$this->factory = $factory;
			$this->fieldCollection = $fieldCollection;
			$this->schema = $schema;
		}

		/** @inheritdoc */
		public function getListByFieldId($fieldId, $limit = 100, $offset = 0, $dataType = null) {
			$field = $this->getFieldCollection()
				->getField($fieldId);

			if (!$field instanceof \iUmiField) {
				return [];
			}

			$fieldId = $field->getId();
			$offset = (int) $offset;
			$limit = (int) $limit;
			$dataType = $dataType ?: $field->getDataType();
			$table = $this->getSchema()
				->getTableByDataType($dataType);
			$sql = <<<SQL
SELECT `obj_id` FROM `$table` WHERE `field_id` = $fieldId LIMIT $offset, $limit;
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			$result->setFetchType(\IQueryResult::FETCH_ASSOC);
			$propertyList = [];
			$factory = $this->getFactory();

			foreach ($result as $row) {
				$propertyList[] = $factory->create($row['obj_id'], $fieldId);
			}

			return $propertyList;
		}

		/**
		 * Возвращает подключение к бд
		 * @return \IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Возвращает фабрику полей объектов
		 * @return iFactory
		 */
		private function getFactory() {
			return $this->factory;
		}

		/**
		 * Возвращает коллекцию полей
		 * @return \iUmiFieldsCollection
		 */
		private function getFieldCollection() {
			return $this->fieldCollection;
		}

		/**
		 * Возвращает схему таблиц значений свойств объектов
		 * @return iSchema
		 */
		private function getSchema() {
			return $this->schema;
		}
	}