<?php

	namespace UmiCms\System\Hierarchy\Element;

	/**
	 * Интерфейс фабрики иерархических элементов (страниц)
	 * @package UmiCms\System\Hierarchy\Element;
	 */
	interface iFactory {

		/**
		 * Создает иерархический элемент (страницу) по его идентификатору
		 * @param int $id идентификатор
		 * @return \iUmiHierarchyElement
		 */
		public function createById($id);

		/**
		 * Создает иерархический элемент (страницу) по полному набору его свойств
		 * @param array $data полный набор свойств объекта
		 *
		 * [
		 *      0 => 'id',
		 *      1 => 'parent_id',
		 *      2 => 'type_id',
		 *      3 => 'lang_id',
		 *      4 => 'domain_id',
		 *      5 => 'tpl_id',
		 *      6 => 'object_id',
		 *      7 => 'ord',
		 *      8 => 'alt_name',
		 *      9 => 'is_active',
		 *      10 => 'is_visible',
		 *      11 => 'is_deleted',
		 *      12 => 'update_time',
		 *      13 => 'is_default',
		 *      14 => 'object: name',
		 *      15 => 'object: type_id',
		 *      16 => 'object: is_locked',
		 *      17 => 'object: owner_id',
		 *      18 => 'object: guid',
		 *      19 => 'object: type_guid',
		 *      20 => 'object: update_time',
		 *      21 => 'object: ord',
		 * ]
		 *
		 * @return \iUmiHierarchyElement
		 */
		public function createByData(array $data);
	}
