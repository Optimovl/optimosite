<?php

	use UmiCms\Service;

	/**
	 * Вспомогательный класс для класса selector. В прикладном коде напрямую не используется.
	 * Предназначен для того, чтобы получать сущности по их идентификаторам,
	 * по связке модуль/метод базового типа данных (для типов данных),
	 * по префиксу (для языков) и по домену (для доменов).
	 * Примеры использования смотрите в selector::get().
	 */
	class selectorGetter {

		/** Список поддерживаемых сущностей */
		protected static $types = [
			'object',
			'page',
			'object-type',
			'hierarchy-type',
			'field',
			'field-type',
			'domain',
			'lang'
		];

		/** Запрошенная сущность */
		protected $requestedType;

		/**
		 * @param string $requestedType запрошенная сущность
		 * @throws selectorException
		 */
		public function __construct($requestedType) {
			if (!in_array($requestedType, self::$types)) {
				throw new selectorException("Wrong content type \"{$requestedType}\"");
			}
			$this->requestedType = $requestedType;
		}

		/**
		 * Возвращает сущности по их идентификаторам
		 * @param int|int[] $ids идентификаторы искомых сущностей
		 * @return mixed
		 */
		public function id($ids) {
			if (is_array($ids)) {
				$entities = [];
				foreach ($ids as $id) {
					$entity = $this->id($id);
					if (is_object($entity)) {
						$entities[] = $entity;
					}
				}
				return $entities;
			}

			if (!$ids) {
				return null;
			}

			$id = $ids;
			$collection = $this->collection();
			try {
				switch ($this->requestedType) {
					case 'object':
						return $collection->getObject($id);
					case 'page':
						return $collection->getElement($id);
					case 'hierarchy-type':
					case 'object-type':
						return $collection->getType($id);
					case 'field':
						return $collection->getField($id);
					case 'field-type':
						return $collection->getFieldType($id);
					case 'domain':
						return $collection->getDomain($id);
					case 'lang':
						return $collection->getLang($id);
				}
			} catch (coreException $e) {
				return null;
			}
		}

		/**
		 * Возвращает объектный или иерархический тип данных по связке модуль/метод
		 * @param string $module модуль базового типа данных
		 * @param string $method метод базового типа данных
		 * @return mixed
		 * @throws selectorException
		 */
		public function name($module, $method = '') {
			$collection = $this->collection();
			switch ($this->requestedType) {
				case 'object-type': {
					$objectTypeId = $collection->getTypeIdByHierarchyTypeName($module, $method);
					return $this->id($objectTypeId);
				}
				case 'hierarchy-type': {
					$hierarchyType = $collection->getTypeByName($module, $method);
					return ($hierarchyType instanceof iUmiHierarchyType) ? $hierarchyType : null;
				}
				default: {
					throw new selectorException("Unsupported \"name\" method for \"{$this->requestedType}\"");
				}
			}
		}

		/**
		 * Возвращает экземпляр объектного типа данных или объект по гуиду
		 * @param string $guid гуид
		 * @return iUmiObject|iUmiObjectType|null
		 * @throws selectorException
		 */
		public function guid($guid) {
			$collection = $this->collection();
			switch ($this->requestedType) {
				case 'object-type': {
					/** @var iUmiObjectTypesCollection $collection */
					$objectType = $collection->getTypeByGUID($guid);
					return $objectType ?: null;
				}
				case 'object': {
					/** @var iUmiObjectsCollection $collection */
					$object = $collection->getObjectByGUID($guid);
					return $object ?: null;
				}
				default: {
					throw new selectorException("Unsupported \"guid\" method for \"{$this->requestedType}\"");
				}
			}
		}

		/**
		 * Получить язык по префиксу
		 * @param string $prefix префикс языка
		 * @return mixed
		 * @throws selectorException
		 */
		public function prefix($prefix) {
			if ($this->requestedType != 'lang') {
				throw new selectorException("Unsupported \"prefix\" method for \"{$this->requestedType}\"");
			}
			$collection = $this->collection();
			return $this->id($collection->getLangId($prefix));
		}

		/**
		 * Получить домен по доменному имени
		 * @param string $host доменное имя
		 * @return mixed
		 * @throws selectorException
		 */
		public function host($host) {
			if ($this->requestedType != 'domain') {
				throw new selectorException("Unsupported \"host\" method for \"{$this->requestedType}\"");
			}
			$collection = $this->collection();
			return $this->id($collection->getDomainId($host));
		}

		/**
		 * Получить экземпляр коллекции, которая отвечает за тип искомой сущности
		 * @return mixed
		 */
		protected function collection() {
			switch ($this->requestedType) {
				case 'object':
					return umiObjectsCollection::getInstance();
				case 'page':
					return umiHierarchy::getInstance();
				case 'object-type':
					return umiObjectTypesCollection::getInstance();
				case 'hierarchy-type':
					return umiHierarchyTypesCollection::getInstance();
				case 'field':
					return umiFieldsCollection::getInstance();
				case 'field-type':
					return umiFieldTypesCollection::getInstance();
				case 'domain':
					return Service::DomainCollection();
				case 'lang':
					return Service::LanguageCollection();
			}
		}
	}
