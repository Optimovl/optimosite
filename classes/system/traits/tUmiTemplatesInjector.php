<?php

	/** Трейт работника с коллекцией шаблонов */
	trait tUmiTemplatesInjector {

		/** @var \iTemplatesCollection $templatesCollection коллекция шаблонов */
		private $templatesCollection;

		/**
		 * Устанавливает экземпляр коллекции шаблонов системы
		 * @param \iTemplatesCollection $templates экземпляр коллекции шаблонов системы
		 * @return $this
		 */
		public function setTemplatesCollection(\iTemplatesCollection $templates) {
			$this->templatesCollection = $templates;
			return $this;
		}

		/**
		 * @return \iTemplatesCollection
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getTemplatesCollection() {
			if (!$this->templatesCollection instanceof \iTemplatesCollection) {
				throw new \RequiredPropertyHasNoValueException('You should inject \iTemplatesCollection first');
			}

			return $this->templatesCollection;
		}
	}
