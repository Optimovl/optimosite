<?php

	/** Создатель логгеров запросов к MySQL */
	class MysqlLoggerCreator implements iMysqlLoggerCreator {

		/** @var MysqlLoggerCreator|null $instance экземпляр класса */
		private static $instance;

		/** @const string MYSQL_LOGGER_CLASS_POSTFIX постфикс имени класса логгера запросов к MySQL */
		const MYSQL_LOGGER_CLASS_POSTFIX = 'MysqlLogger';

		/** @inheritdoc */
		public static function getInstance() {
			if (self::$instance === null) {
				self::$instance = new MysqlLoggerCreator();
			}
			return self::$instance;
		}

		/** @inheritdoc */
		public function createMysqlLogger($loggerType, iConfiguration $configuration) {
			if (!is_string($loggerType)) {
				throw new coreException('Wrong type of logger type');
			}

			$loggerClassName = $this->getMysqlLoggerClassName($loggerType);

			if (!class_exists($loggerClassName)) {
				$loggerFilePath = $this->getMysqlLoggerFilePath($loggerType);

				if (!file_exists($loggerFilePath)) {
					throw new coreException('Cant find realisation of Mysql logger with type: ' . $loggerFilePath);
				}

				$this->loadMysqlLogger($loggerFilePath);
			}

			if (!class_exists($loggerClassName)) {
				throw new coreException('Cant load class of Mysql logger with type: ' . $loggerType);
			}

			$loggerClass = new $loggerClassName($configuration);

			if (!$loggerClass instanceof iMysqlLogger) {
				throw new coreException('Mysql logger with type: ' . $loggerType . ' must implement iMysqlLogger');
			}

			return $loggerClass;
		}

		/**
		 * Запрещает клонирование
		 * @throws coreException
		 */
		public function __clone() {
			throw new coreException('Not permitted');
		}

		/**
		 * Загружает класс логгера
		 * @param string $loggerFilePath путь до файла с реализация логгера запросов к MySQL
		 */
		private function loadMysqlLogger($loggerFilePath) {
			include_once $loggerFilePath;
		}

		/**
		 * Возвращает имя класса логгера запросов к MySQL
		 * @param string $loggerType тип логгера запросов к MySQL
		 * @return string
		 */
		private function getMysqlLoggerClassName($loggerType) {
			return $loggerType . self::MYSQL_LOGGER_CLASS_POSTFIX;
		}

		/**
		 * Возвращает путь до файла с реализацией логгера запросов к MySQL
		 * @param string $loggerType тип логгера запросов к MySQL
		 * @return string
		 */
		private function getMysqlLoggerFilePath($loggerType) {
			return dirname(__FILE__) . '/' . $loggerType . self::MYSQL_LOGGER_CLASS_POSTFIX . '.php';
		}

		/** Запрещает получени экземпляров класса напрямую */
		private function __construct() {
		}
	}

