<?php

	namespace UmiCms\System\Data\Object;

	/**
	 * Класс фабрики объектов данных
	 * @package UmiCms\System\Data\Object
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function createById($id) {
			return $this->create($id);
		}

		/** @inheritdoc */
		public function createByData(array $data) {
			$id = array_shift($data);

			if (count($data) !== \umiObject::INSTANCE_ATTRIBUTE_COUNT) {
				throw new \privateException('Incorrect data for object given: ' . var_export($data, true));
			}

			return $this->create($id, $data);
		}

		/**
		 * Создает объект данных
		 * @param int $id идентификатор объекта
		 * @param array|bool $data набор свойств объекта или false
		 *
		 * [
		 *      0 => 'name',
		 *      1 => 'type_id',
		 *      2 => 'is_locked',
		 *      3 => 'owner_id',
		 *      4 => 'guid',
		 *      5 => 'type_guid',
		 *      6 => 'updateTime',
		 *      7 => 'ord'
		 * ]
		 *
		 * @return \iUmiObject
		 * @throws \privateException
		 */
		private function create($id, $data = false) {
			return new \umiObject($id, $data);
		}
	}
