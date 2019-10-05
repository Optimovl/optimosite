<?php

	/**
	 * Вспомогательный класс для механизма формирования выборок "Selector"
	 * Хранит информацию о типе запрошенных сущностей
	 */
	class selectorType {

		/** Доступные типы */
		protected static $typeClasses = ['object-type', 'hierarchy-type'];

		/** @var array идентификаторы объектных типов, которые участвуют в выборке */
		public $objectTypeIds;

		/** @var array идентификаторы базовых типов, которые участвуют в выборке */
		public $hierarchyTypeIds;

		/** Выбранный тип */
		protected $typeClass;

		/**
		 * @param string $typeClass тип, по которому ведется выборка
		 * @throws selectorException
		 */
		public function __construct($typeClass) {
			$this->setTypeClass($typeClass);
		}

		/**
		 * Установить тип по связке модуль/метод базового типа
		 * @param string $module модуль
		 * @param string $method метод
		 * @throws selectorException
		 */
		public function name($module, $method) {
			if (!$method && $module == 'content') {
				$method = 'page';
			}
			$umiTypesHelper = umiTypesHelper::getInstance();

			switch ($this->typeClass) {
				case 'object-type': {
					$umiTypesHelper->getFieldsByHierarchyTypeName($module, $method);
					$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName($module, $method);

					if (!$hierarchyTypeId) {
						throw new selectorException(__METHOD__ . ": Hierarchy type ($module, $method) not found");
					}

					$objectTypeIds = $umiTypesHelper->getObjectTypesIdsByHierarchyTypeId($hierarchyTypeId);

					if (!is_array($objectTypeIds) || umiCount($objectTypeIds) == 0) {
						throw new selectorException(
							__METHOD__ . ": Object types ids by hierarchy type ($hierarchyTypeId) not found"
						);
					}

					$this->setObjectTypeIds($objectTypeIds);
					break;
				}
				case 'hierarchy-type': {
					$umiTypesHelper->getFieldsByHierarchyTypeName($module, $method);
					$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName($module, $method);

					if (!$hierarchyTypeId) {
						throw new selectorException(__METHOD__ . ": Hierarchy type ($module, $method) not found");
					}

					$this->setHierarchyTypeIds($hierarchyTypeId);
					break;
				}
			}
		}

		/**
		 * Установить тип по его идентификатору
		 * @param mixed $id идентификатор типа
		 * @throws selectorException
		 */
		public function id($id) {
			if (!is_numeric($id) && is_string($id)) {
				$this->guid($id);
				return;
			}
			if (!is_array($id)) {
				$id = [$id];
			}

			$id = array_map('intval', $id);
			$umiTypesHelper = umiTypesHelper::getInstance();

			switch ($this->typeClass) {
				case 'object-type': {
					$umiTypesHelper->getFieldsByObjectTypeIds($id);
					$this->setObjectTypeIds($id);
					break;
				}
				case 'hierarchy-type': {
					$umiTypesHelper->getFieldsByHierarchyTypeId($id);
					$this->setHierarchyTypeIds($id);
					break;
				}
			}
		}

		/**
		 * Установить тип по его guid
		 * @param string $guid guid
		 * @throws selectorException
		 */
		public function guid($guid) {
			if ($this->typeClass != 'object-type') {
				throw new selectorException('Select by guid is allowed only for object-type');
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$umiTypesHelper->getFieldsByObjectTypeGuid($guid);
			$sourceGuid = $guid;

			if (!is_array($guid)) {
				$guid = [$guid];
			}

			$objectTypeIds = [];

			foreach ($guid as $value) {
				$typeId = $umiTypesHelper->getObjectTypeIdByGuid($value);
				if (is_numeric($typeId)) {
					$objectTypeIds[] = $typeId;
				}
			}

			if (!is_array($objectTypeIds) || umiCount($objectTypeIds) == 0) {
				throw new selectorException(__METHOD__ . ": Object types ids by guid ({$sourceGuid}) not found");
			}

			$this->setObjectTypeIds($objectTypeIds);
		}

		/**
		 * Установить тип
		 * @param string $typeClass требуемый тип
		 * @throws selectorException
		 */
		public function setTypeClass($typeClass) {
			if (in_array($typeClass, self::$typeClasses)) {
				$this->typeClass = $typeClass;
			} else {
				throw new selectorException(
					"Unknown type class \"{$typeClass}\". These types are only supported: " .
					implode(', ', self::$typeClasses)
				);
			}
		}

		/**
		 * Получить массив с разными id полей по названию полю.
		 * Id разные, потому что в каждом типе данных может быть свое поле с запрошенным названием.
		 * @param string $fieldName
		 * @return array|bool
		 * @throws selectorException
		 */
		public function getFieldsId($fieldName) {
			if ($this->objectTypeIds === null && $this->hierarchyTypeIds === null) {
				throw new selectorException("Object and hierarchy type prop can't be empty both");
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$fieldIds = [];

			if ($this->objectTypeIds !== null) {
				$objectTypesFields = $umiTypesHelper->getFieldsByObjectTypeIds($this->objectTypeIds);
				foreach ($this->objectTypeIds as $id) {
					if (isset($objectTypesFields[$id][$fieldName])) {
						$fieldIds[] = $objectTypesFields[$id][$fieldName];
					}
				}
			}

			if ($this->hierarchyTypeIds !== null) {
				foreach ($this->hierarchyTypeIds as $hierarchyTypeId) {
					$objectTypeIds = $umiTypesHelper->getObjectTypesIdsByHierarchyTypeId($hierarchyTypeId);
					$objectTypesFields = $umiTypesHelper->getFieldsByObjectTypeIds($objectTypeIds);
					foreach ($objectTypeIds as $id) {
						if (isset($objectTypesFields[$id][$fieldName])) {
							$fieldIds[] = $objectTypesFields[$id][$fieldName];
						}
					}
				}
			}

			$fieldIds = array_unique($fieldIds);
			$fieldIdsCount = umiCount($fieldIds);

			if ($fieldIdsCount === 0) {
				return false;
			}

			return array_values($fieldIds);
		}

		protected function setObjectTypeIds($objectTypeIds) {
			if (!is_array($objectTypeIds)) {
				$objectTypeIds = [$objectTypeIds];
			}

			if ($this->objectTypeIds === null) {
				$this->objectTypeIds = $objectTypeIds;
			} else {
				$this->objectTypeIds = array_unique(array_merge($this->objectTypeIds, $objectTypeIds));
			}
		}

		protected function setHierarchyTypeIds($hierarchyTypeIds) {
			if (!is_array($hierarchyTypeIds)) {
				$hierarchyTypeIds = [$hierarchyTypeIds];
			}

			if ($this->hierarchyTypeIds === null) {
				$this->hierarchyTypeIds = $hierarchyTypeIds;
			} else {
				$this->hierarchyTypeIds = array_unique(array_merge($this->hierarchyTypeIds, $hierarchyTypeIds));
			}
		}
	}
