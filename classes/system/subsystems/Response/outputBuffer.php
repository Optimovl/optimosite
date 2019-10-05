<?php

	use UmiCms\Service;

	/**
	 * Абстрактный класс буфера вывода
	 * @todo сделать нормальные геттеры и сеттеры
	 */
	abstract class outputBuffer implements iOutputBuffer {

		/** @var string $buffer буферизованная информация конкретного буфера */
		protected $buffer = '';

		/** @var null|int $invokeTime время создания конкретного буфера в микросекундах */
		protected $invokeTime;

		/** @var array $options опции буфера */
		protected $options = [];

		/** @var bool $eventsEnabled включена ли отправка событий */
		private $eventsEnabled = true;

		/** @var string $charset кодировка ответа */
		private $charset = 'utf-8';

		/** @var string $contentType тип контента ответа */
		private $contentType = 'text/html';

		/** @var string $status статус ответа */
		private $status = '200 Ok';

		/** @var array $headerList очередь заголовков для отправки имя => значение */
		private $headerList = [];

		/** Конструктор */
		public function __construct() {
			$this->invokeTime = microtime(true);
		}

		/** @inheritdoc */
		abstract public function send();

		/** @inheritdoc */
		public function clear() {
			$this->buffer = '';
			return $this;
		}

		/** @inheritdoc */
		public function reset() {
			while (ob_get_level()) {
				ob_end_clean();
			}

			return $this;
		}

		/** @inheritdoc */
		public function length() {
			return mb_strlen($this->buffer);
		}

		/** @inheritdoc */
		public function content() {
			return $this->buffer;
		}

		/** @inheritdoc */
		public function push($data) {
			$this->buffer .= $data;
			return $this;
		}

		/** @inheritdoc */
		public function end() {
			$this->send();
			$this->stop();
		}

		/** @inheritdoc */
		public function stop() {
			exit('');
		}

		/** @inheritdoc */
		public function calltime() {
			return round(microtime(true) - $this->invokeTime, 6);
		}

		/** @inheritdoc */
		public function redirect($url, $status = '301 Moved Permanently', $numStatus = 301) {
			$this->push(PHP_EOL . 'Redirected to address: ' . $url . PHP_EOL);
			$this->end();
		}

		/** @inheritdoc */
		public function status($status = false) {
			if ($status) {
				$this->status = $status;
			}
			return $this->status;
		}

		/** @inheritdoc */
		public function getStatusCode() {
			return (int) $this->status;
		}

		/** @inheritdoc */
		public function charset($charset = false) {
			if ($charset) {
				$this->charset = $charset;
			}

			return $this->charset;
		}

		/** @inheritdoc */
		public function contentType($contentType = false) {
			if ($contentType) {
				$this->contentType = $contentType;
			}

			return $this->contentType;
		}

		/** @inheritdoc */
		public function option($key, $value = null) {
			if ($value === null) {
				return isset($this->options[$key]) ? $this->options[$key] : null;
			}

			return $this->options[$key] = $value;
		}

		/** @inheritdoc */
		public function issetHeader($name) {
			if (!is_string($name) || $name === '') {
				throw new wrongParamException('Header name must be not empty string');
			}

			return isset($this->headerList[$name]);
		}

		/** @inheritdoc */
		public function setHeader($name, $value) {
			if (!is_string($name) || $name === '') {
				throw new wrongParamException('Header name must be not empty string');
			}

			if (!is_string($value) || $value === '') {
				throw new wrongParamException('Header value must be not empty string');
			}

			$this->headerList[$name] = $value;
			return $this;
		}

		/** @inheritdoc */
		public function unsetHeader($name) {
			if (!is_string($name) || $name === '') {
				throw new wrongParamException('Header name must be not empty string');
			}

			unset($this->headerList[$name]);
			return $this;
		}

		/** @inheritdoc */
		public function getHeaderList() {
			return $this->headerList;
		}

		/** @inheritdoc */
		public function isEventsEnabled() {
			if ($this->isFatalOccurred()) {
				return false;
			}

			return $this->eventsEnabled;
		}

		/** @inheritdoc */
		public function enableEvents() {
			$this->eventsEnabled = true;
			return $this;
		}

		/** @inheritdoc */
		public function disableEvents() {
			$this->eventsEnabled = false;
			return $this;
		}

		/**
		 * @internal
		 * @inheritdoc
		 * @todo: вынести этот метод отсюда
		 * @param string|null $generatorType
		 * @return string|null
		 */
		public static function contentGenerator($generatorType = null) {
			static $contentGenerator = null;

			if ($generatorType === null) {
				return $contentGenerator;
			}

			return $contentGenerator = $generatorType;
		}

		/** @inheritdoc */
		public function __destruct() {
			$this->send();
		}

		/**
		 * Определяет, что произошла фатальная ошибка
		 * @return bool
		 */
		protected function isFatalOccurred() {
			return $this->getStatusCode() == 500;
		}

		/**
		 * @deprecated
		 * @param $name
		 * @param $arguments
		 * @return null
		 */
		public function __call($name, $arguments) {
			return null;
		}

		/**
		 * @deprecated
		 * @return iOutputBuffer
		 * @throws coreException
		 */
		final public static function current($class = false) {
			$response = Service::Response();

			if ($class) {
				return $response->getBufferByClass($class);
			}

			return $response->getCurrentBuffer();
		}
	}
