<?php

	/** Интерфейс логгера запросов к MySQL */
	interface iMysqlLogger {

		/**
		 * Конструктор с внедрение класса настроек
		 * @param iConfiguration $configuration объекта класса настроек
		 */
		public function __construct(iConfiguration $configuration);

		/** Запускает счетчик */
		public function runTimer();

		/** Возвращает время, прошедшее с момента запуска счетчика */
		public function getTimer();

		/**
		 * Логгирует данные
		 * @param string $message сообщение
		 */
		public function log($message);

		/**
		 * Формирует сообщение лога на основе строки запроса
		 * @param string $query строка запроса
		 * @param float $time время работы
		 * @return string
		 */
		public function getMessage($query, $time);
	}
