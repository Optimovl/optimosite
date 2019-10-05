<?php

	/** Реализация заглушки хранилища кеша */
	class nullCacheEngine implements iCacheEngine {

		/** @const string NAME название хранилища */
		const NAME = 'null';

		/** @inheritdoc */
		public function getName() {
			return self::NAME;
		}

		/** @inheritdoc */
		public function saveRawData($key, $data, $expire) {
			return true;
		}

		/** @inheritdoc */
		public function loadRawData($key) {
			return null;
		}

		/** @inheritdoc */
		public function delete($key) {
			return true;
		}

		/** @inheritdoc */
		public function flush() {
			return true;
		}

		/** @inheritdoc */
		public function getIsConnected() {
			return true;
		}
	}
