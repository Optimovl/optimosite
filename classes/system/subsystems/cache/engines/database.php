<?php

	/** Реализация хранилища кеша в базе данных */
	class databaseCacheEngine implements iCacheEngine {

		/** @var IConnection|null $connection ресурс соединения с бд */
		private $connection;

		/** @var iConfiguration|null $configuration конфигурация */
		private $configuration;

		/** @const string NAME название хранилища */
		const NAME = 'database';

		/** Конструктор */
		public function __construct() {
			$this->connection = ConnectionPool::getInstance()
				->getConnection();
			$this->configuration = mainConfiguration::getInstance();
		}

		/** @inheritdoc */
		public function getName() {
			return self::NAME;
		}

		/** @inheritdoc */
		public function getIsConnected() {
			return $this->getConnection() instanceof IConnection;
		}

		/** @inheritdoc */
		public function saveRawData($key, $data, $expire) {
			$connection = $this->getConnection();
			$escapedKey = $connection->escape($key);
			$data = $connection->escape(serialize($data));
			$createTime = time();
			$expire = time() + $this->normaliseExpire($expire);

			$sql = <<<sql
INSERT INTO `cms3_data_cache` (`key`, `value`, `create_time`, `expire_time`, `entry_time`, `entries_number`)
	VALUES('$escapedKey', '$data', $createTime, $expire, 0, 0)
		ON DUPLICATE KEY UPDATE `value` = '$data', `create_time` = $createTime, `expire_time` = $expire;
sql;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function loadRawData($key) {
			$connection = $this->getConnection();
			$escapedKey = $connection->escape($key);

			$sql = <<<SQL
SELECT `value`, `expire_time`
	FROM `cms3_data_cache`
		WHERE `key` = '$escapedKey';
SQL;
			$cacheRowList = $connection->queryResult($sql);

			if ($cacheRowList->length() == 0) {
				return null;
			}

			$cacheRow = $cacheRowList->fetch();

			if (time() > $cacheRow['expire_time']) {
				$this->delete($key);
				return null;
			}

			$debugEnable = (bool) $this->getConfiguration()
				->get('cache', 'engine.debug');

			if ($debugEnable) {
				$this->updateEntry($key);
			}

			return unserialize($cacheRow['value']);
		}

		/** @inheritdoc */
		public function delete($key) {
			$connection = $this->getConnection();
			$escapedKey = $connection->escape($key);

			$sql = <<<SQL
DELETE FROM `cms3_data_cache`
	WHERE `key` = '$escapedKey';
SQL;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function flush() {
			$sql = <<<SQL
TRUNCATE TABLE `cms3_data_cache`;
SQL;
			$this->getConnection()
				->query($sql);

			return true;
		}

		/**
		 * Возвращает экземпляр ресурса соединения с бд
		 * @return IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Возвращает экземплял конфигурации
		 * @return iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Нормализует время, за которое данные можно будет извлечь из кеша
		 * @param int $expire время
		 * @return int
		 */
		private function normaliseExpire($expire) {
			$expire = (int) $expire;
			$timestamp = time();

			if ($expire >= $timestamp) {
				return $expire - $timestamp;
			}

			return $expire;
		}

		/**
		 * Обновляет количество обращений к кешу
		 * @param string $key ключ кеша
		 * @return bool
		 */
		private function updateEntry($key) {
			$connection = $this->getConnection();
			$escapedKey = $connection->escape($key);
			$entryTime = time();

			$sql = <<<SQL
UPDATE `cms3_data_cache`
	SET `entry_time` = $entryTime, `entries_number` = `entries_number` + 1
		WHERE `key` = '$escapedKey';
SQL;
			$connection->query($sql);

			return true;
		}

		/**
		 * Удаляет кеш с истекшим временем жизни
		 * @return bool
		 */
		public function dropExpired() {
			$time = time();
			$sql = <<<SQL
DELETE FROM `cms3_data_cache`
	WHERE `expire_time` < $time;
SQL;
			$this->getConnection()
				->query($sql);

			return true;
		}

		/**
		 * Оптимизирует таблицу с кешем
		 * @return bool
		 */
		public function optimise() {
			$sql = <<<SQL
OPTIMIZE TABLE `cms3_data_cache`;
SQL;
			$this->getConnection()
				->query($sql);

			return true;
		}
	}
