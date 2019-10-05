<?php

	namespace UmiCms\System\Hierarchy\Element;

	/**
	 * Класс фабрики иерархических элементов (страниц)
	 * @package UmiCms\System\Hierarchy\Element;
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function createById($id) {
			return $this->create($id);
		}

		/** @inheritdoc */
		public function createByData(array $data) {
			$id = array_shift($data);

			if (count($data) !== \umiHierarchyElement::INSTANCE_ATTRIBUTE_COUNT) {
				throw new \privateException('Incorrect data for page given: ' . var_export($data, true));
			}

			return $this->create($id, $data);
		}

		/**
		 * Создает иерархический элемент (страницу)
		 * @param int $id идентификатор элемента
		 * @param array|bool $data набор свойств элемента или false
		 *
		 * [
		 *      0 => 'parent_id',
		 *      1 => 'type_id',
		 *      2 => 'lang_id',
		 *      3 => 'domain_id',
		 *      4 => 'tpl_id',
		 *      5 => 'object_id',
		 *      6 => 'ord',
		 *      7 => 'alt_name',
		 *      8 => 'is_active',
		 *      9 => 'is_visible',
		 *      10 => 'is_deleted',
		 *      11 => 'update_time',
		 *      12 => 'is_default',
		 *      13 => 'object: name',
		 *      14 => 'object: type_id',
		 *      15 => 'object: is_locked',
		 *      16 => 'object: owner_id',
		 *      17 => 'object: guid',
		 *      18 => 'object: type_guid',
		 *      19 => 'object: update_time',
		 *      20 => 'object: ord',
		 * ]
		 *
		 * @return \iUmiHierarchyElement
		 * @throws \privateException
		 */
		private function create($id, $data = false) {
			return new \umiHierarchyElement($id, $data);
		}
	}
