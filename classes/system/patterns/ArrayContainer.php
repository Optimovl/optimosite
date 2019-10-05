<?php

	namespace UmiCms\System\Patterns;

	/**
	 * Класс контейнера массива
	 * @package UmiCms\System\Patterns
	 */
	abstract class ArrayContainer implements iArrayContainer, \Countable {

		/** @var array $array массив */
		protected $array = [];

		/** @inheritdoc */
		public function __construct(array $array = []) {
			$this->array = $array;
		}

		/** @inheritdoc */
		public function get($key) {
			if (!$this->isValidKey($key)) {
				return null;
			}

			return getArrayKey($this->array, $key);
		}

		/** @inheritdoc */
		public function isExist($key) {
			if (!$this->isValidKey($key)) {
				return false;
			}

			return array_key_exists($key, $this->array);
		}

		/** @inheritdoc */
		public function set($key, $value) {
			if (!$this->isValidKey($key)) {
				return $this;
			}

			$this->array[$key] = $value;
			return $this;
		}

		/** @inheritdoc */
		public function del($keyList) {
			$keyList = is_array($keyList) ? $keyList : [$keyList];

			foreach ($keyList as $key) {
				if (!$this->isValidKey($key)) {
					continue;
				}
				unset($this->array[$key]);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getArrayCopy() {
			return $this->array;
		}

		/** @inheritdoc */
		public function count() {
			return umiCount($this->getArrayCopy(), true);
		}

		/** @inheritdoc */
		public function clear() {
			$this->array = [];
			return $this;
		}

		/** @inheritdoc */
		public function __get($key) {
			return $this->get($key);
		}

		/** @inheritdoc */
		public function __isset($key) {
			return $this->isExist($key);
		}

		/** @inheritdoc */
		public function __set($key, $value) {
			return $this->set($key, $value);
		}

		/** @inheritdoc */
		public function __unset($keyList) {
			return $this->del($keyList);
		}

		/**
		 * Определяет валиден ли ключ для значения массива
		 * @param mixed $key
		 * @return bool
		 */
		protected function isValidKey($key) {
			return (is_string($key) || is_int($key));
		}
	}
