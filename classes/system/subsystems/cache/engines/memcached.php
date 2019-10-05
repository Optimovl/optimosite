<?php

	/** Реализация хранилища кеша в Memcached */
	class memcachedCacheEngine implements iCacheEngine {

		/** @var bool|Memcached $client клиент */
		private $client;

		/** @var string $host хост для подключения */
		private $host;

		/** @var string $port порт для подключения */
		private $port;

		/** @const string DEFAULT_HOST хост подключения по умолчанию */
		const DEFAULT_HOST = 'localhost';

		/** @const int DEFAULT_PORT порт подключения по умолчанию */
		const DEFAULT_PORT = 11211;

		/** @const string NAME название хранилища */
		const NAME = 'memcached';

		/** Конструктор */
		public function __construct() {
			$umiConfig = mainConfiguration::getInstance();
			$this->host = $umiConfig->get('cache', 'memcached.host') ?: self::DEFAULT_HOST;
			$this->port = $umiConfig->get('cache', 'memcached.port') ?: self::DEFAULT_PORT;
			$this->connect();
		}

		/** @inheritdoc */
		public function getName() {
			return self::NAME;
		}

		/** @inheritdoc */
		public function saveRawData($key, $data, $expire) {
			$client = $this->getClient();
			return $client ? $client->set($key, $data, $expire) : false;
		}

		/** @inheritdoc */
		public function loadRawData($key) {
			$client = $this->getClient();
			return $client ? $client->get($key) : null;
		}

		/** @inheritdoc */
		public function delete($key) {
			$client = $this->getClient();
			return $client ? $client->delete($key) : false;
		}

		/** @inheritdoc */
		public function flush() {
			$client = $this->getClient();
			return $client ? $client->flush() : false;
		}

		/** @inheritdoc */
		public function getIsConnected() {
			return $this->getClient() instanceof Memcached;
		}

		/**
		 * Инициирует подключение клиента
		 * @return bool
		 */
		private function connect() {
			$client = new Memcached();

			if (!$client->addServer($this->host, $this->port)) {
				return false;
			}

			$this->client = $client;
			return true;
		}

		/**
		 * Возвращает экземпляр клиента
		 * @return Memcache|bool
		 */
		private function getClient() {
			return $this->client;
		}
	}
