<?php

	namespace UmiCms\Mail;

	/**
	 * Абстрактный класс средства отправки писем
	 * @package UmiCms\Mail
	 */
	abstract class Engine implements iEngine {

		/** @var string $subject тема письма */
		private $subject;

		/** @var string $message сообщение письма */
		private $message;

		/** @var string $headers заголовки письма */
		private $headers;

		/** @var string $parameters параметры отправки */
		private $parameters;

		/** @inheritdoc */
		public function setSubject($subject) {
			$this->subject = $subject;
			return $this;
		}

		/** @inheritdoc */
		public function setMessage($message) {
			$this->message = $message;
			return $this;
		}

		/** @inheritdoc */
		public function setHeaders($headers) {
			$this->headers = $headers;
			return $this;
		}

		/** @inheritdoc */
		public function setParameters($parameters) {
			$this->parameters = $parameters;
			return $this;
		}

		/** @inheritdoc */
		abstract public function send($address);

		/**
		 * Возвращает тему письма
		 * @return string
		 */
		protected function getSubject() {
			return $this->subject;
		}

		/**
		 * Возвращает сообщение письма
		 * @return string
		 */
		protected function getMessage() {
			return $this->message;
		}

		/**
		 * Возвращает заголовки письма
		 * @return string
		 */
		protected function getHeaders() {
			return $this->headers;
		}

		/**
		 * Возвращает параметры отправки
		 * @return string
		 */
		protected function getParameters() {
			return $this->parameters;
		}
	}
