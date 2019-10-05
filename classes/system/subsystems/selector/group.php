<?php

	abstract class selectorGroupField {

		public function __get($prop) {
			return $this->$prop;
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

	class selectorGroupFieldProp extends selectorGroupField {

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

	class selectorGroupSysProp extends selectorGroupField {

		protected $name;

		public function __construct($name) {
			$this->name = $name;
		}
	}
