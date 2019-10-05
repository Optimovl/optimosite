<?php

	/** Интерфейс логгера */
	interface iUmiLogger {

		/**
		 * Конструктор
		 * @param string $directoryPath путь до директории с журналами
		 */
		public function __construct($directoryPath = './logs/');

		/**
		 * Устанавливает имя файла журнала
		 * @param string $fileName имя файла
		 * @return $this
		 */
		public function setFileName($fileName);

		/**
		 * Добавляет сообщение в журнал
		 * @param string $message сообщение
		 * @param bool $appendTimer нужно ли добавить в начало сообщение таймер
		 * @return $this
		 */
		public function push($message, $appendTimer = true);

		/**
		 * Сохраняет содержимое журнала в файл
		 * @return $this
		 */
		public function save();

		/**
		 * Очищает содержимое Журнала
		 * @return $this
		 */
		public function resetLog();

		/**
		 * Возвращает лог в виде строки
		 * @return string
		 */
		public function get();

		/**
		 * Возвращает лог
		 * @return array
		 */
		public function getRaw();

		/**
		 * Устанавливает нужно ли разделять лог по ip адресам
		 * @param bool $flag
		 * @return $this
		 */
		public function separateByIp($flag = true);

		/**
		 * Добавляет в журнал дамп суперглобальных массивов
		 * @return $this
		 */
		public function pushGlobalEnvironment();

		/**
		 * @alias iUmiLogger::push()
		 * @param string $message
		 * @param bool $appendTimer
		 * @return $this
		 */
		public function log($message, $appendTimer = true);
	}
