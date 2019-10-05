<?php
	namespace UmiCms\System\Orm\Entity\Importer;

	use UmiCms\System\Orm\Entity\iImporter;

	/**
	 * Трейт инжектора импортера сущностей
	 * @package UmiCms\System\Orm\Entity\Importer
	 */
	trait tInjector {

		/** @var iImporter $importer импортер сущностей */
		private $importer;

		/**
		 * Возвращает импортер сущностей
		 * @return iImporter
		 */
		protected function getImporter() {
			return $this->importer;
		}

		/**
		 * Устанавливает импортер сущностей
		 * @param iImporter $importer импортер сущностей
		 * @return $this
		 */
		protected function setImporter(iImporter $importer) {
			$this->importer = $importer;
			return $this;
		}
	}