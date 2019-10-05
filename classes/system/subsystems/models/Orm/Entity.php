<?php
	namespace UmiCms\System\Orm;

	/**
	 * Класс абстрактной сущности
	 * @package UmiCms\System\Orm
	 */
	abstract class Entity implements iEntity {

		/** @var int|null $id идентификатор */
		private $id;

		/** @var bool $isUpdated была ли обновлена сущность */
		private $isUpdated = false;

		/** @inheritdoc */
		public function getId() {
			return $this->id;
		}

		/** @inheritdoc */
		public function setId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect entity id given');
			}

			return $this->setDifferentValue('id', $id);
		}

		/** @inheritdoc */
		public function hasId() {
			return $this->getId() !== null;
		}

		/** @inheritdoc */
		public function isUpdated() {
			return $this->isUpdated;
		}

		/** @inheritdoc */
		public function setUpdated($flag = true) {
			if (!is_bool($flag)) {
				throw new \ErrorException('Incorrect entity update status given');
			}

			$this->isUpdated = $flag;
			return $this;
		}

		/**
		 * Устанавливает значение свойства в случае, если оно отличается от текущего
		 * @param string $property имя свойства
		 * @param mixed $value значение
		 * @return $this
		 * @throws \ErrorException
		 */
		protected function setDifferentValue($property, $value) {
			if ($this->$property !== $value) {
				$this->$property = $value;
				$this->setUpdated();
			}

			return $this;
		}
	}