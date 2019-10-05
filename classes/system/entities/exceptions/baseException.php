<?php

	/**
	 * Абстрактный класс базового исключения.
	 * Главная особенность - хранит в себе все брошенные исключения, поэтому приводить к переполнению памяти.
	 */
	abstract class baseException extends Exception {

		/** @var string $stringCode строковой код */
		protected $stringCode;

		/** @var int $id идентификатор */
		protected $id;

		/** @var baseException[] $catchedExceptions список брошенных исключений */
		public static $catchedExceptions = [];

		/** @inheritdoc */
		public function __construct($message, $code = 0, $stringCode = '') {
			baseException::$catchedExceptions[$this->getId()] = $this;
			$this->stringCode = $stringCode;
			$message = def_module::parseTPLMacroses($message);
			parent::__construct($message, $code);
		}

		/**
		 * Возвращает строковой код
		 * @return string
		 */
		public function getStrCode() {
			return (string) $this->stringCode;
		}

		/** Выгружает исключение из памяти */
		public function unregister() {
			$id = $this->getId();

			if (isset(baseException::$catchedExceptions[$id])) {
				unset(baseException::$catchedExceptions[$id]);
			}
		}

		/**
		 * Возвращает идентификатор
		 * @return int
		 */
		protected function getId() {
			static $id = 0;

			if ($this->id === null) {
				$this->id = $id++;
			}

			return $this->id;
		}
	}
