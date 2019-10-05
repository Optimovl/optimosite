<?php

	namespace UmiCms\System\Selector;

	/**
	 * Класс фабрики селекторов
	 * @package UmiCms\System\Selector
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create($mode) {
			return new \selector($mode);
		}

		/** @inheritdoc */
		public function createObject() {
			return $this->create('objects');
		}

		/** @inheritdoc */
		public function createPage() {
			return $this->create('pages');
		}

		/** @inheritdoc */
		public function createPageTypeName($module, $method) {
			$selector = $this->createPage();
			$selector->types('object-type')->name($module, $method);
			return $selector;
		}

		/** @inheritdoc */
		public function createObjectTypeGuid($guid) {
			$selector = $this->createObject();
			$selector->types('object-type')->guid($guid);
			return $selector;
		}

		/** @inheritdoc */
		public function createObjectTypeId($id) {
			$selector = $this->createObject();
			$selector->types('object-type')->id($id);
			return $selector;
		}

		/** @inheritdoc */
		public function createObjectTypeName($module, $method) {
			$selector = $this->createObject();
			$selector->types('object-type')->name($module, $method);
			return $selector;
		}
	}
