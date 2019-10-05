<?php

	/** Интерфейс работника с коллекцией доменов */
	interface iUmiDomainsInjector {

		/**
		 * Возвращает экземпляр коллекции доменов
		 * @return \iDomainsCollection
		 * @throws Exception
		 */
		public function getDomainCollection();

		/**
		 * Устанавливает подключение к базе данных
		 * @param \iDomainsCollection $domainCollection экземпляр коллекции доменов
		 * @return $this
		 */
		public function setDomainCollection(\iDomainsCollection $domainCollection);
	}
