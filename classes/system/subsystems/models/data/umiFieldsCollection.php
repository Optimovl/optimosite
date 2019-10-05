<?php

	/** Коллекция полей, singleton. */
	class umiFieldsCollection extends singleton implements iUmiFieldsCollection {

		/**
		 * @var array $loadedFieldList список загруженных полей
		 *
		 * [
		 *      iUmiField->getId() => iUmiField
		 * ]
		 *
		 */
		private $loadedFieldList = [];

		/**
		 * @inheritdoc
		 * @return iUmiFieldsCollection
		 */
		public static function getInstance($className = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function add($name, $title, $fieldTypeId) {
			$id = $this->addField($name, $title, $fieldTypeId);
			return $this->getLoadedField($id);
		}

		/** @inheritdoc */
		public function getById($id) {
			return $this->getField($id);
		}

		/** @inheritdoc */
		public function delById($id) {
			return $this->delField($id);
		}

		/** @inheritdoc */
		public function isExists($id) {
			$id = (int) $id;

			if ($id === 0) {
				return false;
			}

			$query = <<<SQL
SELECT `id` FROM `cms3_object_fields` WHERE `id` = $id LIMIT 0, 1;
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($query);

			return $result->length() == 1;
		}

		/** @inheritdoc */
		public function getFieldIdListByType(iUmiFieldType $type) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$fieldTypeId = (int) $type->getId();
			$sql = <<<SQL
SELECT `id` FROM `cms3_object_fields` WHERE `field_type_id` = $fieldTypeId;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$fieldIdList = [];

			foreach ($result as $row) {
				$fieldIdList[] = $row['id'];
			}

			return $fieldIdList;
		}

		/** @inheritdoc */
		public function addField(
			$name,
			$title,
			$fieldTypeId,
			$isVisible = true,
			$isLocked = false,
			$isInheritable = false
		) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sql = 'INSERT INTO cms3_object_fields VALUES()';
			$connection->query($sql);
			$id = $connection->insertId();

			try {
				$field = new umiField($id);
				$field->setName($name);
				$field->setTitle($title);
				$field->setFieldTypeId($fieldTypeId);
				$field->setIsVisible($isVisible);
				$field->setIsLocked($isLocked);
				$field->setIsInheritable($isInheritable);
				$field->commit();
			} catch (Exception $exception) {
				$this->delField($id);
				throw $exception;
			}

			return $this->setLoadedField($field)
				->getLoadedField($id)
				->getId();
		}

		/** @inheritdoc */
		public function getField($id, $data = false) {
			if ($this->isLoaded($id)) {
				return $this->getLoadedField($id);
			}

			return $this->loadField($id, $data);
		}

		/** @inheritdoc */
		public function getFieldList(array $idList) {
			$notLoadedItList = [];

			foreach ($idList as $id) {
				if (!$this->isLoaded($id)) {
					$notLoadedItList[] = $id;
				}
			}

			if (count($notLoadedItList) > 0) {
				$this->loadFieldList($notLoadedItList);
			}

			$fieldList = [];

			foreach ($idList as $id) {
				$field = $this->getLoadedField($id);

				if ($field instanceof iUmiField) {
					$fieldList[] = $field;
				}
			}

			return $fieldList;
		}

		/** @inheritdoc */
		public function delField($id) {
			$id = (int) $id;

			if ($id === 0) {
				return false;
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sql = "DELETE FROM cms3_object_fields WHERE id = $id";
			$connection->query($sql);

			$this->unloadField($id);

			return $connection->affectedRows() > 0;
		}

		/** @inheritdoc */
		public function clearCache() {
			$this->unloadAllFields();
		}

		/** @inheritdoc */
		public function filterListByNameBlackList(array &$fieldList, array $blackList) {

			foreach ($fieldList as $index => $field) {
				if (in_array($field->getName(), $blackList)) {
					unset($fieldList[$index]);
				}
			}

			return $this;
		}

		/** @inheritdoc */
		public function filterListByTypeWhiteList(array &$fieldList, array $whiteList) {
			foreach ($fieldList as $index => $field) {
				if (!in_array($field->getDataType(), $whiteList)) {
					unset($fieldList[$index]);
				}
			}

			return $this;
		}

		/** @inheritdoc */
		protected function __construct() {
		}

		/**
		 * Загружает список полей
		 * @param array $idList список идентификаторов полей
		 * @return $this
		 */
		private function loadFieldList(array $idList) {
			if (isEmptyArray($idList)) {
				return $this;
			}

			$idList = array_map(function ($id) {
				return (int) $id;
			}, $idList);
			$idList = array_unique($idList);
			$limit = count($idList);
			$idList = implode(', ', $idList);

			$sql = <<<SQL
SELECT `id`, `name`, `title`, `is_locked`, `is_inheritable`, `is_visible`, `field_type_id`, `guide_id`, `in_search`, 
`in_filter`, `tip`, `is_required`, `sortable`, `is_system`, `restriction_id`, `is_important` 
FROM `cms3_object_fields`
WHERE `id` IN ($idList)
LIMIT 0, $limit
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$id = getFirstValue($row);
				$this->loadField($id, $row);
			}

			return $this;
		}

		/**
		 * Выгружает поле из кеша
		 * @param int $id идентификатор поля
		 * @return $this
		 */
		private function unloadField($id) {
			unset($this->loadedFieldList[$id]);
			return $this;
		}

		/**
		 * Выгружает все поля из кеша
		 * @return $this
		 */
		private function unloadAllFields() {
			$this->loadedFieldList = [];
			return $this;
		}

		/**
		 * Загружает поле в кеш
		 * @param iUmiField $field
		 * @return $this
		 */
		private function setLoadedField(iUmiField $field) {
			$this->loadedFieldList[$field->getId()] = $field;
			return $this;
		}

		/**
		 * Возвращает поля из кеша
		 * @param int $id идентификатор поля
		 * @return iUmiField|bool
		 */
		private function getLoadedField($id) {
			if (!$this->isLoaded($id)) {
				return false;
			}

			return $this->loadedFieldList[$id];
		}

		/**
		 * Загружено ли поле в кеш
		 * @param int $id идентификатор поля
		 * @return bool
		 */
		private function isLoaded($id) {
			if (!is_numeric($id)) {
				return false;
			}

			$id = (int) $id;

			return array_key_exists($id, $this->loadedFieldList);
		}

		/**
		 * Создает экземпляр коля и возвращает его
		 * @param int $id идентификатор поля
		 * @param array|bool $data список данных поля
		 * @return bool|umiField
		 */
		private function loadField($id, $data = false) {
			try {
				$field = new umiField($id, $data);
			} catch (privateException $e) {
				$e->unregister();
				return false;
			}

			$this->setLoadedField($field);
			return $field;
		}

		/** @deprecated */
		public function getRestrictionIdByFieldId($id) {
			$field = $this->getField($id);
			return ($field instanceof iUmiField) ? $field->getRestrictionId() : false;
		}

		/** @deprecated */
		public function isFieldRequired($id) {
			$field = $this->getField($id);
			return ($field instanceof iUmiField) ? $field->getIsRequired() : false;
		}
	}
