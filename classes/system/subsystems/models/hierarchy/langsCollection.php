<?php

	use UmiCms\Service;

	/** Класс коллекции языков. @todo: вынести из класса репозиторий и фабрику */
	class langsCollection implements iLangsCollection {

		/** @var IConnection $connection соединение с базой данных */
		private $connection;

		/** @var iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/** @var iLang[] $languageList список языков системы */
		private $languageList = [];

		/** @inheritdoc */
		public function __construct(IConnection $connection, iDomainsCollection $domainCollection) {
			$this->connection = $connection;
			$this->domainCollection = $domainCollection;
		}

		/** @inheritdoc */
		public function addLang($prefix, $title, $isDefault = false) {
			$id = $this->getLangId($prefix);

			if ($id) {
				throw new coreException("Lang #{$prefix} already exist.");
			}

			$connection = $this->getConnection();
			$connection->startTransaction("Create lang {$prefix}");

			try {
				$sql = "INSERT INTO `cms3_langs` VALUES(null, '%s', '%s', %d)";
				$sql = sprintf($sql, $prefix, $title, (int) $isDefault);
				$connection->query($sql);

				$id = $connection->insertId();

				$language = new lang($id);
				$language->setPrefix($prefix);
				$language->setTitle($title);
				$language->setIsDefault($isDefault);
				$language->commit();
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();
			$this->setLanguage($language);

			if ($isDefault) {
				$this->setDefault($id);
			}

			return $id;
		}

		/** @inheritdoc */
		public function delLang($id) {
			if (!$this->isExists($id)) {
				throw new coreException("Language #{$id} doesn't exist.");
			}

			$language = $this->getLang($id);
			$connection = $this->getConnection();
			$escapedId = (int) $id;
			$sql = "DELETE FROM `cms3_langs` WHERE `id` = $escapedId";
			$connection->query($sql);

			$this->unsetLanguage($language);
			unset($language);

			return true;
		}

		/** @inheritdoc */
		public function getLang($id) {
			return $this->isExists($id) ? $this->getList()[$id] : false;
		}

		/** @inheritdoc */
		public function getDefaultLang() {
			foreach ($this->getList() as $language) {
				if ($language->getIsDefault()) {
					return $language;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function setDefault($id) {
			if (!$this->isExists($id)) {
				throw new coreException("Language #{$id} doesn't exist.");
			}

			$connection = $this->getConnection();
			$connection->startTransaction("Set default lang #{$id}");

			try {
				$oldDefaultLanguage = $this->getDefaultLang();

				if ($oldDefaultLanguage instanceof iLang) {
					$oldDefaultLanguage->setIsDefault(false);
					$oldDefaultLanguage->commit();
				}

				$newDefaultLanguage = $this->getLang($id);
				$newDefaultLanguage->setIsDefault(true);
				$newDefaultLanguage->commit();
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();

			return true;
		}

		/** @inheritdoc */
		public function getLangId($prefix) {
			foreach ($this->getList() as $language) {
				if ($language->getPrefix() == $prefix) {
					return $language->getId();
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function getList() {
			if (empty($this->languageList)) {
				$this->loadLanguageList();
			}

			return $this->languageList;
		}

		/** @inheritdoc */
		public function isExists($id) {
			if (!is_string($id) && !is_int($id)) {
				return false;
			}

			return array_key_exists($id, $this->getList());
		}

		/** @inheritdoc */
		public function getLanguageIdByUrl($url) {
			$domainCollection = $this->getDomainCollection();
			$domainId = $domainCollection->getDomainIdByUrl($url);
			$domain = $domainCollection->getDomain($domainId);

			if (!$domain instanceof iDomain) {
				return false;
			}

			if (!preg_match('/^https?:\/\/[^\/]*[\/]([^\/]*)/', $url, $matches)) {
				return $domain->getDefaultLangId();
			}

			$prefix = $matches[1];
			$languageIdByPrefix = $this->getLangId($prefix);

			if (is_numeric($languageIdByPrefix)) {
				return $languageIdByPrefix;
			}

			return $domain->getDefaultLangId();
		}

		/** @inheritdoc */
		public function clearCache() {
			$languages = $this->getList();

			foreach ($languages as $language) {
				$this->unsetLanguage($language);
				unset($languages);
			}

			unset($languages);
		}

		/**
		 * Удаляет язык из списка загружены языков
		 * @param iLang $language язык
		 * @return $this
		 */
		private function unsetLanguage(iLang $language) {
			unset($this->languageList[$language->getId()]);
			return $this;
		}

		/**
		 * Добавляет язык в список загруженных языков
		 * @param iLang $language язык
		 * @return $this
		 */
		private function setLanguage(iLang $language) {
			$this->languageList[$language->getId()] = $language;
			return $this;
		}

		/**
		 * Загружает список доменов
		 * @return bool
		 */
		private function loadLanguageList() {
			$sql = 'SELECT `id`, `prefix`, `is_default`, `title` FROM `cms3_langs`';
			$result = $this->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				try {
					$language = new lang($row[0], $row);
				} catch (privateException $e) {
					$e->unregister();
					continue;
				}

				if ($language instanceof iLang) {
					$this->setLanguage($language);
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
		 * Возвращает коллекцию доменов
		 * @return iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}

		/** @deprecated */
		public function getAssocArray() {
			$res = [];

			foreach ($this->getList() as $lang) {
				$res[$lang->getId()] = $lang->getTitle();
			}

			return $res;
		}

		/**
		 * @deprecated
		 * @return iLangsCollection
		 */
		public static function getInstance($c = null) {
			return Service::LanguageCollection();
		}
	}
