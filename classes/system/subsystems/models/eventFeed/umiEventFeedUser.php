<?php

	class umiEventFeedUser {

		private static $connection;

		private static $users = [];

		/** @var int $id идентификатор пользователя */
		private $id;

		/** @var int $lastCheckIn timestamp последней активности пользователя */
		private $lastCheckIn;

		/**
		 * @var array $settings настройки вывода событий
		 */
		private $settings;

		/** @var bool $isModified был ли изменен объект */
		private $isModified = false;

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
		 * Создает и возвращает нового пользователя
		 * @param int $id идентификатор пользователя
		 * @return umiEventFeedUser экземпляр нового пользователя
		 */
		public static function create($id) {
			$id = (int) $id;
			$time = time();
			$settings = serialize([]);
			self::getConnection()->queryResult(
				"INSERT INTO umi_event_users (id, last_check_in, settings) VALUES('{$id}', '{$time}', '{$settings}')"
			);
			$user = new self($id);
			self::$users[$id] = $user;
			return $user;
		}

		/**
		 * Получить экземпляр пользователя
		 * @param int $id
		 * @throws Exception если пользователь не найден
		 * @return umiEventFeedUser
		 */
		public static function get($id) {
			$id = (int) $id;
			if (isset(self::$users[$id])) {
				return self::$users[$id];
			}
			$user = new self($id);
			self::$users[$id] = $user;
			return $user;
		}

		/**
		 * Создает экземпляр пользователя
		 * @param int $id идентификатор пользователя
		 * @throws Exception если пользователь не найден
		 */
		public function __construct($id) {
			$id = (int) $id;
			$this->id = $id;
			$this->load();
		}

		/**
		 * Получить идентификатор пользователя
		 * @return int id
		 */
		public function getId() {
			return $this->id;
		}

		/**
		 * Получить время последнего захода пользователя
		 * @return int
		 */
		public function getLastCheckIn() {
			return $this->lastCheckIn;
		}

		/**
		 * Получить список типов событий, которые использует пользователь
		 * @return array
		 */
		public function getSettings() {
			return $this->settings;
		}

		/**
		 * Узнать изменен ли объект
		 * @return bool
		 */
		public function getModified() {
			return $this->isModified;
		}

		/**
		 * Пометить объект как измененный
		 * @param bool $isModified
		 */
		public function setModified($isModified = true) {
			$this->isModified = $isModified;
		}

		/** Установить время последнего захода пользователя на текущее время */
		public function setLastCheckIn() {
			$this->setModified();
			$this->lastCheckIn = time();
		}

		/**
		 * Установить список типов событий, которые использует пользователь
		 * @param array $settings
		 */
		public function setSettings(array $settings) {
			$this->setModified();
			$this->settings = $settings;
		}

		/** Загрузить данные из базы */
		protected function load() {
			$id = (int) $this->id;
			$userInfo = self::getConnection()
				->queryResult("SELECT last_check_in, settings FROM umi_event_users WHERE id = {$id}");

			if (!$userInfo || !$userInfo->length()) {
				throw new privateException("Failed to load info for umiEventFeedUser with id {$id}");
			}

			foreach ($userInfo as $info) {
				$this->lastCheckIn = $info['last_check_in'];
				$this->settings = unserialize($info['settings']);
			}
		}

		/** Сохранить изменения в БД */
		public function save() {
			if (!$this->isModified) {
				return false;
			}

			$id = (int) $this->id;
			$lastCheckIn = $this->lastCheckIn ?: time();
			$settings = serialize($this->settings);
			self::getConnection()->query(
				"UPDATE umi_event_users SET last_check_in = '{$lastCheckIn}', settings = '{$settings}' WHERE id = {$id}"
			);
		}
	}

