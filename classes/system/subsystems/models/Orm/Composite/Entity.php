<?php
	namespace UmiCms\System\Orm\Composite;

	use UmiCms\System\Orm\Entity\iBuilder;
	use UmiCms\System\Orm\Entity as AbstractEntity;

	/**
	 * Абстрактный класс составной сущности
	 * @package UmiCms\System\Orm\Composite
	 */
	abstract class Entity extends AbstractEntity implements iEntity {

		/** @var iBuilder $builder строитель сущностей */
		private $builder;

		/**
		 * Конструктор
		 * @param iBuilder $builder строитель сущностей
		 */
		public function __construct(iBuilder $builder) {
			$this->setBuilder($builder);
		}

		/**
		 * Загружает зависимые сущности
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function loadAllRelations() {
			$this->getBuilder()
				->buildAllRelations($this);
			return $this;
		}

		/**
		 * Загружает зависимую сущность
		 * @param string $name имя связи
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function loadRelation($name) {
			$this->getBuilder()
				->buildOneRelation($this, $name);
			return $this;
		}

		/**
		 * Возвращает строителя сущностей
		 * @return iBuilder
		 */
		protected function getBuilder() {
			return $this->builder;
		}

		/**
		 * Устанавливает строителя сущностей
		 * @param iBuilder $builder строитель сущностей
		 * @return $this
		 */
		protected function setBuilder(iBuilder $builder) {
			$this->builder = $builder;
			return $this;
		}
	}