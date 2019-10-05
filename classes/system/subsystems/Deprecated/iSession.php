<?php

	/** @deprecated */
	interface iSession extends iMapContainer {

		/**
		 * Возращает экземпляр сессии.
		 * @param bool $start нужно ли запускать сессию, если она не запущена
		 * @return session
		 */
		public static function getInstance($start = true);

		/** Уничтожает экземпляр. */
		public static function destroy();

		/** Сохраняет экземпляр сессии и закрывает его */
		public static function commit();

		/**
		 * Возвращает идентификатор сессии
		 * @return string
		 */
		public static function getId();

		/**
		 * Возвращает идентификатор сессии и закрывает ее
		 * @return string
		 */
		public function getIdAndClose();

		/**
		 * Устанавливает идентификатор экземпляра сессии и возвращает его
		 * @param string $id Идентификатор
		 * @return string
		 */
		public static function setId($id);

		/**
		 * Возвращает имя экземпляра сессии
		 * @return string Имя
		 */
		public static function getName();

		/**
		 * Устанавливает имя экземпляра сессии и возвращает его
		 * @param string $name Имя
		 * @return string
		 */
		public static function setName($name);

		/**
		 * Генерирует новый идентификатор сессии и заменяет им текущий идентификатор сессии
		 * @return bool результат операции
		 */
		public function regenerateId();

		/**
		 * Возвращает значение по ключу и закрывает сессию
		 * @param string $key ключ
		 * @return mixed
		 */
		public function getAndClose($key);

		/**
		 * Проверяет задано ли значение для ключа и закрывает сессию
		 * @param string $key ключ
		 * @return bool результат проверки
		 */
		public function issetAndClose($key);

		/**
		 * Устанавливает значение ключа и закрывает сессию
		 * @param string $key ключ
		 * @param mixed $value Значение
		 */
		public function setAndClose($key, $value);

		/**
		 * Удаляет значение по ключу
		 * @param string|array $key ключ или массив ключей
		 */
		public function delAndClose($key);
	}
