<?php
	namespace UmiCms\System\Orm\Entity\Factory;

	use UmiCms\System\Orm\Entity\iFactory;

	/**
	 * Трейт инжектора фабрики сущностей
	 * @package UmiCms\System\Orm\Entity\Factory
	 */
	trait tInjector {

		/** @var iFactory $factory фабрика сущностей */
		private $factory;

		/**
		 * Возвращает фабрику сущностей
		 * @return iFactory
		 */
		protected function getFactory() {
			return $this->factory;
		}

		/**
		 * Устанавливает фабрику сущностей
		 * @param iFactory $factory фабрика сущностей
		 * @return $this
		 */
		protected function setFactory(iFactory $factory) {
			$this->factory = $factory;
			return $this;
		}
	}