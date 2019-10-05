<?php

	use UmiCms\Service;

	/** Класс соединения с базой данных MySQL через расширение mysqli */
	class mysqliConnection implements IConnection {

		/** @var string $host хост для подключения */
		private $host;

		/** @var string $userName логин для подключения */
		private $userName;

		/** @var string $password пароль для подключения */
		private $password;

		/** @var string $databaseName имя базы данных, с которой будет вестить работа */
		private $databaseName;

		/** @var null|int $port порт для подключения */
		private $port;

		/** @var null|string $socket сокет для подключения */
		private $socket;

		/** @var bool $isPersistent использовать ли постоянное соединение */
		private $isPersistent;

		/** @var bool $isCritical является ли соединение критичным для работы приложения */
		private $isCritical;

		/** @var mysqli $mysqliConnection соединение с базой данных */
		private $mysqliConnection;

		/** @var bool $isOpen открыто ли соединение */
		private $isOpen = false;

		/** @var int $queriesCount количество выполненных выборок за сессию */
		private $queriesCount = 0;

		/** @var null|iMysqlLogger $queriesLogger логгер запросов */
		private $queriesLogger;

		/** @const string PERSISTENT_CONNECTION_PREFIX префикс хоста для указания, что подключение будет постоянным */
		const PERSISTENT_CONNECTION_PREFIX = 'p:';

		/** @inheritdoc */
		public function __construct(
			$host,
			$userName,
			$password,
			$databaseName,
			$port = false,
			$persistent = false,
			$critical = true
		) {
			$this->host = $host;
			$this->userName = $userName;
			$this->password = $password;
			$this->databaseName = $databaseName;
			$this->port = is_numeric($port) ? $port : null;
			$this->isPersistent = $persistent;
			$this->isCritical = $critical;
		}

		/**
		 * Устанавливает сокет для подключени
		 * @param string $socket сокет
		 */
		public function setSocket($socket) {
			$this->socket = $socket;
		}

		/** @inheritdoc */
		public function setLogger(iMysqlLogger $mysqlLogger) {
			$this->queriesLogger = $mysqlLogger;
		}

		/** @inheritdoc */
		public function open() {
			if ($this->isOpen()) {
				return true;
			}

			try {
				$this->mysqliConnection = new mysqli(
					$this->isPersistent ? self::PERSISTENT_CONNECTION_PREFIX . $this->host : $this->host,
					$this->userName,
					$this->password,
					$this->databaseName,
					$this->port,
					$this->socket
				);

				if ($this->errorOccurred()) {
					throw new Exception($this->errorDescription());
				}

				$this->initConnection();
			} catch (Exception $e) {
				return $this->isCritical ? $this->makeFatalError() : false;
			}

			$this->isOpen = true;
			return true;
		}

		/** @inheritdoc */
		public function close() {
			if ($this->isOpen() && $this->isCorrectConnection()) {
				$this->mysqliConnection->close();
				$this->isOpen = false;
			}
		}

		/** @inheritdoc */
		public function query($queryString) {
			if ((!$this->isOpen() || !$this->isCorrectConnection()) && !$this->open()) {
				return false;
			}

			if (!is_string($queryString)) {
				throw new Exception('Query string expected');
			}

			$queryString = $this->prepareQueryString($queryString);

			$result = $this->mysqliQuery($queryString);
			++$this->queriesCount;

			if ($this->errorOccurred()) {
				throw new databaseException($this->errorDescription($queryString), $this->mysqliConnection->errno);
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
		public function getQueriesCount() {
			return $this->queriesCount;
		}

		/** @inheritdoc */
		public function queryResult($queryString) {
			$result = $this->query($queryString);
			return $this->isCorrectQueryResult($result) ? new mysqliQueryResult($result) : null;
		}

		/** @deprecated alias */
		public function errorOccured() {
			return $this->errorOccurred();
		}

		/** @inheritdoc */
		public function errorOccurred() {
			if ($this->isCorrectConnection()) {
				return $this->mysqliConnection->error !== '';
			}
		}

		/** @inheritdoc */
		public function errorDescription($sqlQuery = null) {
			if ($this->isCorrectConnection() && $this->errorOccurred()) {
				$errorMessage = $this->mysqliConnection->error;
				return is_string($sqlQuery) ? $errorMessage . ' in query: ' . $sqlQuery : $errorMessage;
			}
		}

		/** @inheritdoc */
		public function isOpen() {
			return $this->isOpen;
		}

		/** @inheritdoc */
		public function escape($input) {
			if ($this->isOpen() && $this->isCorrectConnection()) {
				return $this->mysqliConnection->real_escape_string($input);
			}

			return addslashes($input);
		}

		/** @inheritdoc */
		public function getConnectionInfo() {
			return [
				'host' => $this->host,
				'port' => $this->port,
				'user' => $this->userName,
				'password' => $this->password,
				'dbname' => $this->databaseName,
				'link' => $this->mysqliConnection,
				'socket' => $this->socket
			];
		}

		/** @inheritdoc */
		public function insertId() {
			if ($this->isOpen() && $this->isCorrectConnection()) {
				return $this->mysqliConnection->insert_id;
			}
			return 0;
		}

		/** @inheritdoc */
		public function errorNumber() {
			if ($this->isOpen() && $this->isCorrectConnection()) {
				return $this->mysqliConnection->connect_errno;
			}
			return 0;
		}

		/** @inheritdoc */
		public function getServerInfo() {
			if ($this->isOpen() && $this->isCorrectConnection()) {
				return $this->mysqliConnection->server_info;
			}
			return null;
		}

		/** @inheritdoc */
		public function errorMessage() {
			if ($this->isOpen() && $this->isCorrectConnection()) {
				return $this->mysqliConnection->error;
			}
			return '';
		}

		/** @inheritdoc */
		public function affectedRows() {
			if ($this->isOpen() && $this->isCorrectConnection()) {
				return $this->mysqliConnection->affected_rows;
			}
			return 0;
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
			return $this->queriesLogger;
		}

		/** Устанавливает настройки для подключения */
		private function initConnection() {
			$this->mysqliQuery('SET NAMES utf8');
			$this->mysqliQuery('SET CHARSET utf8');
			$this->mysqliQuery('SET CHARACTER SET utf8');
			$this->mysqliQuery('SET character_set_client = \'utf8\'');
			$this->mysqliQuery('SET SESSION collation_connection = \'utf8_general_ci\'');
			$this->mysqliQuery('SET SQL_BIG_SELECTS = 1');
			$this->queriesCount += 6;
		}

		/**
		 * Корректно ли текущее соединение с базой данных
		 * @return bool
		 */
		private function isCorrectConnection() {
			return $this->mysqliConnection instanceof mysqli;
		}

		/**
		 * Корректен ли результат выборки
		 * @param mixed $result
		 * @return bool
		 */
		private function isCorrectQueryResult($result) {
			return $result instanceof mysqli_result;
		}

		/**
		 * Подготавливает и возвращает строку запроса к выполнению
		 * @param string $queryString строка запроса
		 * @return string
		 */
		private function prepareQueryString($queryString) {
			return trim($queryString, " \t\n");
		}

		/**
		 * Генерирует фатальную ошибку работы с базой,
		 * приводящую к остановке работы приложения
		 */
		private function makeFatalError() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->status(500);
			$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/mysql_failed.html'));
			$buffer->end();
		}

		/**
		 * Выполняет выборку и возвращает результат,
		 * при наличии логгера - логгирует запрос
		 * @param string $queryString строка запроса
		 * @return bool|mysqli_result
		 */
		private function mysqliQuery($queryString) {
			if (!$this->isCorrectConnection()) {
				return false;
			}

			$logger = $this->getLogger();

			if ($logger === null) {
				return $this->mysqliConnection->query($queryString);
			}

			if ($logger instanceof iMysqlLogger) {
				$logger->runTimer();
				$queryResult = $this->mysqliConnection->query($queryString);
				$elapsedTime = $logger->getTimer();
				$message = $logger->getMessage($queryString, $elapsedTime);
				$logger->log($message);
				return $queryResult;
			}
		}

		/** @deprecated */
		public function clearCache() {
			return false;
		}
	}
