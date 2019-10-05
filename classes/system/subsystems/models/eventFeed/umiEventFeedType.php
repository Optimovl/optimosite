<?php

	class umiEventFeedType {

		private static $connection;

		private static $types;

		/** @var int $id идентификатор типа события */
		private $id;

		/**
		 * Установить соединение к базе данных
		 * @param iConnection $connection соединение к базе данных
		 */
		public static function setConnection(IConnection $connection) {
			self::$connection = $connection;
		}

		/**
		 * Получить соединение к базе данных
		 * @throws Exception если соединение не установлено
		 * @return iConnection $connection соединение к базе данных
		 */
		public static function getConnection() {
			if (self::$connection === null) {
				throw new Exception('No database connection is set');
			}
			return self::$connection;
		}

		/**
		 * Создает и возвращает новый тип события
		 * @param string $id уникальный идентификатор сотипа события
		 * @throws Exception если тип с таким id уже существует
		 * @return umiEventFeedType экземпляр нового типа
		 */
		public static function create($id) {
			$id = self::getConnection()->escape($id);
			if ($id === '') {
				throw new Exception('umiFeedEventType cannot be empty');
			}
			try {
				self::getConnection()->query("INSERT INTO umi_event_types SET id = '{$id}'");
			} catch (Exception $e) {
				throw new Exception("umiFeedEventType with id {$id} already exists");
			}

			self::load();
			return self::get($id);
		}

		/**
		 * Получить новый экземпляр типа события по его id
		 * @param string $id
		 * @throws Exception если тип не найден
		 * @return umiEventFeedType
		 */
		public static function get($id) {
			if (self::$types === null) {
				self::load();
			}
			if (!isset(self::$types[$id])) {
				throw new Exception("umiFeedEventType with id '{$id}' doesn't exist");
			}
			return self::$types[$id];
		}

		/**
		 * Получить список всех типов событий
		 * @return array массив umiEventFeedType
		 */
		public static function getList() {
			if (self::$types === null) {
				self::load();
			}
			return self::$types;
		}

		/**
		 * Алиас @see umiEventFeedType::getAllowedEvents()
		 * @param array $excludes
		 * @return array
		 */
		public function getAllowedList($excludes) {
			return self::getAllowedEvents($excludes);
		}

		/**
		 * Получить список типов событий c учетом исключений
		 * @param array $excludes список исключений
		 * @return array массив id типов
		 */
		public static function getAllowedEvents($excludes) {
			if (empty($excludes)) {
				return array_keys(self::getList());
			}

			$result = [];
			$types = self::getList();
			foreach ($types as $typeId => $type) {
				if (!in_array($typeId, $excludes)) {
					$result[] = $typeId;
				}
			}

			return $result;
		}

		/**
		 * Создает экземпляр типа события
		 * @param array $data
		 */
		public function __construct(array $data) {
			$this->id = $data['id'];
		}

		/**
		 * Загрузить данные из базы
		 * @throws Exception если типов нет
		 */
		private static function load() {
			self::$types = [];
			$info = self::getConnection()->queryResult('SELECT * FROM umi_event_types');
			if (!$info) {
				throw new privateException('Failed to load event types');
			}
			foreach ($info as $row) {
				self::$types[$row['id']] = new self($row);
			}
		}

		/**
		 * Получить уникальный идентификатор типа события
		 * @return string идентификатор типа события
		 */
		public function getId() {
			return $this->id;
		}
	}

