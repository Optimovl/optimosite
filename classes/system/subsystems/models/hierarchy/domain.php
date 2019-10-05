<?php

	use UmiCms\Service;

	/** Класс домена, содержит список зеркал */
	class domain extends umiEntinty implements iDomain {

		/** @var string хост домена */
		private $host;

		/** @var int идентификатор языка по умолчанию */
		private $defaultLanguageId;

		/** @var iDomainMirror[] список зеркал домена */
		private $mirrors = [];

		/** @var bool является ли домен основным */
		private $isDefaultFlag;

		/** @var bool домен использует ssl */
		private $usingSsl;

		/** @var iUmiImageFile|null $favicon иконка домена */
		private $favicon;

		/** @var array список поддерживаемых форматов для фавикона */
		private static $FAVICON_EXT_LIST = ['ico', 'png', 'svg', 'jpeg', 'gif', 'jpg'];

		/** @var string тип сохраняемой сущности для кеширования */
		protected $store_type = 'domain';

		/** @const int количество свойств объекта, необходимое для корректного инстанцирования */
		const INSTANCE_ATTRIBUTE_COUNT = 6;

		/** @inheritdoc */
		public function getHost($encode = false) {
			if ($encode) {
				return $this->getEncodedHost();
			}

			return $this->host;
		}

		/** @inheritdoc */
		public function getEncodedHost() {
			return Service::IdnConverter()
				->encode($this->getHost());
		}

		/** @inheritdoc */
		public function getDecodedHost() {
			return Service::IdnConverter()
				->decode($this->getHost());
		}

		/** @inheritdoc */
		public function setHost($host) {
			if (!is_string($host) || empty($host)) {
				throw new wrongParamException('Wrong domain host given');
			}

			$host = self::filterHostName($host);

			if ($this->getHost() != $host) {
				$this->host = $host;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getIsDefault() {
			return $this->isDefaultFlag;
		}

		/** @inheritdoc */
		public function setIsDefault($flag) {
			$flag = (bool) $flag;

			if ($this->getIsDefault() != $flag) {
				$this->isDefaultFlag = $flag;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function isUsingSsl() {
			return $this->usingSsl;
		}

		/** @inheritdoc */
		public function setUsingSsl($flag = true) {
			$flag = (bool) $flag;

			if ($this->isUsingSsl() !== $flag) {
				$this->usingSsl = $flag;
				$this->setIsUpdated();
			}

			return $this;
		}

		/** @inheritdoc */
		public function getFavicon() {
			return $this->favicon;
		}

		/** @inheritdoc */
		public function setFavicon($icon) {

			if (!$icon instanceof iUmiImageFile && $icon !== null) {
				throw new wrongParamException('Wrong favicon given');
			}

			if ($icon instanceof iUmiImageFile) {

				if ($icon->getIsBroken()) {
					throw new wrongParamException(sprintf('Broken favicon path given: "%s"', $icon->getFilePath()));
				}

				if (!in_array(strtolower($icon->getExt()), self::$FAVICON_EXT_LIST)) {
					throw new wrongParamException(sprintf('Incorrect file format for favicon "%s"', $icon->getExt()));
				}
			}

			if ($this->getFavicon() !== $icon) {
				$this->favicon = $icon;
				$this->setIsUpdated();
			}

			return $this;
		}

		/** @inheritdoc */
		public function getDefaultLangId() {
			return $this->defaultLanguageId;
		}

		/** @inheritdoc */
		public function setDefaultLangId($id) {
			if (!Service::LanguageCollection()->isExists($id)) {
				throw new coreException("Language #{$id} doesn't exist");
			}

			if ($this->getDefaultLangId() != $id) {
				$this->defaultLanguageId = $id;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function addMirror($host) {
			$mirrorId = $this->getMirrorId($host);
			if ($mirrorId) {
				throw new coreException("Domain mirror #{$host} already exist.");
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction("Create domain mirror {$host}");

			try {
				$escapedId = (int) $this->getId();
				$sql = "INSERT INTO `cms3_domain_mirrows` (`rel`) VALUES ($escapedId)";
				$connection->query($sql);

				$mirrorId = $connection->insertId();

				$mirror = new domainMirror($mirrorId);
				$mirror->setHost($host);
				$mirror->setDomainId($escapedId);
				$mirror->commit();
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();
			$this->mirrors[$mirrorId] = $mirror;

			return $mirrorId;
		}

		/** @inheritdoc */
		public function delMirror($id) {
			if (!$this->isMirrorExists($id)) {
				throw new coreException("Domain mirror #{$id} doesn't exist.");
			}

			$escapedId = (int) $id;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "DELETE FROM cms3_domain_mirrows WHERE id = $escapedId";
			$connection->query($sql);

			unset($this->mirrors[$escapedId]);
			return true;
		}

		/** @inheritdoc */
		public function delAllMirrors() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$escapedId = (int) $this->getId();
			$sql = "DELETE FROM cms3_domain_mirrows WHERE rel = $escapedId";
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function getMirrorId($host, $checkIdn = true) {
			if (!is_string($host) || empty($host)) {
				return false;
			}

			foreach ($this->getMirrorsList() as $mirror) {
				if ($mirror->getHost() == $host) {
					return $mirror->getId();
				}
			}

			if ($checkIdn) {
				$host = Service::IdnConverter()
					->convert($host);
				return $this->getMirrorId($host, false);
			}

			return false;
		}

		/** @inheritdoc */
		public function getMirror($id) {
			return $this->isMirrorExists($id) ? $this->mirrors[$id] : false;
		}

		/** @inheritdoc */
		public function isMirrorExists($id) {
			if (!is_string($id) && !is_int($id)) {
				return false;
			}

			return array_key_exists($id, $this->mirrors);
		}

		/** @inheritdoc */
		public function getMirrorsList() {
			return $this->mirrors;
		}

		/** @inheritdoc */
		public function getUrl() {
			return $this->getProtocol() . '://' . $this->getHost();
		}

		/** @inheritdoc */
		public function getCurrentUrl() {
			return $this->getProtocol() . '://' . $this->getCurrentHostName();
		}

		/** @inheritdoc */
		public function getProtocol() {
			return $this->isUsingSsl() ? 'https' : 'http';
		}

		/** @inheritdoc */
		public static function filterHostName($host) {
			return preg_replace("/([^\p{Ll}\p{Lu}\d\._:-]+)/u", '', $host);
		}

		/** @inheritdoc */
		public function getCurrentHostName() {
			$host = Service::Request()->host();

			if (startsWith($host, 'xn--')) {
				$host = Service::IdnConverter()
					->decode($host);
			}

			if ($this->getHost() != $host) {
				$mirrorId = $this->getMirrorId($host, false);

				if ($mirrorId !== false) {
					return $this->getMirror($mirrorId)
						->getHost();
				}
			}

			return $this->getHost();
		}

		/** @inheritdoc */
		protected function save() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$host = $connection->escape($this->getHost());
			$isDefaultFlag = (int) $this->getIsDefault();
			$defaultLanguageId = (int) $this->getDefaultLangId();
			$usingSsl = (int) $this->isUsingSsl();
			$escapedId = (int) $this->getId();
			$favicon = $this->getFavicon();

			if ($favicon instanceof iUmiImageFile) {
				$filepath = $connection->escape('.' . $favicon->getFilePath(true));
				$filepath = sprintf("'%s'", $filepath);
			} else {
				$filepath = 'NULL';
			}

			$sql = <<<SQL
UPDATE `cms3_domains`
	SET `host` = '$host', `is_default` = $isDefaultFlag, `default_lang_id` = $defaultLanguageId, `use_ssl` = $usingSsl, `favicon` = $filepath
		WHERE `id` = $escapedId
SQL;
			$connection->query($sql);
			return true;
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < self::INSTANCE_ATTRIBUTE_COUNT) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT `id`, `host`, `is_default`, `default_lang_id`, `use_ssl`, `favicon` FROM `cms3_domains` WHERE `id` = $escapedId LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < self::INSTANCE_ATTRIBUTE_COUNT) {
				return false;
			}

			list($id, $host, $isDefaultFlag, $defaultLanguageId, $usingSsl, $favicon) = $row;

			$this->host = (string) $host;
			$this->isDefaultFlag = (bool) $isDefaultFlag;
			$this->defaultLanguageId = (int) $defaultLanguageId;
			$this->usingSsl = (bool) $usingSsl;
			$this->favicon = ($favicon) ? Service::ImageFactory()->create($favicon) : null;
			return $this->loadMirrors();
		}

		/**
		 * Загружает список зеркал домена
		 * @return bool
		 */
		private function loadMirrors() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$escapedId = (int) $this->getId();
			$sql = "SELECT `id`, `host`, `rel` FROM `cms3_domain_mirrows` WHERE `rel` = $escapedId";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				list($mirrorId) = $row;

				try {
					$this->mirrors[$mirrorId] = new domainMirror($mirrorId, $row);
				} catch (privateException $e) {
					$e->unregister();
					continue;
				}
			}

			return true;
		}

		/** @deprecated */
		public function addMirrow($host) {
			return $this->addMirror($host);
		}

		/** @deprecated */
		public function delMirrow($id) {
			return $this->delMirror($id);
		}

		/** @deprecated */
		public function delAllMirrows() {
			return $this->delAllMirrors();
		}

		/** @deprecated */
		public function getMirrowId($host) {
			return $this->getMirrorId($host);
		}

		/** @deprecated */
		public function getMirrow($id) {
			return $this->getMirror($id);
		}

		/** @deprecated */
		public function isMirrowExists($id) {
			return $this->isMirrorExists($id);
		}

		/** @deprecated */
		public function getMirrowsList() {
			return $this->getMirrorsList();
		}
	}
