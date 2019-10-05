<?php

	namespace UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory;

	use UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\iFactory;

	/**
	 * Трейт инжектора фабрики класса, связующего идентификатору импортируемых сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory
	 */
	trait Injector {

		/** @var iFactory $sourceIdBinderFactory фабрика класса, связующего идентификатору импортируемых сущностей */
		private $sourceIdBinderFactory;

		/**
		 * Устанавливает фабрику класса, связующего идентификатору импортируемых сущностей
		 * @param iFactory $factory
		 * @return $this
		 */
		public function setSourceIdBinderFactory(iFactory $factory) {
			$this->sourceIdBinderFactory = $factory;
			return $this;
		}

		/**
		 * Возвращает фабрику класса, связующего идентификатору импортируемых сущностей
		 * @return iFactory
		 */
		public function getSourceIdBinderFactory() {
			return $this->sourceIdBinderFactory;
		}
	}
