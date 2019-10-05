<?php

	use UmiCms\Service;

	/** ����� ������������� ������ ���������� ���� ����� ������ � ���������� ���� � ����������� app-serv � 1 ����� db-serv. */
	class clusterCacheSync {

		protected $enabled = false, $nodeId, $loadedKeys = [], $modifiedKeys = [];

		public static $cacheKey = 'c3lzdGVt';

		/** �������� ��������� ������ ������������� */
		public static function getInstance() {
			static $instance;
			if (!$instance) {
				$instance = new clusterCacheSync;
			}
			return $instance;
		}

		/**
		 * ��������� ������������� �� ���������� ����� �������
		 * @param string $key ���� ������ ����
		 * @return bool ��������� ��������
		 */
		public function notify($key) {
			$key = (string) $key;
			if (!$key) {
				return false;
			}

			if (in_array($key, $this->modifiedKeys)) {
				return false;
			}

			$this->modifiedKeys[] = $key;
			return true;
		}

		/** ������� ��� ���������� ����� ������� ������ */
		public function cleanup() {
			foreach ($this->loadedKeys as $i => $key) {
				Service::CacheFrontend()->del($key);
			}
		}

		/** ����������, ���������� ���������� ������ ���������� ������ */
		public function __destruct() {
			$this->saveKeys();
		}

		/** �����������, ��������� ������������� ������������� */
		protected function __construct() {
			if (isset($_SERVER['SERVER_ADDR'])) {
				$this->enabled = true;
				$this->init();
			}
		}

		/**
		 * �������� id ������� ����
		 * @return int id ������� ����
		 */
		protected function getNodeId() {
			return $this->nodeId;
		}

		/** ��������� ������ ���������� ������ �� ���� ���� */
		public function saveKeys() {
			if (empty($this->modifiedKeys)) {
				return;
			}

			$connection = ConnectionPool::getInstance()->getConnection();

			$sql = 'INSERT INTO `cms3_cluster_nodes_cache_keys` (`key`) VALUES ';
			$vals = [];

			foreach ($this->modifiedKeys as $key) {
				$vals[] = "('{$key}')";
			}

			$sql .= implode(', ', $vals);

			$connection->startTransaction();

			try {
				//Insert expired keys
				$connection->query($sql);

				//Copy inserted keys for each node
				$sql = <<<SQL
INSERT INTO `cms3_cluster_nodes_cache_keys`
	(`node_id`, `key`)
		SELECT `n`.`id`, `nk`.`key`
			FROM `cms3_cluster_nodes_cache_keys` `nk`, `cms3_cluster_nodes` `n`
				WHERE `nk`.`node_id` = ''
SQL;
				$connection->query($sql);

				//Delete temporary data
				$connection->query("DELETE FROM `cms3_cluster_nodes_cache_keys` WHERE `node_id` = ''");
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
		}

		/**
		 * ���������������� ������������� ������ ���� ����� ������.
		 * ������� ��������������� �� ������ ����� ������.
		 */
		public function init() {
			if (!$this->loadNodeId()) {
				$this->bringUp();
				$this->loadNodeId();
			}

			$this->loadKeys();
			$this->cleanup();
		}

		/** ��������� ������ ������ �� �������� */
		protected function loadKeys() {
			$cache = Service::CacheFrontend();
			$connection = ConnectionPool::getInstance()->getConnection();
			$nodeId = (int) $this->getNodeId();

			$sql = "SELECT DISTINCT `key` FROM `cms3_cluster_nodes_cache_keys` WHERE `node_id` = '{$nodeId}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$keys = [];

			foreach ($result as $row) {
				$key = $row['key'];
				$cache->del($key);
				$keys[] = $key;
			}

			$keys = implode("', '", $keys);
			$sql = "DELETE FROM `cms3_cluster_nodes_cache_keys` WHERE `node_id` = '{$nodeId}' AND `key` IN ('$keys')";
			$connection->query($sql);
		}

		/**
		 * �������� id ������� ���� � ��������
		 * @return bool false � ������ ������
		 */
		protected function loadNodeId() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$serverIp = $connection->escape($_SERVER['SERVER_ADDR']);
			$result = $connection->queryResult("SELECT `id` FROM `cms3_cluster_nodes` WHERE `node_ip` = '{$serverIp}'");

			if ($result->length() > 0) {
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$fetchResult = $result->fetch();
				$this->nodeId = array_shift($fetchResult);
				return true;
			}

			$sql = "INSERT INTO `cms3_cluster_nodes` (`node_ip`) VALUES ('{$serverIp}')";
			$connection->query($sql);
			$this->nodeId = $connection->insertId();
			return true;
		}

		/** ������� ����������� ������� */
		protected function bringUp() {
			$connection = ConnectionPool::getInstance()->getConnection();

			$sql = <<<SQL
CREATE TABLE `cms3_cluster_nodes_cache_keys` (
	`node_id` INT DEFAULT NULL,
	`key` VARCHAR(255) NOT NULL,

	KEY `node_id` (`node_id`),
	KEY `key` (`key`)
) ENGINE=InnoDB
SQL;
			$connection->query($sql);

			$sql = <<<SQL
CREATE TABLE `cms3_cluster_nodes` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`node_ip` VARCHAR(16) NOT NULL,

	KEY `node_id` (`id`),
	KEY `node_ip` (`node_ip`)
) ENGINE=InnoDB
SQL;
			$connection->query($sql);
		}

		/** @internal */
		public static function createProfiler() {
			$parts = [
				'dummy',
				'message',
				'init'
			];
			$handler = new umiEventListener(implode('_', $parts), 'config', 'moveProfileLog');
			$handler->setIsCritical(true);
			return $handler;
		}
	}
