<?php

	/** Трейт работника с коллекцией языков */
	trait tUmiLanguagesInjector {

		/** @var iLangsCollection $languagesCollection коллекция доменов */
		private $languagesCollection;

		/**
		 * Возвращает экземпляр коллекции языков
		 * @return iLangsCollection
		 * @throws Exception
		 */
		public function getLanguageCollection() {
			if (!$this->languagesCollection instanceof iLangsCollection) {
				throw new Exception('You should set iLangsCollection first');
			}

			return $this->languagesCollection;
		}

		/**
		 * Устанавливает экземпляр коллекции языков
		 * @param iLangsCollection $languagesCollection экземпляр коллекции языков
		 * @return iUmiLanguagesInjector|tUmiLanguagesInjector
		 */
		public function setLanguageCollection(iLangsCollection $languagesCollection) {
			$this->languagesCollection = $languagesCollection;
			return $this;
		}
	}
