<?php

	/** Интерфейс хранилища для кеша */
	interface iCacheEngine {

		/**
		 * Возвращает название хранилища
		 * @return string
		 */
		public function getName();

		/**
		 * Помещает данные в кеш и возвращает результат операции
		 * @param int|string $key ключ, по которому данные можно будет извлечь
		 * @param mixed $data данные скалярного типа или массив
		 * @param int $expire время, за которое данные можно будет извлечь из кеша, в секундах
		 * @return bool
		 */
		public function saveRawData($key, $data, $expire);

		/**
		 * Получает данные из кеша
		 * @param int|string $key ключ, по которому извлекаются данные
		 * @return object|null|bool
		 */
		public function loadRawData($key);

		/**
		 * Удаляет любые данные из кеша
		 * @param int|string $key ключ, по которому данные можно извлечь
		 * @return bool|void
		 */
		public function delete($key);

		/**
		 * Очищает хранилище
		 * @return bool|void
		 */
		public function flush();

		/**
		 * Определяет доступно ли хранилище
		 * @return bool
		 */
		public function getIsConnected();
	}
