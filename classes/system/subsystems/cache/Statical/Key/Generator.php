<?php

	namespace UmiCms\System\Cache\Statical\Key;

	use UmiCms\System\Request\iFacade as iRequest;

	/**
	 * Класс генератора ключей для статического кеша
	 * @package UmiCms\System\Cache\Statical\Key
	 */
	class Generator implements iGenerator {

		/** @var iRequest $request запрос */
		private $request;

		/** @var \iUmiHierarchy $pageCollection коллекция страниц */
		private $pageCollection;

		/** @var \iConfiguration $config конфигурация */
		private $config;

		/** @var \iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/** @const string MOBILE_PREFIX префикс ключа для запроса с мобильного утройства */
		const MOBILE_PREFIX = '/mobile';

		/** @const string DEFAULT_PREFIX префикс по умолчанию */
		const DEFAULT_PREFIX = '';

		/** @inheritdoc */
		public function __construct(
			iRequest $request,
			\iUmiHierarchy $pageCollection,
			\iConfiguration $config,
			\iDomainsCollection $domainsCollection
		) {
			$this->request = $request;
			$this->pageCollection = $pageCollection;
			$this->config = $config;
			$this->domainCollection = $domainsCollection;
		}

		/** @inheritdoc */
		public function getKey() {
			$prefix = $this->isMobile() ? self::MOBILE_PREFIX : self::DEFAULT_PREFIX;
			$host = $this->getHost();
			$url = $this->getUrl();
			return $this->glueKey($prefix, $host, $url);
		}

		/** @inheritdoc */
		public function getKeyList($id) {
			$pageCollection = $this->getPageCollection();
			$element = $pageCollection->getElement($id);

			if (!$element instanceof \iUmiHierarchyElement) {
				return [];
			}

			$domain = $this->getDomainCollection()
				->getDomain($element->getDomainId());

			if (!$domain instanceof \iDomain) {
				return [];
			}

			$url = $element->getIsDefault() ? '' : ltrim($pageCollection->getPathById($id), '/');
			$url = str_replace($domain->getUrl() . '/', '', $url);
			$pathList = [];

			foreach ($this->getPathPartList($domain, $url) as $pathPart) {
				list($prefix, $host, $url) = $pathPart;
				$pathList[] = $this->glueKey($prefix, $host, $url);
			}

			return $pathList;
		}

		/**
		 * Возвращает части путей для формирования ключей страницы
		 * @param \iDomain $domain домен страницы
		 * @param string $url адрес страницы
		 * @return array
		 *
		 * [
		 *      [
		 *          'prefix',
		 *          'host',
		 *          'url'
		 *      ]
		 * ]
		 */
		private function getPathPartList(\iDomain $domain, $url) {
			$domainHost = $this->filterWWW($domain->getHost(true));
			$pathPartList = [
				[
					self::MOBILE_PREFIX,
					$domainHost,
					$url
				],
				[
					self::DEFAULT_PREFIX,
					$domainHost,
					$url
				]
			];

			foreach ($domain->getMirrorsList() as $mirror) {
				$mirrorHost = $this->filterWWW($mirror->getHost(true));

				$pathPartList[] = [
					self::MOBILE_PREFIX,
					$mirrorHost,
					$url
				];

				$pathPartList[] = [
					self::DEFAULT_PREFIX,
					$mirrorHost,
					$url
				];
			}

			return $pathPartList;
		}

		/**
		 * Определяет требуется ли хранить кеш для мобильных устройств отдельно
		 * @return bool
		 */
		private function isMobile() {
			return $this->getRequest()->isMobile() && $this->getConfig()
					->get('cache', 'static.cache-for-mobile-devices');
		}

		/**
		 * Склеивает ключ
		 * @param string $prefix префикс ключа
		 * @param string $host домен (без www) в punycode
		 * @param string $url адрес страницы с параметрами запроса или без них
		 * @return string
		 */
		private function glueKey($prefix, $host, $url) {
			return sprintf('%s/%s/%s', $prefix, $host, $url);
		}

		/**
		 * Возвращает адрес текущей страницы с параметрами запроса или без них
		 * @return string
		 */
		private function getUrl() {
			return trim($this->getRequest()->uri(), '/');
		}

		/**
		 * Возвращает текущий домен в punycode
		 * @return string
		 */
		private function getHost() {
			$host = $this->filterWWW($this->getRequest()->host());
			return trim($host, '/');
		}

		/**
		 * Убирает "www" из домена, если это необходимо
		 * @param string $host домен
		 * @return string
		 */
		private function filterWWW($host) {
			$filterWWW = (bool) $this->getConfig()
				->get('cache', 'static.filter-www');
			$host = $filterWWW ? preg_replace('/^www\./i', '', $host) : $host;
			return $host;
		}

		/**
		 * Возвращает запрос
		 * @return iRequest
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает коллекцию страниц
		 * @return \iUmiHierarchy
		 */
		private function getPageCollection() {
			return $this->pageCollection;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfig() {
			return $this->config;
		}

		/**
		 * Возвращает коллекцию доменов
		 * @return \iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}
	}
