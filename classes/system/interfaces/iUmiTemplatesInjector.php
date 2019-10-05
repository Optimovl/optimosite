<?php

	/** Интерфейс работника с коллекцией шаблонов */
	interface iUmiTemplatesInjector {

		/**
		 * Устанавливает экземпляр коллекции шаблонов системы
		 * @param \iTemplatesCollection $templates экземпляр коллекции шаблонов системы
		 * @return iUmiTemplatesInjector
		 */
		public function setTemplatesCollection(\iTemplatesCollection $templates);

		/**
		 * Возвращает экземпляр коллекции шаблонов системы
		 * @return \iTemplatesCollection
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getTemplatesCollection();
	}
