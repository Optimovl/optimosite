<?php

	namespace UmiCms\System\Selector;

	/**
	 * Интерфейс фабрики селекторов
	 * @package UmiCms\System\Selector
	 */
	interface iFactory {

		/**
		 * Создает селектор
		 * @param string $mode режим (objects/pages)
		 * @return \selector
		 */
		public function create($mode);

		/**
		 * Создает селектор объектов
		 * @return \selector
		 */
		public function createObject();

		/**
		 * Создает селектор страниц
		 * @return \selector
		 */
		public function createPage();

		/**
		 * Создает селектор страниц типа с заданным именем
		 * @param string $module модуль типа
		 * @param string $method метод типа
		 * @return \selector
		 */
		public function createPageTypeName($module, $method);

		/**
		 * Создает селектор объектов типа с заданным гуидом
		 * @param string $guid гуид типа
		 * @return \selector
		 */
		public function createObjectTypeGuid($guid);

		/**
		 * Создает селектор объектов типа с заданным идентификатором
		 * @param int $id идентификатор типа
		 * @return \selector
		 */
		public function createObjectTypeId($id);

		/**
		 * Создает селектор объектов типа с заданным именем
		 * @param string $module модуль типа
		 * @param string $method метод типа
		 * @return \selector
		 */
		public function createObjectTypeName($module, $method);
	}
