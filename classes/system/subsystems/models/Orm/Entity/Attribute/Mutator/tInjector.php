<?php
	namespace UmiCms\System\Orm\Entity\Attribute\Mutator;

	use UmiCms\System\Orm\Entity\iMutator;

	/**
	 * Трейт инжектора мутатора атрибутов сущности
	 * @package UmiCms\System\Orm\Entity\Attribute\Mutator
	 */
	trait tInjector {

		/** @var iMutator $attributeMutator мутатор атрибутов сущности */
		private $attributeMutator;

		/**
		 * Возвращает мутатор атрибутов сущности
		 * @return iMutator
		 */
		protected function getAttributeMutator() {
			return $this->attributeMutator;
		}

		/**
		 * Устанавливает мутатор атрибутов сущности
		 * @param iMutator $mutator мутатор атрибутов сущности
		 * @return $this
		 */
		protected function setAttributeMutator(iMutator $mutator) {
			$this->attributeMutator = $mutator;
			return $this;
		}
	}