<?php

	/** Интерфейс коллекции сущностей */
	interface iUmiCollection {

		/**
		 * Возвращает класс элементов коллекции
		 * @return mixed
		 */
		public function getCollectionItemClass();

		/** Возвращает имя таблицы, где хранятся элементы коллекции */
		public function getTableName();

		/**
		 * Возвращает слайдер по его идентификатору
		 * @param int $id идентификатор слайдера
		 * @return iUmiCollectionItem|null
		 */
		public function getById($id);

		/**
		 * Возвращает список сущностей, в соответствии с настройками
		 * @param array $params настройки
		 * @return iUmiCollectionItem[]
		 */
		public function get(array $params);

		/**
		 * Возвращает количество сущностей, в соответствии с настройками
		 * @param array $params настройки
		 * @return int
		 */
		public function count(array $params);

		/**
		 * Удаляет сущности, в соответствии с настройками
		 * @param array $params настройки
		 * @return bool
		 */
		public function delete(array $params);

		/** Удаляет все сущности */
		public function deleteAll();

		/**
		 * Создает и возвращает сущность, в соответствии с настройками
		 * @param array $params настройки
		 * @return iUmiCollectionItem
		 */
		public function create(array $params);

		/**
		 * Существуют ли сущности, соответствующие настройкам
		 * @param array $params настройки
		 * @return bool
		 */
		public function isExists(array $params);

		/**
		 * Импортирует список сущностей и возвращает результат
		 * @param array $data данные сущностей
		 * @return array
		 */
		public function import(array $data);

		/**
		 * Возвращает данные сущностей, соответствующих настройкам
		 * @param array $params настройки
		 * @return array
		 */
		public function export(array $params);

		/**
		 * Возвращает условия выборки
		 * @param array $params настройки
		 * @return mixed
		 */
		public function getFieldsConditions(array $params);

		/**
		 * Обновляет идентификаторы связанных сущостей.
		 * Если сущности были импортированы - у них могут измениться идентификаторы.
		 * @param array $properties данные импортируемой сущности
		 * @param string|int $sourceId идентификатор ресурса
		 * @return array
		 */
		public function updateRelatedId(array $properties, $sourceId);
	}
