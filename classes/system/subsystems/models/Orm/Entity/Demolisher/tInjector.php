<?php
	namespace UmiCms\System\Orm\Entity\Demolisher;

	use UmiCms\System\Orm\Entity\iDemolisher;

	/**
	 * Трейт инжектора класса удаления импортированных сущностей
	 * @package UmiCms\System\Orm\Entity\Demolisher
	 */
	trait tInjector {

		/** @var iDemolisher $demolisher экземпляр класса удаления импортированных сущностей */
		private $demolisher;

		/**
		 * Возвращает экземпляр класса удаления импортированных сущностей
		 * @return iDemolisher
		 */
		protected function getDemolisher() {
			return $this->demolisher;
		}

		/**
		 * Устанавливает экземпляр класса удаления импортированных сущностей
		 * @param iDemolisher $demolisher экземпляр класса удаления импортированных сущностей
		 * @return $this
		 */
		protected function setDemolisher(iDemolisher $demolisher) {
			$this->demolisher = $demolisher;
			return $this;
		}
	}