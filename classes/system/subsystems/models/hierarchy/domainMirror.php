<?php

	use UmiCms\Service;

	/** Класс зеркала домена */
	class domainMirror extends umiEntinty implements iDomainMirror {

		/** @var string хост зеркала */
		private $host;

		/** @var int идентификатор домена, к которому принадлежит зеркало */
		private $domainId;

		/** @var string тип сохраняемой сущности для кеширования */
		protected $store_type = 'domain_mirror';

		/** @inheritdoc */
		public function getHost($encode = false) {
			if ($encode) {
				return Service::IdnConverter()
					->encode($this->host);
			}

			return $this->host;
		}

		/** @inheritdoc */
		public function setHost($host) {
			if (!is_string($host) || empty($host)) {
				throw new wrongParamException('Wrong domain mirror host given');
			}

			$host = domain::filterHostName($host);

			if ($this->getHost() != $host) {
				$this->host = $host;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getDomainId() {
			return $this->domainId;
		}

		/** @inheritdoc */
		public function setDomainId($id) {
			if (!Service::DomainCollection()->isExists($id)) {
				throw new coreException("Domain #{$id} doesn't exist");
			}

			if ($this->getDomainId() != $id) {
				$this->domainId = $id;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < 3) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = "SELECT `id`, `host`, `rel` FROM `cms3_domain_mirrows` WHERE `id` = $escapedId LIMIT 0,1";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 3) {
				return false;
			}

			list($id, $host, $rel) = $row;

			$this->host = (string) $host;
			$this->domainId = (int) $rel;
			return true;
		}

		/** @inheritdoc */
		protected function save() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$host = $connection->escape($this->getHost());
			$escapedId = (int) $this->getId();
			$domainId = (int) $this->getDomainId();

			$sql = <<<SQL
UPDATE `cms3_domain_mirrows`
	SET `host` = '$host', `rel` = $domainId
		WHERE `id` = $escapedId
SQL;
			$connection->query($sql);

			return true;
		}
	}
