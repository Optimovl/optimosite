<?php

	namespace UmiCms\System\Hierarchy\Domain;

	use UmiCms\System\Request\Http\iRequest;

	/**
	 * Интерфейс определителя запрошенного домена
	 * @package UmiCms\System\Hierarchy\Domain
	 */
	interface iDetector {

		/**
		 * Конструктор
		 * @param \iDomainsCollection $domainCollection коллекция домена
		 * @param iRequest $httpRequest http запрос
		 */
		public function __construct(\iDomainsCollection $domainCollection, iRequest $httpRequest);

		/**
		 * Определяет запрошенный домен
		 * @return \iDomain
		 * @throws \coreException
		 */
		public function detect();

		/**
		 * Определяет идентификатор запрошенного домена
		 * @return int
		 * @throws \coreException
		 */
		public function detectId();

		/**
		 * Определяет хост запрошенного домена
		 * @return string
		 * @throws \coreException
		 */
		public function detectHost();

		/**
		 * Определяет url запрошенного домена
		 * @return string
		 * @throws \coreException
		 */
		public function detectUrl();
	}
