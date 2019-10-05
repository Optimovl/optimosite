<?php

	/**
	 * Этот класс-коллекция служит для управления/получения доступа к типам полей
	 * Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
	 */
	class umiFieldTypesCollection extends singleton implements iSingleton, iUmiFieldTypesCollection {

		private $field_types = [];

		/** @inheritdoc */
		protected function __construct() {
			$this->loadFieldTypes();
		}

		/**
		 * @inheritdoc
		 * @return iUmiFieldTypesCollection
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function addFieldType($name, $dataType = 'string', $isMultiple = false, $isUnsigned = false) {
			if (!umiFieldType::isValidDataType($dataType)) {
				throw new coreException('Not valid data type given');
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "INSERT INTO cms3_object_field_types (data_type) VALUES('{$dataType}')";
			$connection->query($sql);

			$fieldTypeId = $connection->insertId();
			$fieldType = new umiFieldType($fieldTypeId);

			$fieldType->setName($name);
			$fieldType->setDataType($dataType);
			$fieldType->setIsMultiple($isMultiple);
			$fieldType->setIsUnsigned($isUnsigned);
			$fieldType->commit();

			$this->field_types[$fieldTypeId] = $fieldType;

			return $fieldTypeId;
		}

		/** @inheritdoc */
		public function delFieldType($id) {
			if (!$this->isExists($id)) {
				return false;
			}

			$id = (int) $id;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "DELETE FROM cms3_object_field_types WHERE id = '{$id}'";
			$connection->query($sql);

			unset($this->field_types[$id]);
			return true;
		}

		/** @inheritdoc */
		public function getFieldType($id) {
			if ($this->isExists($id)) {
				return $this->field_types[$id];
			}

			return false;
		}

		/** @inheritdoc */
		public function getFieldTypeByDataType($dataType, $isMultiple = false) {
			$dataType = (string) $dataType;

			if ($dataType === '') {
				return false;
			}

			$fieldTypes = $this->getFieldTypesList();
			$fieldType = false;

			foreach ($fieldTypes as $ftype) {
				if ($ftype->getDataType() == $dataType && $ftype->getIsMultiple() == $isMultiple) {
					$fieldType = $ftype;
					break;
				}
			}

			return $fieldType;
		}

		/** @inheritdoc */
		public function isExists($id) {
			return array_key_exists($id, $this->field_types);
		}

		/**
		 * Загружает в коллекцию все типы полей, создает экземпляры класса umiFieldType для каждого типа
		 * @return bool true, если удалось загрузить, либо строку - описание ошибки, в случае неудачи.
		 */
		private function loadFieldTypes() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT id, name, data_type, is_multiple, is_unsigned FROM cms3_object_field_types ORDER BY name ASC';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$fieldTypeId = $row[0];

				try {
					$fieldType = new umiFieldType($fieldTypeId, $row);
				} catch (privateException $e) {
					$e->unregister();
					continue;
				}

				$this->field_types[$fieldTypeId] = $fieldType;
			}

			return true;
		}

		/** @inheritdoc */
		public function getFieldTypesList() {
			if (!is_array($this->field_types) || umiCount($this->field_types) == 0) {
				$this->loadFieldTypes();
			}

			return $this->field_types;
		}

		/** @inheritdoc */
		public function clearCache() {
			$keys = array_keys($this->field_types);
			foreach ($keys as $key) {
				unset($this->field_types[$key]);
			}
			$this->field_types = [];
			$this->loadFieldTypes();
		}
	}
