<?php
	namespace UmiCms\System\Orm\Entity\Schema;

	use UmiCms\System\Orm\Entity\iSchema;

	/**
	 * Трейт инжектора схемы хранения сущностей
	 * @package UmiCms\System\Orm\Entity\Schema
	 */
	trait tInjector {

		/** @var iSchema $schema схема хранения сущности */
		private $schema;

		/**
		 * Возвращает схему хранения сущностей
		 * @return iSchema
		 */
		protected function getSchema() {
			return $this->schema;
		}

		/**
		 * Устанавливает схему хранения сущностей
		 * @param iSchema $schema схема
		 * @return $this
		 */
		protected function setSchema(iSchema $schema) {
			$this->schema = $schema;
			return $this;
		}
	}