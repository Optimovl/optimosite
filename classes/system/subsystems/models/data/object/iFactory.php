<?php

	namespace UmiCms\System\Data\Object;

	/**
	 * Интерфейс фабрики объектов данных
	 * @package UmiCms\System\Data\Object
	 */
	interface iFactory {

		/**
		 * Создает объект данных по его идентификатору
		 * @param int $id идентификатор
		 * @return \iUmiObject
		 */
		public function createById($id);

		/**
		 * Создает объект данных по полному набору его свойств
		 * @param array $data полный набор свойств объекта
		 *
		 * [
		 *      0 => 'id',
		 *      1 => 'name',
		 *      2 => 'type_id',
		 *      3 => 'is_locked',
		 *      4 => 'owner_id',
		 *      5 => 'guid',
		 *      6 => 'type_guid',
		 *      7 => 'updateTime',
		 *      8 => 'ord'
		 * ]
		 *
		 * @return \iUmiObject
		 */
		public function createByData(array $data);
	}
