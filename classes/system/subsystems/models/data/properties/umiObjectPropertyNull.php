<?php

	/** Класс заглушки поля объекта. */
	class umiObjectPropertyNull extends umiObjectProperty {

		/** @var array $storage хранилище поля */
		private $storage = [];

		/** @inheritdoc */
		public function __construct($id, $row = false) {
			$this->setId($id);
			$this->value = $this->loadValue();
		}

		/**
		 * Возвращает содержимое хранилища поля
		 * @return array
		 */
		public function getStorage() {
			return $this->storage;
		}

		/** @inheritdoc */
		public function getName() {
			return __CLASS__;
		}

		/** @inheritdoc */
		public function getDataType() {
			return 'nullType';
		}

		/** @inheritdoc */
		public function setValue($value) {
			if ($this->value !== $value) {
				$this->value = $value;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function getValue(array $params = null) {
			return $this->value;
		}

		/** @inheritdoc */
		protected function loadValue() {
			return $this->storage;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->storage = (array) $this->value;
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			return true;
		}
	}
