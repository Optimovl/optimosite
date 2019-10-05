<?php

	/** Интерфейс для управления иерархическими типами */
	interface iUmiHierarchyTypesCollection {

		/**
		 * Возвращает тип по его id
		 * @param int $id id типа
		 * @return iUmiHierarchyType|bool иерархический тип (класс umiHierarchyType), либо false
		 */
		public function getType($id);

		/**
		 * Возвращает иерархический тип по его модулю/методу, либо false
		 * @param string $name модуль типа
		 * @param string|bool $ext метод типа
		 * @return iUmiHierarchyType|bool
		 */
		public function getTypeByName($name, $ext = false);

		/**
		 * Возвращает иерархические типы определенных модулей
		 * @param string|array $modules имена модулей
		 * @return iUmiHierarchyType[]
		 */
		public function getTypesByModules($modules);

		/**
		 * Добавляет новый иерархический тип и возвращает его идентификатор
		 * @param string $name модуль типа
		 * @param string $title название типа
		 * @param string $ext метод типа
		 * @return int
		 */
		public function addType($name, $title, $ext = '');

		/**
		 * Удаляет иерархического тип и возвращает результат операции
		 * @param int $id id иерархического типа
		 * @return bool
		 */
		public function delType($id);

		/**
		 * Определяет, существует ли иерархический тип с указанным идентификатором
		 * @param int $id id типа
		 * @return bool
		 */
		public function isExists($id);

		/**
		 * Возвращает список всех иерархических типов
		 * @return iUmiHierarchyType[] где ключ это id типа
		 */
		public function getTypesList();

		/** Очищает кеш */
		public function clearCache();
	}
