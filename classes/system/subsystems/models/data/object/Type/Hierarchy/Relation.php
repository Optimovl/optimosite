<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy;

	/**
	 * Класс иерархической связи между объектными типа данных
	 * @package UmiCms\System\Data\Object\Type\Hierarchy
	 */
	class Relation implements iRelation {

		/** @var int $id идентификатор связи */
		private $id;

		/** @var int $parentId идентификатор родительского объектного типа данных */
		private $parentId;

		/** @var int $childId идентификатор дочернего объектного типа данных */
		private $childId;

		/** @var int $level уровень вложенности иерархии */
		private $level;

		/** @inheritdoc */
		public function __construct(array $data) {
			foreach (['id', 'parent_id', 'child_id', 'level'] as $key) {
				if (!array_key_exists($key, $data)) {
					throw new \RuntimeException('Incorrect object type relation data given');
				}
			}
			$this->setId($data['id']);
			$this->setParentId($data['parent_id']);
			$this->setChildId($data['child_id']);
			$this->setLevel($data['level']);
		}

		/** @inheritdoc */
		public function getId() {
			return $this->id;
		}

		/** @inheritdoc */
		public function getParentId() {
			return $this->parentId;
		}

		/** @inheritdoc */
		public function setParentId($id) {
			if ($id == $this->getChildId() && $this->getChildId() !== null) {
				throw new \RuntimeException('Prevent cyclic relation: incorrect parent_id');
			}

			$this->parentId = (int) $id;
			return $this;
		}

		/** @inheritdoc */
		public function getChildId() {
			return $this->childId;
		}

		/** @inheritdoc */
		public function setChildId($id) {
			if ($id == $this->getParentId()) {
				throw new \RuntimeException('Prevent cyclic relation: incorrect child_id');
			}

			$this->childId = (int) $id;
			return $this;
		}

		/** @inheritdoc */
		public function getLevel() {
			return $this->level;
		}

		/**
		 * Устанавливает идентификатор связи
		 * @param int $id идентификатор связи
		 * @return $this
		 */
		private function setId($id) {
			$this->id = (int) $id;
			return $this;
		}

		/**
		 * Устанавливает уровень вложенности иерархии
		 * @param int $level уровень вложенности иерархии
		 * @return $this
		 */
		private function setLevel($level) {
			$this->level = (int) $level;
			return $this;
		}
	}
