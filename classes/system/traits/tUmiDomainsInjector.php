<?php

	/** Трейт работника с коллекцией доменов */
	trait tUmiDomainsInjector {

		/** @var \iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/**
		 * Возвращает экземпляр коллекции доменов
		 * @return \iDomainsCollection
		 * @throws Exception
		 */
		public function getDomainCollection() {
			if (!$this->domainCollection instanceof iDomainsCollection) {
				throw new Exception('You should set iDomainsCollection first');
			}

			return $this->domainCollection;
		}

		/**
		 * Устанавливает подключение к базе данных
		 * @param \iDomainsCollection $domainCollection экземпляр коллекции доменов
		 * @return $this
		 */
		public function setDomainCollection(\iDomainsCollection $domainCollection) {
			$this->domainCollection = $domainCollection;
			return $this;
		}
	}
