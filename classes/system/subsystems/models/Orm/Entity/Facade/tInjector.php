<?php
	namespace UmiCms\System\Orm\Entity\Facade;

	use UmiCms\System\Orm\Entity\iFacade;

	/**
	 * Трейт инжектора фасада сущностей
	 * @package UmiCms\System\Orm\Entity\Facade
	 */
	trait tInjector {

		/** @var iFacade $facade фасад сущностей */
		private $facade;

		/**
		 * Устанавливает фасад сущностей
		 * @param iFacade $facade фасад сущностей
		 * @return $this
		 */
		protected function setFacade(iFacade $facade) {
			$this->facade = $facade;
			return $this;
		}

		/**
		 * Возвращает фасад сущностей
		 * @return iFacade
		 */
		protected function getFacade() {
			return $this->facade;
		}
	}