<?php

	/** Интерфейс создателя логгеров запросов к MySQL */
	interface iMysqlLoggerCreator {

		/**
		 * Возвращает экземпляр класса конкретного создателя логгеров запросов к MySQL
		 * @return iMysqlLoggerCreator
		 */
		public static function getInstance();

		/**
		 * Создает и возвращает логгер запросов к MySQL
		 * @param string $loggerType тип логгера запросов к MySQL
		 * @param iConfiguration $configuration класс настроек
		 * @return iMysqlLogger
		 */
		public function createMysqlLogger($loggerType, iConfiguration $configuration);

	}
