<?php

	/** Реализация хранилища кеша в массиве */
	class arrayCacheEngine implements iCacheEngine {

		/** @var array $storage хранилище */
		private $storage = [];

		/** @const string NAME название хранилища */
		const NAME = 'array';

		/** @inheritdoc */
		public function getName() {
			return self::NAME;
		}

		/** @inheritdoc */
		public function saveRawData($key, $data, $expire) {
			if (!$this->isValidKey($key)) {
				return false;
			}

			$this->storage[$key] = $data;
			return true;
		}

		/** @inheritdoc */
		public function loadRawData($key) {
			if (!$this->isValidKey($key)) {
				return null;
			}

			if (isset($this->storage[$key])) {
				return $this->storage[$key];
			}

			return null;
		}

		/** @inheritdoc */
		public function delete($key) {
			if (!$this->isValidKey($key)) {
				return false;
			}

			if (isset($this->storage[$key])) {
				unset($this->storage[$key]);
			}

			return true;
		}

		/** @inheritdoc */
		public function flush() {
			$this->storage = [];
			return true;
		}

		/** @inheritdoc */
		public function getIsConnected() {
			return true;
		}

		/**
		 * Валидирует ключ, по которому доступны кешировуемые данные
		 * @param mixed $key
		 * @return bool
		 */
		private function isValidKey($key) {
			return (is_string($key) || is_int($key));
		}
	}
