<?php

	/** Класс логгера */
	class umiLogger implements iUmiLogger {

		/** @var umiDirectory $directory директория с журналами */
		protected $directory;

		/** @var array $log лог */
		protected $log = [];

		/** @var bool $isSaved был ли журнал сохранен */
		protected $isSaved = false;

		/** @var bool $startTime время начала работы логгера */
		protected $startTime = false;

		/** @var bool $separateByIp нужно ли разделять журнал по директориям по ip */
		protected $separateByIp = true;

		/** @var string $fileName имя файла, куда писать журнал */
		protected $fileName;

		/** @var bool $isGlobalVariablesPushed был ли добавлен в журнал дамп суперглобальных массивов */
		protected $isGlobalVariablesPushed = false;

		/** @inheritdoc */
		public function __construct($directoryPath = './logs/') {
			$this->runTimer();
			$directory = new umiDirectory($directoryPath);
			$this->setDirectory($directory);
			$this->setFileName(date('Y-m-d_H_i_s'));
		}

		/** @inheritdoc */
		public function setFileName($fileName) {
			if (!is_string($fileName) || empty($fileName)) {
				throw new Exception('File name expected');
			}

			$this->fileName = $fileName;
			return $this;
		}

		/** @inheritdoc */
		public function push($message, $appendTimer = true) {
			if (!is_string($message) && !is_callable([$message, '__toString'])) {
				throw new Exception('Incorrect log message given');
			}

			$this->log[] = $appendTimer ? $this->appendTimer($message) : (string) $message;
			return $this;
		}

		/** @inheritdoc */
		public function save() {
			$directory = $this->isSeparatedByIp() ? $this->createDirectoryByIp() : $this->getDirectory();
			$filePath = $directory->getPath() . '/' . $this->getFileName() . '.log';
			$this->setSaved();

			if (!file_put_contents($filePath, $this->get(), FILE_APPEND)) {
				throw new Exception("Can't save log in \"{$filePath}\"");
			}

			return $this->resetLog();
		}

		/** @inheritdoc */
		public function resetLog() {
			$this->log = [];
			return $this;
		}

		/** @inheritdoc */
		public function get() {
			return implode(PHP_EOL, $this->log);
		}

		/** @inheritdoc */
		public function getRaw() {
			return $this->log;
		}

		/** @inheritdoc */
		public function separateByIp($flag = true) {
			$this->separateByIp = (bool) $flag;
			return $this;
		}

		/** @inheritdoc */
		public function pushGlobalEnvironment() {
			if ($this->isGlobalVariablesPushed === false) {
				$this->collectGlobalEnvironment();
				$this->isGlobalVariablesPushed = true;
			}

			return $this;
		}

		/** @inheritdoc */
		public function log($message, $appendTimer = true) {
			return $this->push($message, $appendTimer);
		}

		/** Деструктор */
		public function __destruct() {
			if (!$this->isSaved() && $this->getDirectory() instanceof iUmiDirectory && !empty($this->get())) {
				$this->save();
			}
		}

		/**
		 * Устанавливает директорию с журналами
		 * @param iUmiDirectory $directory директория с журналами
		 * @return $this
		 * @throws Exception
		 */
		protected function setDirectory(iUmiDirectory $directory) {
			if (!$directory->isExists()) {
				throw new Exception("Directory \"{$directory->getPath()}\" doesn't exist");
			}

			if (!$directory->isWritable()) {
				throw new Exception("Directory \"{$directory->getPath()}\" must be writable");
			}

			$this->directory = $directory;
			return $this;
		}

		/**
		 * Возвращает директорию с журналами
		 * @return iUmiDirectory
		 */
		protected function getDirectory() {
			return $this->directory;
		}

		/**
		 * Создает директорию с именем по текущему ip адресу в директории с журналами
		 * @return iUmiDirectory созданная директория
		 * @throws Exception
		 */
		protected function createDirectoryByIp() {
			$directoryPath = $this->getDirectory()
				->getPath();
			$newDirectoryPath = $directoryPath . '/' . getServer('REMOTE_ADDR');
			$newDirectory = new umiDirectory($newDirectoryPath);

			if ($newDirectory->isExists() && !$newDirectory->isWritable()) {
				throw new Exception("Directory \"{$newDirectoryPath}\" must be writable");
			}

			$newDirectory->requireFolder($newDirectoryPath);
			$newDirectory->refresh();

			if (!$newDirectory->isExists() || !$newDirectory->isWritable()) {
				throw new Exception("Can't create directory \"{$newDirectoryPath}\"");
			}

			return $newDirectory;
		}

		/**
		 * Запускает журналирование суперглобальных массивов
		 * @return $this;
		 */
		protected function collectGlobalEnvironment() {
			$this->collectArray('$_COOKIE', $_COOKIE)
				->collectArray('$_SESSION', isset($_SESSION) ? $_SESSION : [])
				->collectArray('$_POST', $_POST)
				->collectArray('$_GET', $_GET)
				->collectArray('$_FILES', $_FILES);

			if (function_exists('apache_request_headers')) {
				$this->collectArray('Request headers', apache_request_headers());
			}

			if (function_exists('apache_response_headers')) {
				$this->collectArray('Response headers', apache_response_headers());
			}

			return $this;
		}

		/**
		 * Упаковывает массив в Журнал
		 * @param string $name название массива
		 * @param array $array массив
		 * @return $this
		 */
		protected function collectArray($name, $array) {
			if (!is_array($array) || empty($array)) {
				return $this;
			}

			$msg = "[{$name}]" . PHP_EOL;

			foreach ($array as $index => $value) {
				$msg .= "\t[" . $index . ']' . PHP_EOL . "\t" . '(' . gettype($value) . ') ';

				if (is_array($value)) {
					$value = $this->serializeArray($value);
				}

				$msg .= $value . PHP_EOL . PHP_EOL;
			}

			$appendTimer = false;
			$this->push($msg, $appendTimer);

			return $this;
		}

		/**
		 * Сериализует массив  строку
		 * @param array $array
		 * @return string
		 */
		protected function serializeArray($array) {
			$serializedArray = [];

			foreach ($array as $index => $value) {
				if (is_array($value)) {
					$value = $this->serializeArray($value);
				}

				$serializedArray[] = "'$index' => '" . $value . "'";
			}

			return '[' . implode(', ', $serializedArray) . ']';
		}

		/**
		 * Запускает таймер
		 * @return $this
		 */
		protected function runTimer() {
			$this->startTime = microtime(true);
			return $this;
		}

		/**
		 * Возвращает время, прошедшее с запуска таймера
		 * @return float
		 */
		protected function getTimer() {
			$time = microtime(true) - $this->getStartTime();
			return round($time, 7);
		}

		/**
		 * Добавляет в начало сообщения таймер
		 * @param string $message сообщение
		 * @return string
		 */
		protected function appendTimer($message) {
			return '[' . sprintf('%1.7f', $this->getTimer()) . "]\t" . (string) $message;
		}

		/**
		 * Определяет был ли журнал сохранен
		 * @return bool
		 */
		protected function isSaved() {
			return (bool) $this->isSaved;
		}

		/**
		 * Устанавливает был ли журнал сохранен
		 * @param bool $flag
		 * @return $this
		 */
		protected function setSaved($flag = true) {
			$this->isSaved = $flag;
			return $this;
		}

		/**
		 * Возвращает имя файла для журнала
		 * @return string
		 */
		protected function getFileName() {
			return $this->fileName;
		}

		/**
		 * Определяет нужно ли разделять журнал по ip
		 * @return bool
		 */
		protected function isSeparatedByIp() {
			return (bool) $this->separateByIp;
		}

		/**
		 * Возвращает время начала работы логера
		 * @return float
		 */
		protected function getStartTime() {
			return (float) $this->startTime;
		}

		/** @deprecated */
		public function pushGlobalEnviroment() {
			return $this->pushGlobalEnvironment();
		}
	}
