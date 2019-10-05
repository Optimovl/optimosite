<?php

	/** Интерфейс буфера вывода */
	interface iOutputBuffer {

		/** Конструктор */
		public function __construct();

		/**
		 * Отправляет буферизованную на текущий момент информацию, не завершая запрос.
		 */
		public function send();

		/**
		 * Очищает содержимое
		 * @return $this
		 */
		public function clear();

		/**
		 * Очищает все буферы и отключает буферизацию
		 * @return $this
		 */
		public function reset();

		/**
		 * Возвращает длину буферизованной на текущий момент информации (число текстовых символов).
		 * @return int
		 */
		public function length();

		/**
		 * Возвращает буферизованную на текущий момент информацию
		 * @return string
		 */
		public function content();

		/**
		 * Добавляет данные к выводу
		 * @param string $data данные
		 * @return $this
		 */
		public function push($data);

		/**
		 * Отправляет информацию из буфера и завершает запрос
		 */
		public function end();

		/** Завершает работу */
		public function stop();

		/**
		 * Возвращает время жизни буфера с момента его создания
		 * @return float
		 */
		public function calltime();

		/**
		 * Делает перенаправление на указанный адрес
		 * @param string $url адрес
		 * @param string $status текстовый статус
		 * @param int $numStatus числовой статус
		 */
		public function redirect($url, $status = '301 Moved Permanently', $numStatus = 301);

		/**
		 * Возвращает/устанавливает статус ответа
		 * @param string|bool $status статус, если false - возвращает статус
		 * @return string
		 */
		public function status($status = false);

		/**
		 * Возвращает код статуса ответа
		 * @return int
		 */
		public function getStatusCode();

		/**
		 * Возвращает/устанавливает кодировку ответа
		 * @param string|bool $charset кодировка, если false - возвращает кодировку
		 * @return string
		 */
		public function charset($charset = false);

		/**
		 * Возвращает/устанавливает тип контента ответа
		 * @param string|bool $contentType тип контента, если false - возвращает тип контента
		 * @return string
		 */
		public function contentType($contentType = false);

		/**
		 * Возвращает значение опции или устанавливает значение, если передан второй параметр
		 * @param string $key имя опции
		 * @param null $value значение опции
		 * @return mixed|null
		 */
		public function option($key, $value = null);

		/**
		 * Определяет был ли установлен заголовок
		 * @param string $name имя заголовка
		 * @return bool
		 */
		public function issetHeader($name);

		/**
		 * Устанавливает заголовок
		 * @param string $name имя заголовка
		 * @param string $value значение заголовка
		 * @return $this
		 */
		public function setHeader($name, $value);

		/**
		 * Удаляет заголовок из очереди на отправку
		 * @param string $name имя заголовка
		 * @return $this
		 * @throws wrongParamException
		 */
		public function unsetHeader($name);

		/**
		 * Возвращает очередь заголовков для отправки
		 * @return array
		 *
		 * [
		 *        header_name => header_value
		 * ]
		 */
		public function getHeaderList();

		/**
		 * Определяет включена ли отправка событий
		 * @return bool
		 */
		public function isEventsEnabled();

		/**
		 * Включает отправку событий
		 * @return $this
		 */
		public function enableEvents();

		/**
		 * Выключает отправку событий
		 * @return $this
		 */
		public function disableEvents();

		/**
		 * Возвращает / устанавливает название генератора контента
		 * Используется для вывода в generate time блоке
		 * @param string|null $generatorType
		 * @return string|null
		 */
		public static function contentGenerator($generatorType = null);

		/** Деструктор */
		public function __destruct();
	}
