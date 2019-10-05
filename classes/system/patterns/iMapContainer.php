<?php

	/**
	 * Интерфейс контейнера типа ключ-значение.
	 * У класса, реализующего этот интерфейс, должны существовать магические методы.
	 */
	interface iMapContainer {

		/**
		 * Возвращает значение по ключу
		 * @param string $key ключ
		 * @return mixed
		 */
		public function get($key);

		/**
		 * Задано ли значение для ключа
		 * @param string $key ключ
		 * @return bool
		 */
		public function isExist($key);

		/**
		 * Записывает значение ключа и возвращает его
		 * @param string $key ключ
		 * @param mixed $value Значение
		 * @return mixed
		 */
		public function set($key, $value);

		/**
		 * Удаляет значение по ключу
		 * @param string $key ключ
		 * @return bool
		 */
		public function del($key);

		/**
		 * Возвращает содержимое контейнера
		 * @return array
		 */
		public function getArrayCopy();

		/** Очищает содержимое контейнера */
		public function clear();

		/**
		 * Алиас @see iMapContainer::get()
		 * @param string $key ключ
		 * @return mixed
		 */
		public function __get($key);

		/**
		 * Алиас @see iMapContainer::isExist()
		 * @param string $key ключ
		 * @return bool
		 */
		public function __isset($key);

		/**
		 * Алиас @see iMapContainer::set()
		 * @param string $key ключ
		 * @param mixed $value Значение
		 * @return mixed
		 */
		public function __set($key, $value);

		/**
		 * Алиас @see iMapContainer::del()
		 * @param string $key ключ
		 * @return bool
		 */
		public function __unset($key);
	}
