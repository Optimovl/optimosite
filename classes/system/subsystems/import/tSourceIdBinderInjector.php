<?php

	namespace UmiCms\System\Import;

	/**
	 * Инжектор экземпляра класса, связующего идентификаторы импортируемых сущностей
	 * @package UmiCms\System\Import
	 */
	trait tSourceIdBinderInjector {

		/**
		 * @var \iUmiImportRelations|null $sourceIdBinder экземпляр класса,
		 * связующего идентификаторы импортируемых сущностей
		 */
		private $sourceIdBinder;

		/**
		 * Устанавливает экземпляр класса, связующего идентификаторы импортируемых сущностей
		 * @param \iUmiImportRelations $sourceIdBinder класса, связующего идентификатору импортируемых сущностей
		 * @return $this
		 */
		public function setSourceIdBinder(\iUmiImportRelations $sourceIdBinder) {
			$this->sourceIdBinder = $sourceIdBinder;
			return $this;
		}

		/**
		 * Возвращает экземпляр класса, связующего идентификаторы импортируемых сущностей
		 * @return \iUmiImportRelations
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getSourceIdBinder() {
			if (!$this->sourceIdBinder instanceof \iUmiImportRelations) {
				throw new \RequiredPropertyHasNoValueException('You should inject \iUmiImportRelations first');
			}

			return $this->sourceIdBinder;
		}
	}
