<?php

	use UmiCms\Service;

	/** Реализация хранилища кеша в Redis */
	class redisCacheEngine implements iCacheEngine {

		/** @var bool|Redis $client клиент */
		private $client;

		/** @var string $host хост для подключения */
		private $host;

		/** @var string $port порт для подключения */
		private $port;

		/** @var string $baseNumber номер используемой базы */
		private $baseNumber;

		/** @var string $authKey ключ доступа к серверу, если необходим */
		private $authKey;

		/** @var string|null $socket путь до юникс сокета */
		private $socket;

		/** @const string DEFAULT_HOST хост подключения по умолчанию */
		const DEFAULT_HOST = '127.0.0.1';

		/** @const int DEFAULT_PORT порт подключения по умолчанию */
		const DEFAULT_PORT = 6379;

		/** @const string NAME название хранилища */
		const NAME = 'redis';

		/** Конструктор */
		public function __construct() {
			$umiConfig = mainConfiguration::getInstance();
			$this->host = $umiConfig->get('cache', 'redis.host') ?: self::DEFAULT_HOST;
			$this->port = $umiConfig->get('cache', 'redis.port') ?: self::DEFAULT_PORT;
			$this->socket = $umiConfig->get('cache', 'redis.socket') ?: null;
			$this->baseNumber = $umiConfig->get('cache', 'redis.base');
			$this->authKey = $umiConfig->get('cache', 'redis.auth');
			$this->createClient();
			$this->connect();
		}

		/** @inheritdoc */
		public function getName() {
			return self::NAME;
		}

		/** @inheritdoc */
		public function saveRawData($key, $data, $expire) {
			$client = $this->getConnectedClient();
			return (bool) $client ? $client->set($key, serialize($data), $expire) : false;
		}

		/** @inheritdoc */
		public function loadRawData($key) {
			$client = $this->getConnectedClient();
			$data = $client ? $client->get($key) : null;
			return $data ? unserialize($data) : null;
		}

		/** @inheritdoc */
		public function delete($key) {
			$client = $this->getConnectedClient();
			return $client ? (bool) $client->del($key) : false;
		}

		/** @inheritdoc */
		public function flush() {
			$client = $this->getConnectedClient();

			if (!$client) {
				return false;
			}

			$client->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

			$iterator = null;
			$basePrefix = Service::CacheKeyGenerator()
				->getBasePrefix();

			while ($keys = $client->scan($iterator, $basePrefix . '*')) {
				if (is_array($keys) && !empty($keys)) {
					$client->del($keys);
				}
			}

			$client->setOption(Redis::OPT_SCAN, Redis::SCAN_NORETRY);

			return true;
		}

		/** @inheritdoc */
		public function getIsConnected() {
			return $this->getConnectedClient() instanceof Redis;
		}

		/**
		 * Создает клиент
		 * @return $this
		 */
		private function createClient() {
			$this->client = new Redis();
			return $this;
		}

		/**
		 * Инициирует подключение клиента
		 * @return bool
		 */
		private function connect() {
			$client = $this->getClient();

			if ($this->socket) {
				if (!$client->connect($this->socket)) {
					return false;
				}
			} else {
				if (!$client->connect($this->host, $this->port)) {
					return false;
				}
			}

			if (!empty($this->authKey) && !$client->auth($this->authKey)) {
				return false;
			}

			if (!empty($this->baseNumber) && !$client->select($this->baseNumber)) {
				return false;
			}

			return true;
		}

		/**
		 * Возвращает экземпляр клиента, который точно подключен
		 * @return bool|Redis
		 */
		private function getConnectedClient() {
			$client = $this->getClient();

			if ($client instanceof Redis) {
				try {
					$pong = $client->ping();

					if (!$pong) {
						$this->connect();
					}
				} catch (RedisException $exception) {
					$this->connect();
				}
			}

			return $client;
		}

		/**
		 * Возвращает экземпляр клиента
		 * @return bool|Redis
		 */
		private function getClient() {
			return $this->client;
		}
	}
