<?php

	/**
	 * Интерфейс соединения с базой данных
	 * Пример использования:
	 *    $connection = new Connection('localhost', 'root', '', 'umi');
	 *    $connection->open();
	 *    $connection->query('SHOW TABLES');
	 *    $connection->close();
	 */
	interface IConnection {

		/** @const String DUPLICATE_KEY_ERROR_CODE код ошибки о дублировании уникальных ключей */
		const DUPLICATE_KEY_ERROR_CODE = 1062;

		/**
		 * Конструктор соединения
		 * @param string $host хост СУБД
		 * @param string $login имя пользователя БД
		 * @param string $password пароль к БД
		 * @param string $dbname имя БД
		 * @param bool|int $port порт
		 * @param bool $persistent true - для сохранения подключения открытым
		 * @param bool $critical true - если функционирование подключения критично для системы
		 */
		public function __construct(
			$host,
			$login,
			$password,
			$dbname,
			$port = false,
			$persistent = false,
			$critical = true
		);

		/**
		 * Устанавливает логгер запросов
		 * @param iMysqlLogger $mysqlLogger
		 */
		public function setLogger(iMysqlLogger $mysqlLogger);

		/**
		 * Открывает соединение
		 * @return bool
		 */
		public function open();

		/** Закрывает текущее соединение */
		public function close();

		/**
		 * Выполняет запрос к БД
		 * @param string $queryString строка запроса
		 * @return Resource|mysqli_result|bool результат выполнения запроса
		 * @throws databaseException
		 */
		public function query($queryString);

		/**
		 * Запускает транзакцию
		 * @param string $comment комментарий транзакции
		 * @return IConnection
		 */
		public function startTransaction($comment = '');

		/**
		 * Фиксирует завершение транзакции
		 * @return IConnection
		 */
		public function commitTransaction();

		/**
		 * Откатывает транзакцию
		 * @return IConnection
		 */
		public function rollbackTransaction();

		/**
		 * Возвращает количество запросов к бд
		 * @return int
		 */
		public function getQueriesCount();

		/**
		 * Выполняет запрос к БД
		 * @param string $queryString строка запроса
		 * @return IQueryResult результат выполнения запроса
		 * @throws databaseException
		 */
		public function queryResult($queryString);

		/**
		 * Проверяет, успешно ли завершен последний запрос
		 * @return bool true в случае возникновения ошибки, иначе false
		 */
		public function errorOccurred();

		/**
		 * Возвращает описание последней возникшей ошибки
		 * @param string|null $sqlQuery запрос, который привел к ошибке
		 * @return string
		 */
		public function errorDescription($sqlQuery = null);

		/**
		 * Возвращает признак открыто соединение или нет
		 * @return bool
		 */
		public function isOpen();

		/**
		 * Экранирует входящую строку
		 * @param string $input строка для экранирования
		 * @return string
		 */
		public function escape($input);

		/**
		 * Возвращает массив с описанием соединения:
		 * @return array
		 *
		 */
		public function getConnectionInfo();

		/**
		 * Возвращает автоматически генерируемый ID, используя последний запрос
		 * @return int
		 */
		public function insertId();

		/**
		 * Возвращает численный код ошибки выполнения последней операции с MySQL
		 * @return int
		 */
		public function errorNumber();

		/**
		 * Возвращает строку, содержащую версию сервера MySQL
		 * @return string|null
		 */
		public function getServerInfo();

		/**
		 * Возвращает текст ошибки последней операции с MySQL
		 * @return string
		 */
		public function errorMessage();

		/**
		 * Возвращает число затронутых прошлой операцией строк
		 * @return int
		 */
		public function affectedRows();

		/**
		 * Определяет относится ли ошибка к ошибке дублирования уникального ключа
		 * @param databaseException $exception исключение
		 * @return bool
		 */
		public function isDuplicateKey(databaseException $exception);
	}
