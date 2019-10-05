<?php

	/** Этот класс служит для управления свойствами типа поля */
	class umiFieldType extends umiEntinty implements iUmiFieldType {

		private $name;

		private $data_type;

		private $is_multiple = false;

		private $is_unsigned = false;

		protected $store_type = 'field_type';

		/** @var array список строковых идентификаторов типов полей, которые хранят числа */
		protected $numberTypes = [
			'counter',
			'int',
			'float'
		];

		/** @inheritdoc */
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/** @inheritdoc */
		public function getIsMultiple() {
			return $this->is_multiple;
		}

		/** @inheritdoc */
		public function getIsUnsigned() {
			return $this->is_unsigned;
		}

		/** @inheritdoc */
		public function getDataType() {
			return $this->data_type;
		}

		/** @inheritdoc */
		public static function getDataTypes() {
			return [
				'int',
				'string',
				'text',
				'relation',
				'file',
				'img_file',
				'video_file',
				'swf_file',
				'date',
				'boolean',
				'wysiwyg',
				'password',
				'tags',
				'symlink',
				'price',
				'float',
				'counter',
				'optioned',
				'color',
				'link_to_object_type',
				'multiple_image',
				'domain_id',
				'domain_id_list',
				'offer_id_list',
				'offer_id'
			];
		}

		/** @inheritdoc */
		public static function getDataTypeDB($dataType) {
			$relations = [
				'text' => 'text_val',
				'file' => 'text_val',
				'img_file' => 'text_val',
				'video_file' => 'text_val',
				'swf_file' => 'text_val',
				'wysiwyg' => 'text_val',
				'string' => 'varchar_val',
				'password' => 'varchar_val',
				'tags' => 'varchar_val',
				'color' => 'varchar_val',
				'int' => 'int_val',
				'date' => 'int_val',
				'boolean' => 'int_val',
				'link_to_object_type' => 'int_val',
				'price' => 'float_val',
				'float' => 'float_val',
				'relation' => 'rel_val',
				'symlink' => 'tree_val',
				'counter' => 'counter',
				'optioned' => 'optioned',
				'domain_id' => 'domain_id',
				'domain_id_list' => 'domain_id',
				'multiple_image' => 'multiple_image',
				'offer_id_list' => 'offer_id',
				'offer_id' => 'offer_id'
			];

			return array_key_exists($dataType, $relations) ? $relations[$dataType] : false;
		}

		/** @inheritdoc */
		public static function isValidDataType($dataType) {
			return in_array($dataType, self::getDataTypes());
		}

		/** @inheritdoc */
		public function setName($name) {
			if ($this->getName() != $name) {
				$name = $this->translateI18n($name, 'field-type-');
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setIsMultiple($isMultiple) {
			$isMultiple = (bool) $isMultiple;

			if ($this->getIsMultiple() != $isMultiple) {
				$this->is_multiple = $isMultiple;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setIsUnsigned($isUnsigned) {
			$isUnsigned = (bool) $isUnsigned;

			if ($this->getIsUnsigned() != $isUnsigned) {
				$this->is_unsigned = $isUnsigned;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setDataType($dataType) {
			if (!self::isValidDataType($dataType)) {
				return false;
			}

			if ($this->getDataType() != $dataType) {
				$this->data_type = $dataType;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function isNumber() {
			return in_array($this->getDataType(), $this->numberTypes);
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < 5) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT id, name, data_type, is_multiple, is_unsigned
FROM cms3_object_field_types
WHERE id = $escapedId LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 5) {
				return false;
			}

			list($id, $name, $dataType, $isMultiple, $isUnsigned) = $row;

			if (!self::isValidDataType($dataType)) {
				return false;
			}

			$this->name = $name;
			$this->data_type = $dataType;
			$this->is_multiple = (bool) $isMultiple;
			$this->is_unsigned = (bool) $isUnsigned;

			return true;
		}

		/** @inheritdoc */
		protected function save() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$name = $connection->escape($this->name);
			$dataType = $connection->escape($this->data_type);
			$isMultiple = (int) $this->is_multiple;
			$isUnsigned = (int) $this->is_unsigned;

			$sql = <<<SQL
UPDATE cms3_object_field_types
SET name = '{$name}', data_type = '{$dataType}',
    is_multiple = '{$isMultiple}', is_unsigned = '{$isUnsigned}'
WHERE id = '{$this->id}'
SQL;
			$connection->query($sql);

			return true;
		}
	}
