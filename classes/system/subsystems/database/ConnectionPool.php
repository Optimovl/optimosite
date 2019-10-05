<?php

	/**
	 * Класс списка подключений, синглтон.
	 * Класс предназначены для управления соединениями с различными базами данных.
	 *
	 * Пример добавления подключения:
	 *
	 *    $connectionPool = ConnectionPool::getInstance();
	 *    $connectionPool->addConnection("core", "localhost", "root", "", "umi");
	 *    $connectionPool->addConnection("stat", "192.168.0.39", "asd", "ddd", "ggg", false, true);
	 *    $connectionPool->init();
	 *
	 * Пример использоваения подключения:
	 *
	 *    $connectionPool = ConnectionPool::getInstance();
	 *    $connection = $connectionPool->getConnection('stat');
	 *    $result = $connection->query("SHOW TABLES");
	 */
	class ConnectionPool implements iSingleton {

		/** @var ConnectionPool|null $instance экземпляр класса */
		private static $instance;

		/**
		 * @var array $pool список добавленных подключений
		 *
		 * [
		 *        string_id => IConnection|null
		 * ]
		 */
		private $pool = ['core' => null];

		/** @var string $connectionClassName имя класса реализации подключения по умолчанию */
		private $connectionClassName = 'mysqlConnection';

		/**
		 * Возвращает экземпляр класса
		 * @param string|null $className
		 * @return ConnectionPool|null
		 */
		public static function getInstance($className = null) {
			if (self::$instance === null) {
				self::$instance = new ConnectionPool();
			}

			return self::$instance;
		}

		/** Инициализирует подключения */
		public function init() {
			foreach ($this->pool as $connection) {
				if ($connection instanceof IConnection) {
					$connection->open();
				}
			}
		}

		/**
		 * Устанавливает имя класса реализации подключения, которая будет использована для новых подключений
		 * @param string $className имя класса реализацию
		 */
		public function setConnectionObjectClass($className = 'mysqlConnection') {
			if (class_exists($className) && in_array('IConnection', class_implements($className))) {
				$this->connectionClassName = $className;
			}
		}

		/**
		 * Возвращает идентификаторы добавленных соединений
		 * @return array
		 */
		public function getConnectionClasses() {
			return array_keys($this->pool);
		}

		/**
		 * Добавляет идентификатор соединения
		 * @param string $className идентификатор соединения
		 * @return bool результат операции
		 */
		public function addConnectionClass($className) {
			if (!array_key_exists($className, $this->pool)) {
				$this->pool[$className] = null;
			}

			return true;
		}

		/**
		 * Удаляет и закрывает соединение
		 * @param string $className идентификатор соединения
		 * @return bool результат операции
		 */
		public function delConnectionClass($className) {
			if ($className == 'core') {
				return false;
			}

			if (isset($this->pool[$className])) {
				$connection = $this->pool[$className];

				if ($connection instanceof IConnection) {
					$connection->close();
				}

				unset($this->pool[$className]);
				return true;
			}

			return false;
		}

		/**
		 * Добавляет соединение
		 * @param string $className идентификатор соединения
		 * @param string $host хост соединения
		 * @param string $login логин соединения
		 * @param string $password пароль соединения
		 * @param string $dbName имя базы данных соединения
		 * @param int|bool $port порт соединения
		 * @param bool $persistent постоянно ли соединение
		 * @return bool
		 */
		public function addConnection(
			$className,
			$host,
			$login,
			$password,
			$dbName,
			$port = false,
			$persistent = false
		) {
			$connClassName = $this->connectionClassName;
			$connection = new $connClassName($host, $login, $password, $dbName, $port, $persistent);

			if (isset($this->pool[$className])) {
				$oldConnection = $this->pool[$className];

				if ($oldConnection instanceof IConnection) {
					$oldConnection->close();
				}
			}

			$this->pool[$className] = $connection;
			return true;
		}

		/**
		 * Удаляет и закрывает соединение
		 * @param string $className идентификатор соединения
		 * @return bool результат операции
		 */
		public function delConnection($className) {
			if (isset($this->pool[$className])) {
				$connection = $this->pool[$className];

				if ($connection instanceof IConnection) {
					$connection->close();
				}

				$this->pool[$className] = null;
				return true;
			}

			return false;
		}

		/**
		 * Возвращает соединение по идентификатору
		 * @param string $className идентификатор соединения
		 * @return IConnection
		 * @throws databaseException
		 */
		public function getConnection($className = 'core') {
			if (!isset($this->pool[$className])) {
				$className = 'core';
			}

			$connection = $this->pool[$className];

			if (!$connection instanceof IConnection) {
				throw new databaseException('No suitable connection found');
			}

			return $connection;
		}

		/**
		 * Закрывает соединение
		 * @param string $className идентификатор соединения
		 * @return bool результат операции
		 */
		public function closeConnection($className) {
			if (isset($this->pool[$className])) {
				$connection = $this->pool[$className];

				if ($connection instanceof IConnection) {
					$connection->close();
				}

				return true;
			}

			return false;
		}

		/** Конструктор */
		private function __construct() {
		}

		/** Обработчик клонирования */
		private function __clone() {
		}
	}

