<?php
	namespace UmiCms\System\Orm\Entity\Builder;

	use UmiCms\System\Orm\Entity\iBuilder;

	/**
	 * Трейт инжектора строителя сущностей
	 * @package UmiCms\System\Orm\Entity\Builder
	 */
	trait tInjector {

		/** @var iBuilder $builder строитель сущностей */
		private $builder;

		/**
		 * Возвращает строителя сущностей
		 * @return iBuilder
		 */
		protected function getBuilder() {
			return $this->builder;
		}

		/**
		 * Устанавливает строителя сущностей
		 * @param iBuilder $builder строитель
		 * @return $this
		 */
		protected function setBuilder(iBuilder $builder) {
			$this->builder = $builder;
			return $this;
		}
	}