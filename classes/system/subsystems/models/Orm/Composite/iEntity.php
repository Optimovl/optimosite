<?php
	namespace UmiCms\System\Orm\Composite;

	use UmiCms\System\Orm\Entity\iBuilder;
	use UmiCms\System\Orm\iEntity as iAbstractEntity;

	/**
	 * Интерфейс составной сущности
	 * @package UmiCms\System\Orm\Composite
	 */
	interface iEntity extends iAbstractEntity {

		/**
		 * Конструктор
		 * @param iBuilder $builder строитель сущностей
		 */
		public function __construct(iBuilder $builder);

		/**
		 * Загружает зависимые сущности
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function loadAllRelations();

		/**
		 * Загружает зависимую сущность
		 * @param string $name имя связи
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function loadRelation($name);
	}