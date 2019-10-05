<?php

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Idn\iConverter;

	/** Класс коллекция доменов. @todo: вынести из класса репозиторий и фабрику */
	class domainsCollection implements iDomainsCollection {

		/** @var IConnection $connection соединение с базой данных */
		private $connection;

		/** @var iConverter $idnConverter Idn конвертер */
		private $idnConverter;

		/** @var iDomain[] $domainList список доменов системы */
		private $domainList = [];

		/** @var iDomain[] $hostMap список доменов системы, где ключ это хост */
		private $hostMap = [];

		/** @inheritdoc */
		public function __construct(IConnection $connection, iConverter $idnConverter) {
			$this->connection = $connection;
			$this->idnConverter = $idnConverter;
		}

		/** @inheritdoc */
		public function addDomain($host, $languageId = false, $isDefault = false, $usingSsl = false) {
			$id = $this->getDomainId($host);
			if ($id) {
				throw new coreException("Domain #{$host} already exist.");
			}

			$langs = Service::LanguageCollection();
			if (!$langs->isExists($languageId)) {
				if ($langs->getDefaultLang()) {
					$languageId = $langs->getDefaultLang()->getId();
				} else {
					throw new coreException("Language $languageId doesn't exist.");
				}
			}

			$connection = $this->getConnection();
			$connection->startTransaction("Create domain {$host}");

			try {
				$isDefault = (int) $isDefault;
				$usingSsl = (int) $usingSsl;
				$languageId = (int) $languageId;
				$sql = "INSERT INTO `cms3_domains` VALUES (null, '%s', %d, %d, %d, null)";
				$sql = sprintf($sql, $host, $isDefault, $languageId, $usingSsl);
				$connection->query($sql);

				$id = $connection->insertId();

				$domain = new domain($id);
				$domain->setHost($host);
				$domain->setIsDefault($isDefault);
				$domain->setDefaultLangId($languageId);
				$domain->setUsingSsl($usingSsl);
				$domain->commit();
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();
			$this->setDomain($domain);

			if ($isDefault) {
				$this->setDefaultDomain($id);
			}

			return $id;
		}

		/** @inheritdoc */
		public function delDomain($id) {
			if (!$this->isExists($id)) {
				throw new coreException("Domain #{$id} doesn't exist.");
			}

			$connection = $this->getConnection();
			$connection->startTransaction("Delete domain #{$id}");

			try {
				$domain = $this->getDomain($id);
				$domain->delAllMirrors();

				$escapedId = (int) $id;
				$sql = "DELETE FROM `cms3_domains` WHERE `id` = $escapedId";
				$connection->query($sql);
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();
			$this->unsetDomain($domain);
			unset($domain);

			return true;
		}

		/** @inheritdoc */
		public function getDomain($id) {
			return $this->isExists($id) ? $this->getList()[$id] : false;
		}

		/** @inheritdoc */
		public function getDefaultDomain() {
			foreach ($this->getList() as $domain) {
				if ($domain->getIsDefault()) {
					return $domain;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function setDefaultDomain($id) {
			if (!$this->isExists($id)) {
				throw new coreException("Domain #{$id} doesn't exist.");
			}

			$connection = $this->getConnection();
			$connection->startTransaction("Set default domain #{$id}");

			try {
				$oldDefaultDomain = $this->getDefaultDomain();

				if ($oldDefaultDomain instanceof iDomain) {
					$oldDefaultDomain->setIsDefault(false);
					$oldDefaultDomain->commit();
				}

				$newDefaultDomain = $this->getDomain($id);
				$newDefaultDomain->setIsDefault(true);
				$newDefaultDomain->commit();
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();

			return true;
		}

		/** @inheritdoc */
		public function getDomainId($host, $useMirrors = true, $checkIdn = true) {
			if (!is_string($host) || empty($host)) {
				return false;
			}

			if (empty($this->hostMap)) {
				$this->loadDomainList();
			}

			if (isset($this->hostMap[$host])) {
				/** @var iDomain $domain */
				$domain = $this->hostMap[$host];

				if ($useMirrors) {
					return $domain->getId();
				}

				return ($domain->getHost() === $host) ? $domain->getId() : false;
			}

			if ($checkIdn) {
				$host = $this->getIdnConverter()->convert($host);
				return $this->getDomainId($host, $useMirrors, false);
			}

			return false;
		}

		/** @inheritdoc */
		public function getDomainByHost($host) {
			$id = $this->getDomainId($host);
			return $this->getDomain($id);
		}

		/** @inheritdoc */
		public function getList() {
			if (empty($this->domainList)) {
				$this->loadDomainList();
			}

			return $this->domainList;
		}

		/** @inheritdoc */
		public function isExists($id) {
			if (!is_string($id) && !is_int($id)) {
				return false;
			}

			return array_key_exists($id, $this->getList());
		}

		/** @inheritdoc */
		public function getDomainIdByUrl($url) {
			if (!is_string($url) || empty($url)) {
				return false;
			}

			if (!preg_match('/^https?:\/\/([^\/]*)/', $url, $matches)) {
				return false;
			}

			$host = $matches[1];
			return $this->getDomainId($host);
		}

		/** @inheritdoc */
		public function clearCache() {
			$domains = $this->getList();

			foreach ($domains as $domain) {
				$this->unsetDomain($domain);
				unset($domain);
			}

			unset($domains);
		}

		/** @inheritdoc */
		public function isDefaultDomain($host) {
			$mainHost = $this->getDefaultDomain()->getHost();
			$converter = $this->getIdnConverter();
			$mainHost = $converter->encode($mainHost);
			$host = $converter->encode($host);
			$hostList = [$mainHost, 'www.' . $mainHost];

			if (in_array($host, $hostList)) {
				return true;
			}

			return false;
		}

		/**
		 * Удаляет домен из списка загруженны доменов
		 * @param iDomain $domain
		 * @return $this
		 */
		private function unsetDomain(iDomain $domain) {
			unset($this->domainList[$domain->getId()]);
			unset($this->hostMap[$domain->getHost()]);

			foreach ($domain->getMirrorsList() as $mirror) {
				unset($this->hostMap[$mirror->getHost()]);
			}

			return $this;
		}

		/**
		 * Добавляет домен в список загруженных доменов
		 * @param iDomain $domain
		 * @return $this
		 */
		private function setDomain(iDomain $domain) {
			$this->domainList[$domain->getId()] = $domain;
			$this->hostMap[$domain->getHost()] = $domain;

			foreach ($domain->getMirrorsList() as $mirror) {
				$this->hostMap[$mirror->getHost()] = $domain;
			}

			return $this;
		}

		/**
		 * Загружает список доменов
		 * @return bool
		 */
		private function loadDomainList() {
			$sql = 'SELECT `id`, `host`, `is_default`, `default_lang_id`, `use_ssl`, `favicon` FROM `cms3_domains`';
			$result = $this->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				try {
					$domain = new domain($row[0], $row);
				} catch (privateException $e) {
					$e->unregister();
					continue;
				}

				if ($domain instanceof iDomain) {
					$this->setDomain($domain);
				}
			}

			return true;
		}

		/**
		 * Возвращает соединение с базой данных
		 * @return IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Возвращает Idn конвертер
		 * @return iConverter
		 */
		private function getIdnConverter() {
			return $this->idnConverter;
		}

		/** @deprecated */
		public function getRequestDomain() {
			return false;
		}

		/**
		 * @deprecated
		 * @return iDomainsCollection
		 */
		public static function getInstance($c = null) {
			return Service::DomainCollection();
		}
	}
