<?php

	use UmiCms\Service;

	/** Класс для работы со полями объекта, без обращения к нему. */
	class umiPropertiesHelper {

		/* @var umiPropertiesHelper $instance экземпляр класса */
		private static $instance;

		/* @var array $properties массив с загруженными свойствами */
		private $properties = [];

		/* @var umiTypesHelper $umiTypesHelper класс для работы с типами данных */
		private $umiTypesHelper;

		/**
		 * Возвращает экземпляр текущего класса
		 * @return umiPropertiesHelper
		 */
		public static function getInstance() {
			if (self::$instance === null) {
				self::$instance = new umiPropertiesHelper();
			}
			return self::$instance;
		}

		/**
		 * Получает и возвращает объект поля, либо null, если
		 * операция не удалась.
		 * @param int $objectId ид объекта, которому принадлежит поле
		 * @param string $fieldName guid поля
		 * @param int $typeId ид типа данных поля
		 * @param bool $resetCache проигнорировать кеш класса
		 * @return null|iUmiObjectProperty
		 */
		public function getProperty($objectId, $fieldName, $typeId, $resetCache = false) {
			$objectId = (int) $objectId;
			$fieldName = (string) $fieldName;
			$typeId = (int) $typeId;
			$hash = $this->getKeyHash($objectId, $fieldName);

			if (isset($this->properties[$hash]) && !$resetCache) {
				return $this->properties[$hash];
			}

			$fieldId = $this->getFieldIdByName($fieldName, $typeId);

			if ($fieldId === null && (bool) $resetCache) {
				$fieldId = $this->getFieldIdByName($fieldName, $typeId, (bool) $resetCache);
			}

			if ($fieldId === null) {
				return $this->properties[$hash] = null;
			}

			try {
				$property = Service::ObjectPropertyFactory()
					->create($objectId, $fieldId);
			} catch (Exception $exception) {
				$property = null;
			}

			return $this->properties[$hash] = $property;
		}

		/**
		 * Получает и возвращает значение поля, либо null, если
		 * операция не удалась.
		 * @param int $objectId ид объекта, которому принадлежит поле
		 * @param string $fieldName guid поля
		 * @param int $typeId ид типа данных поля
		 * @param bool $resetCache проигнорировать кеш класса
		 * @param array|null $params дополнительные параметры
		 * @return mixed
		 */
		public function getPropertyValue($objectId, $fieldName, $typeId, $resetCache = false, $params = null) {
			$objectId = (int) $objectId;
			$fieldName = (string) $fieldName;
			$typeId = (int) $typeId;
			$hash = md5($objectId . $fieldName . $typeId);

			if (isset($this->properties[$hash]) && !$resetCache) {
				return ($this->properties[$hash] instanceof iUmiObjectProperty)
					? $this->properties[$hash]->getValue()
					: null;
			}

			$property = $this->getProperty($objectId, $fieldName, $typeId, $resetCache);
			return ($property instanceof iUmiObjectProperty) ? $property->getValue($params) : null;
		}

		/**
		 * Инициирует сохранение всех загруженных полей, если они были обновлены
		 * @return bool
		 */
		public function saveProperties() {
			$properties = $this->properties;

			if (umiCount($properties) == 0) {
				return true;
			}

			foreach ($properties as $property) {
				if ($property instanceof iUmiObjectProperty && $property->getIsUpdated()) {
					$associatedObject = $property->getObject();
					/* @var iUmiObject $associatedObject */
					if ($associatedObject instanceof iUmiObject) {
						$associatedObject->setIsUpdated();
						$associatedObject->loadFields();
					}

					$property->commit();
				}
			}

			return true;
		}

		/**
		 * Применяет изменения свойства
		 * @param iUmiObjectProperty $property
		 * @return bool
		 */
		public function commitProperty(iUmiObjectProperty $property) {
			/* @var iUmiObjectProperty $property */
			if (!$property->getIsUpdated()) {
				return false;
			}

			$associatedObject = $property->getObject();

			/* @var iUmiObject $associatedObject */
			if ($associatedObject instanceof iUmiObject) {
				$associatedObject->setIsUpdated();
				$associatedObject->loadFields();
			}

			$property->commit();

			return true;
		}

		/**
		 * Очищает внутренний кеш класса
		 * @retun void
		 */
		public function clearCache() {
			$this->properties = [];
		}

		/** Деструктор */
		public function __destruct() {
			$this->saveProperties();
		}

		/**
		 * Очищает кеш свойства
		 * @param int $objectId идентификатор объекта
		 * @param string $fieldName строковой идентификатор поля объекта
		 */
		public function resetPropertyCache($objectId, $fieldName) {
			$objectId = (int) $objectId;
			$fieldName = (string) $fieldName;
			$hash = $this->getKeyHash($objectId, $fieldName);

			if (isset($this->properties[$hash])) {
				$this->properties[$hash] = null;
			}
		}

		/**
		 * Возвращает уникальный хеш, определяющий поле
		 * @param int $objectId идентификатор объекта
		 * @param string $fieldName строковой идентификатор поля объекта
		 * @return string
		 */
		private function getKeyHash($objectId, $fieldName) {
			return md5($objectId . $fieldName);
		}

		/** Конструктор */
		private function __construct() {
			$this->umiTypesHelper = umiTypesHelper::getInstance();
		}

		/**
		 * Возвращает id поля по его гуиду, если такое поле есть,
		 * иначе - null.
		 * @param string $fieldName гуид поля
		 * @param int $typeId ид типа данных поля
		 * @param bool $resetCache проигнорировать кеш
		 * @return int|null
		 */
		private function getFieldIdByName($fieldName, $typeId, $resetCache = false) {
			$fields = $this->umiTypesHelper->getFieldsByObjectTypeIds($typeId, (bool) $resetCache);

			if (isset($fields[$typeId][$fieldName])) {
				return (int) $fields[$typeId][$fieldName];
			}

			return null;
		}
	}
