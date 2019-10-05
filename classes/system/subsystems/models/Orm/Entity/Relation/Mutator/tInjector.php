<?php
	namespace UmiCms\System\Orm\Entity\Relation\Mutator;

	use UmiCms\System\Orm\Entity\iMutator;

	/**
	 * Трейт инжектора мутатора связей сущности
	 * @package UmiCms\System\Orm\Entity\Relation\Mutator
	 */
	trait tInjector {

		/** @var iMutator $relationMutator мутатор связей сущности */
		private $relationMutator;

		/**
		 * Возвращает мутатор связей сущности
		 * @return iMutator
		 */
		protected function getRelationMutator() {
			return $this->relationMutator;
		}

		/**
		 * Устанавливает мутатор связей сущности
		 * @param iMutator $mutator мутатор связей сущности
		 * @return $this
		 */
		protected function setRelationMutator(iMutator $mutator) {
			$this->relationMutator = $mutator;
			return $this;
		}
	}