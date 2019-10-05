<?php

	/** Пример реализации логгера запросов к MySQL, пишет лог в файл */
	class TestMysqlLogger implements iMysqlLogger {

		/** @var int|null $startTime время начала работы таймера */
		private $startTime;

		/** @var string|null $logPath путь до файла с логом */
		private $logPath;

		/** @inheritdoc */
		public function __construct(iConfiguration $configuration) {
			$this->runTimer();
			$this->setLogPath($configuration->includeParam('mysql-queries-log'));
		}

		/** @inheritdoc */
		public function runTimer() {
			$this->setStartTime(microtime(true));
		}

		/** @inheritdoc */
		public function getTimer() {
			$time = microtime(true) - $this->getStartTime();
			return round($time, 7);
		}

		/** @inheritdoc */
		public function log($message) {
			if (!is_string($message)) {
				throw new coreException('Log message expected');
			}

			$message = $this->formatMessage($message);
			$this->write($message);
		}

		/** @inheritdoc */
		public function getMessage($query, $time) {
			return $query . ' done during: ' . $time . ' seconds';
		}

		/**
		 * Записывает сообщение в лог
		 * @param string $message сообщение
		 */
		private function write($message) {
			if (!file_put_contents($this->getLogPath(), $message, FILE_APPEND)) {
				throw new coreException('Cannot log');
			}
		}

		/**
		 * Форматирует и возвращает сообщение лога
		 * @param string $message исходное сообщение
		 * @return string
		 */
		private function formatMessage($message) {
			return date('Y.m.d H:i:s') . ' ' . $message . PHP_EOL;
		}

		/**
		 * Устанавливает время начала работы таймера
		 * @param int $time время начала работы таймера
		 */
		private function setStartTime($time) {
			$this->startTime = (float) $time;
		}

		/**
		 * Возвращает время начала работы таймера
		 * @return int|null
		 */
		private function getStartTime() {
			return $this->startTime;
		}

		/**
		 * Устанавливает путь до файла с логом
		 * @param string $logPath путь до файла с логом
		 */
		private function setLogPath($logPath) {
			$this->logPath = (string) $logPath;
		}

		/**
		 * Возвращает путь до файла с логом
		 * @return string
		 */
		private function getLogPath() {
			return $this->logPath;
		}
	}
