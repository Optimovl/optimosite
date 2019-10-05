<?php

	abstract class selectorOrderField {

		public $asc = true;

		public function asc() {
			$this->asc = true;
		}

		public function desc() {
			$this->asc = false;
		}

		public function rand() {
			$this->name = 'rand';
		}

		public function __get($prop) {
			if (isset($this->$prop)) {
				return $this->$prop;
			}
			return false;
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $prop имя свойства
		 * @return bool
		 */
		public function __isset($prop) {
			return property_exists(get_class($this), $prop);
		}
	}

	class selectorOrderFieldProp extends selectorOrderField {

		protected $fieldIdList;

		public function __construct(array $fieldIdList) {
			$this->fieldIdList = $fieldIdList;
		}

		/**
		 * Возвращает список идентификаторов полей
		 * @return array
		 */
		public function getFieldIdList() {
			return $this->fieldIdList;
		}
	}

	class selectorOrderSysProp extends selectorOrderField {

		public $name;

		public function __construct($name) {
			$this->name = $name;
		}
	}
