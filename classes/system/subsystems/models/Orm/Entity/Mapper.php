<?php
	namespace UmiCms\System\Orm\Entity;

	/**
	 * Абстрактный класс маппера сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Mapper implements iMapper {

		/** @inheritdoc */
		public function getAttributeSchemaList() {
			return [
				self::ID => [
					'getId',
					'setId',
					'int'
				]
			];
		}

		/** @inheritdoc */
		public function getAttributeList() {
			return array_keys($this->getAttributeSchemaList());
		}

		/** @inheritdoc */
		public function isExistsAttribute($name) {
			if (!is_string($name) && !is_int($name)) {
				return false;
			}

			return isset($this->getAttributeSchemaList()[$name]);
		}

		/** @inheritdoc */
		public function getAttributeSchema($name) {
			if (!$this->isExistsAttribute($name)) {
				throw new \ErrorException(sprintf('Incorrect attribute name given: "%s"', $name));
			}

			return $this->getAttributeSchemaList()[$name];
		}

		/** @inheritdoc */
		public function getRelationSchemaList() {
			return [];
		}

		/** @inheritdoc */
		public function getRelationList() {
			return array_keys($this->getRelationSchemaList());
		}

		/** @inheritdoc */
		public function isExistsRelation($name) {
			if (!is_string($name) && !is_int($name)) {
				return false;
			}

			return isset($this->getRelationSchemaList()[$name]);
		}

		/** @inheritdoc */
		public function getRelationSchema($name) {
			if (!$this->isExistsRelation($name)) {
				throw new \ErrorException(sprintf('Incorrect relation name given: "%s"', $name));
			}

			return $this->getRelationSchemaList()[$name];
		}
	}