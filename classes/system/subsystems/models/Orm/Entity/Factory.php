<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;

	/**
	 * Абстрактный класс фабрики сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Factory implements iFactory {

		/** @var iEntity $dummyEntity шаблонная сущность */
		private $dummyEntity;

		/** @inheritdoc */
		public function __construct(iEntity $dummyEntity) {
			$this->setDummyEntity($dummyEntity);
		}

		/** @inheritdoc */
		public function create() {
			return clone $this->getDummyEntity();
		}

		/**
		 * Устанавливает шаблонную сущность
		 * @param iEntity $dummyEntity шаблонная сущность
		 * @return $this
		 */
		protected function setDummyEntity(iEntity $dummyEntity) {
			$this->dummyEntity = $dummyEntity;
			return $this;
		}

		/**
		 * Возвращает шаблонную сущность
		 * @return iEntity
		 */
		protected function getDummyEntity() {
			return $this->dummyEntity;
		}
	}