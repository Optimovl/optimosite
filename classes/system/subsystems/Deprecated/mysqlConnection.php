<?php

	/**
	 * Класс соединения с базой данных MySQL через расширение mysql
	 * @deprecated
	 * @use mysqliConnection
	 *
	 * Пример использования:
	 *        $connection = new Connection('localhost', 'root', '', 'umi');
	 *        $connection->open();
	 *        $connection->query('SHOW TABLES');
	 *        $connection->close();
	 */
	class mysqlConnection implements IConnection {

		private $host = null;

		private $username = null;

		private $password = null;

		private $dbname = null;

		private $port = false;

		private $persistent = false;

		private $critical = true;

		private $conn = null;

		private $isOpen = false;

		private $queriesCount = 0;

		private $logger = null;

		/** @inheritdoc */
		public function __construct(
			$host,
			$login,
			$password,
			$dbname,
			$port = false,
			$persistent = false,
			$critical = true
		) {
			$this->host = $host;
			$this->username = $login;
			$this->password = $password;
			$this->dbname = $dbname;
			$this->port = $port;
			$this->persistent = $persistent;
			$this->critical = $critical;
		}

		/** @inheritdoc */
		public function open() {
			if ($this->isOpen) {
				return true;
			}

			try {
				$server = $this->host . ($this->port ? ':' . $this->port : '');
				if ($this->persistent) {
					$this->conn = mysql_pconnect($server, $this->username, $this->password);
				} else {
					$this->conn = mysql_connect($server, $this->username, $this->password, true);
				}
				if ($this->errorOccured()) {
					throw new Exception();
				}
				if (!mysql_select_db($this->dbname, $this->conn)) {
					throw new Exception();
				}

				$this->mysqlQuery("SET NAMES utf8", $this->conn);
				$this->mysqlQuery("SET CHARSET utf8", $this->conn);
				$this->mysqlQuery("SET CHARACTER SET utf8", $this->conn);
				$this->mysqlQuery("SET character_set_client = 'utf8'", $this->conn);
				$this->mysqlQuery("SET SESSION collation_connection = 'utf8_general_ci'", $this->conn);
				$this->mysqlQuery("SET SQL_BIG_SELECTS=1", $this->conn);
				$this->queriesCount += 6;
			} catch (Exception $e) {
				if ($this->critical) {
					try {
						$buffer = outputBuffer::current();
					} catch (coreException $e) {
						$buffer = outputBuffer::current('HTTPOutputBuffer');
					}

					$buffer->status(500);
					$buffer->push(file_get_contents(CURRENT_WORKING_DIR . "/errors/mysql_failed.html"));
					$buffer->end();
				} else {
					return false;
				}
			}
			$this->isOpen = true;
			return true;
		}

		/** @inheritdoc */
		public function getQueriesCount() {
			return (int) $this->queriesCount;
		}

		/** @inheritdoc */
		public function close() {
			if ($this->isOpen) {
				mysql_close($this->conn);
				$this->isOpen = false;
			}
		}

		/** @inheritdoc */
		public function query($queryString) {
			if (!$this->isOpen) {
				if (!$this->open()) {
					return false;
				}
			}

			$queryString = trim($queryString, " \t\n");

			if (defined('SQL_QUERY_DEBUG') && SQL_QUERY_DEBUG) {
				echo $queryString . "\r\n";
			}

			$result = $this->mysqlQuery($queryString, $this->conn);
			++$this->queriesCount;

			if ($this->errorOccurred()) {
				throw new databaseException($this->errorDescription($queryString), mysql_errno());
			}

			return $result;
		}

		/** @inheritdoc */
		public function startTransaction($comment = '') {
			$comment = (!is_string($comment)) ? '' : $this->escape($comment);
			$command = sprintf('START TRANSACTION /* %s */', $comment);
			$this->query($command);
			return $this;
		}

		/** @inheritdoc */
		public function commitTransaction() {
			$this->query('COMMIT');
			return $this;
		}

		/** @inheritdoc */
		public function rollbackTransaction() {
			$this->query('ROLLBACK');
			return $this;
		}

		/** @inheritdoc */
		public function queryResult($queryString) {
			$result = $this->query($queryString);
			return $result ? new mysqlQueryResult($result) : null;
		}

		/** @deprecated alias */
		public function errorOccured() {
			return $this->errorOccurred();
		}

		/** @inheritdoc */
		public function errorOccurred() {
			return mysql_error($this->conn) !== '';
		}

		/** @inheritdoc */
		public function errorDescription($sqlQuery = null) {
			$descr = mysql_error($this->conn);
			if ($sqlQuery) {
				$descr .= " in query: " . $sqlQuery;
			}
			return $descr;
		}

		/** @inheritdoc */
		public function isOpen() {
			return $this->isOpen;
		}

		/** @inheritdoc */
		public function escape($input) {
			if ($this->isOpen) {
				return mysql_real_escape_string($input);
			} else {
				return addslashes($input);
			}
		}

		/** @inheritdoc */
		public function getConnectionInfo() {
			return [
				'host' => $this->host,
				'port' => $this->port,
				'user' => $this->username,
				'password' => $this->password,
				'dbname' => $this->dbname,
				'link' => $this->conn
			];
		}

		/** @inheritdoc */
		public function setLogger(iMysqlLogger $mysqlLogger) {
			$this->logger = $mysqlLogger;
		}

		/** @inheritdoc */
		public function insertId() {
			return mysql_insert_id($this->conn);
		}

		/** @inheritdoc */
		public function errorNumber() {
			return mysql_errno($this->conn);
		}

		/** @inheritdoc */
		public function getServerInfo() {
			return mysql_get_server_info($this->conn);
		}

		/** @inheritdoc */
		public function errorMessage() {
			return mysql_error($this->conn);
		}

		/** @inheritdoc */
		public function affectedRows() {
			return mysql_affected_rows($this->conn);
		}

		/** @inheritdoc */
		public function isDuplicateKey(databaseException $exception) {
			return $exception->getCode() === self::DUPLICATE_KEY_ERROR_CODE;
		}

		/**
		 * Возвращает логгер запросов
		 * @return null|iMysqlLogger
		 */
		private function getLogger() {
			return $this->logger;
		}

		/**
		 * Запускает выполнение запроса,
		 * при необходимости логгирует запрос
		 * @param string $query текст запроса
		 * @param resource $resource подключение к бд
		 * @return resource
		 */
		private function mysqlQuery($query, $resource) {
			$logger = $this->getLogger();

			if ($logger === null) {
				return mysql_query($query, $resource);
			}

			if ($logger instanceof iMysqlLogger) {
				$logger->runTimer();
				$queryResult = mysql_query($query, $resource);
				$elapsedTime = $logger->getTimer();
				$message = $logger->getMessage($query, $elapsedTime);
				$logger->log($message);
				return $queryResult;
			}
		}

		/** @deprecated */
		public function clearCache() {
			return null;
		}
	}
