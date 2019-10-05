<?php

	/** Абстрактный параметр метода инициализации сервиса */
	abstract class AbstractReference {

		/** @var string $name имя параметра */
		private $name;

		/** @param string $name имя параметра */
		public function __construct($name) {
			$this->name = $name;
		}

		/**
		 * Возвращает имя параметра
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}
	}
