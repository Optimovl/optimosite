<?php

	namespace UmiCms\System\Hierarchy\Domain;

	use UmiCms\System\Request\Http\iRequest;

	/**
	 * Класс определителя запрошенного домена
	 * @package UmiCms\System\Hierarchy\Domain
	 */
	class Detector implements iDetector {

		/** @var \iDomainsCollection $domainCollection */
		private $domainCollection;

		/** @var iRequest $httpRequest */
		private $httpRequest;

		/** @inheritdoc */
		public function __construct(\iDomainsCollection $domainCollection, iRequest $httpRequest) {
			$this->domainCollection = $domainCollection;
			$this->httpRequest = $httpRequest;
		}

		/** @inheritdoc */
		public function detect() {
			$requestDomain = $this->getRequestDomain();
			$defaultDomain = $this->getDefaultDomain();

			switch (true) {
				case ($requestDomain instanceof \iDomain) : {
					return $requestDomain;
				}
				case ($defaultDomain instanceof \iDomain) : {
					return $defaultDomain;
				}
				default : {
					throw new \coreException('Cannot detect current domain');
				}
			}
		}

		/** @inheritdoc */
		public function detectId() {
			return $this->detect()
				->getId();
		}

		/** @inheritdoc */
		public function detectHost() {
			return $this->detect()
				->getHost();
		}

		/** @inheritdoc */
		public function detectUrl() {
			return $this->detect()
				->getUrl();
		}

		/**
		 * Возвращает запрошенный домен
		 * @return bool|\iDomain
		 */
		private function getRequestDomain() {
			$host = $this->getHttpRequest()
				->host();
			return $this->getDomainCollection()
				->getDomainByHost($host);
		}

		/**
		 * Возврашает домен по умолчанию
		 * @return bool|\iDomain
		 */
		private function getDefaultDomain() {
			return $this->getDomainCollection()
				->getDefaultDomain();
		}

		/**
		 * Возвращает коллекцию доменов
		 * @return \iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}

		/**
		 * Возвращает http запрос
		 * @return iRequest
		 */
		private function getHttpRequest() {
			return $this->httpRequest;
		}
	}
