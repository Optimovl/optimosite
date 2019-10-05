<?php

	class umiObjectsExpiration extends singleton implements iSingleton, iUmiObjectsExpiration {

		/** Лимит выборки просроченных объектов по умолчанию */
		const DEFAULT_EXPIRED_OBJECTS_LIMIT = 50;

		/** @var int время по умолчанию для хранения объектов (в секундах) */
		protected $defaultExpires = 86400;

		/** @inheritdoc */
		protected function __construct() {
		}

		/** @inheritdoc */
		public function getLimit() {
			$limit = mainConfiguration::getInstance()->get('kernel', 'expired-objects-limit');
			return is_numeric($limit) ? (int) $limit : self::DEFAULT_EXPIRED_OBJECTS_LIMIT;
		}

		/**
		 * @inheritdoc
		 * @return iUmiObjectsExpiration
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function isExpirationExists($objectId) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id` = {$objectId}
			LIMIT 1
SQL;
			$queryResult = $connection->queryResult($sql);
			return $queryResult->length() > 0;
		}

		/** @inheritdoc */
		public function getExpiredObjectsByTypeId($typeId, $limit = 50) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$time = time();
			$sql = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id`  IN (
					SELECT
						`id`
					FROM
						`cms3_objects`
					WHERE
						`type_id`='{$typeId}'
					)
				AND (`entrytime` +  `expire`) <= {$time}
			ORDER BY (`entrytime` +  `expire`)
			LIMIT {$limit}
SQL;

			$result = [];
			$queryResult = $connection->queryResult($sql);
			if ($queryResult->length() > 0) {
				$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
				foreach ($queryResult as $row) {
					$result[] = $row['obj_id'];
				}
			}

			return $result;
		}

		/** @inheritdoc */
		public function update($objectId, $expires = false) {
			if (!$expires) {
				$expires = $this->getExpirationTime();
			}
			$connection = ConnectionPool::getInstance()->getConnection();
			$objectId = (int) $objectId;
			$expires = (int) $expires;
			$time = time();
			$sql = <<<SQL
			UPDATE
				`cms3_objects_expiration`
			SET
				`entrytime`='{$time}',
				`expire`='{$expires}'
			WHERE
				`obj_id` = '{$objectId}'
SQL;
			$connection->query($sql);
		}

		/** @inheritdoc */
		public function add($objectId, $expires = false) {
			if (!$expires) {
				$expires = $this->getExpirationTime();
			}
			$connection = ConnectionPool::getInstance()->getConnection();
			$objectId = (int) $objectId;
			$expires = (int) $expires;
			$time = time();

			$sql = <<<SQL
INSERT INTO `cms3_objects_expiration`
	(`obj_id`, `entrytime`, `expire`)
		VALUES ('{$objectId}', '{$time}', '{$expires}')
SQL;
			$connection->query($sql);
		}

		/** @inheritdoc */
		public function clear($objectId) {
			$objectId = (int) $objectId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
DELETE FROM `cms3_objects_expiration`
	WHERE `obj_id` = '{$objectId}'
SQL;
			$connection->query($sql);
		}

		/**
		 * Возвращает время хранения объектов перед удалением (в секундах)
		 * @return int
		 */
		private function getExpirationTime() {
			$umiConfig = mainConfiguration::getInstance();

			$time = $umiConfig->get('kernel', 'objects-expiration-time');
			if (!is_numeric($time)) {
				$time = $this->defaultExpires;
			}

			return (int) $time;
		}

		/** @deprecated */
		public function run() {
		}
	}
