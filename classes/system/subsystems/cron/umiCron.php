<?php

	/** Класс, позволяющий запускать действия по расписанию */
	class umiCron implements iUmiCron {

		protected $statFile, $buffer = [], $logs;

		private $modules = [];

		/** Конструктор */
		public function __construct() {
			$config = mainConfiguration::getInstance();
			$this->statFile = $config->includeParam('system.runtime-cache') . 'cron';
		}

		/** Деструктор */
		public function __destruct() {
			$this->setLastCall();
		}

		/**
		 * Запуск обработки событий
		 * @return Int (зарезервировано)
		 */
		public function run() {
			$lastCallTime = $this->getLastCall();
			$currCallTime = time();

			$result = $this->callEvent($lastCallTime, $currCallTime);
			$this->setLastCall();
			return $result;
		}

		/**
		 * Возвращает буффер
		 * @return Mixed буфер
		 */
		public function getBuffer() {
			return $this->buffer;
		}

		/**
		 * Установить модуль, для которого выполнить крон, если пустое значение - то пройти по всем модулям
		 * @param array $modules
		 */
		public function setModules($modules = []) {
			$this->modules = (array) $modules;
		}

		/**
		 * Получить лог выполнения umiEventListener'ов
		 * @return array массив из объектов класса umiEventListener.
		 * В ключе executed отработавшие, в failed - завершенные с ошибкой.
		 */
		public function getLogs() {
			return $this->logs;
		}

		public function getParsedLogs() {
			$result = '';
			$logs = $this->getLogs();

			if (umiCount($logs['executed'])) {
				$result .= "Executed event handlers:\n";
				$result .= $this->getParsedLogsByArray($logs['executed']);
				$result .= "\n";
			}

			if (umiCount($logs['failed'])) {
				$result .= "Failed event handlers:\n";
				$result .= $this->getParsedLogsByArray($logs['failed']);
				$result .= "\n";
			}

			if (umiCount($logs['breaked'])) {
				$result .= "Breaked event handlers:\n";
				$result .= $this->getParsedLogsByArray($logs['breaked']);
				$result .= "\n";
			}

			return $result ?: 'No event handlers found';
		}

		protected function getParsedLogsByArray($arr) {
			$result = '';
			for ($i = 0; $i < umiCount($arr); $i++) {
				$eventPoint = $arr[$i];
				$module = $eventPoint->getCallbackModule();
				$method = $eventPoint->getCallbackMethod();
				$priority = $eventPoint->getPriority();
				$critical = $eventPoint->getIsCritical() ? 'critical' : 'not critial';

				$n = $i + 1;
				$result .= <<<END
	{$n}. {$module}::{$method} (umiEventPoint), priority = {$priority}, {$critical}

END;
			}
			return $result;
		}

		/**
		 * Возвращает время последнего запуска
		 * @return Int Time Stamp последнего запуска
		 */
		protected function getLastCall() {
			if (is_file($this->statFile)) {
				return filemtime($this->statFile);
			}

			$this->setLastCall();
			return time();
		}

		/**
		 * Меняет время последнего запуска на текущее
		 * @return bool true - в случае успеха, false - в случае ошибки
		 */
		protected function setLastCall() {
			if (!$res = @touch($this->statFile)) {
				$res = @touch($this->statFile);
			}
			return $res;
		}

		protected function callEvent($lastCallTime, $currCallTime) {
			static $counter = 0;

			$event = new umiEventPoint('cron');
			$event->setMode('process');
			$event->setModules($this->modules);
			$event->setParam('lastCallTime', $lastCallTime);
			$event->setParam('currCallTime', $currCallTime);
			$event->addRef('buffer', $this->buffer);
			$event->addRef('counter', $counter);

			$this->logs = $event->call();

			return $counter;
		}
	}

