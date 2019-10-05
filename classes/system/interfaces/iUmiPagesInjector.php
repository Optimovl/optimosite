<?php

	/** Интерфейс работника с коллекцией страниц (иерархией) */
	interface iUmiPagesInjector {

		/**
		 * Устанавливает экземпляр коллекции страниц системы
		 * @param \iUmiHierarchy $pages экземпляр коллекции страниц системы
		 * @return iUmiPagesInjector
		 */
		public function setPagesCollection(\iUmiHierarchy $pages);

		/**
		 * Возвращает экземпляр коллекции страниц системы
		 * @return \iUmiHierarchy
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getPagesCollection();
	}
