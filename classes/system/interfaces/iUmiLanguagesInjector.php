<?php

	/** Интерфейс работника с коллекцией языков */
	interface iUmiLanguagesInjector {

		/**
		 * Возвращает экземпляр коллекции языков
		 * @return iLangsCollection
		 * @throws Exception
		 */
		public function getLanguageCollection();

		/**
		 * Устанавливает экземпляр коллекции языков
		 * @param iLangsCollection $languagesCollection экземпляр коллекции языков
		 * @return iUmiLanguagesInjector
		 */
		public function setLanguageCollection(iLangsCollection $languagesCollection);
	}
