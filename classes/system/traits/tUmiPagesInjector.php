<?php

	/** Трейт работника с коллекцией страниц (иерархией) */
	trait tUmiPagesInjector {

		/** @var \iUmiHierarchy $pagesCollection коллекция страниц */
		private $pagesCollection;

		/**
		 * Устанавливает экземпляр коллекции страниц системы
		 * @param \iUmiHierarchy $pages экземпляр коллекции страниц системы
		 * @return $this
		 */
		public function setPagesCollection(\iUmiHierarchy $pages) {
			$this->pagesCollection = $pages;
			return $this;
		}

		/**
		 * Возвращает экземпляр коллекции страниц системы
		 * @return \iUmiHierarchy
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getPagesCollection() {
			if (!$this->pagesCollection instanceof \iUmiHierarchy) {
				throw new \RequiredPropertyHasNoValueException('You should inject \iUmiHierarchy first');
			}

			return $this->pagesCollection;
		}
	}
