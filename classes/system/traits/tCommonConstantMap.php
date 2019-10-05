<?php

	/** Трейт карты констант */
	trait tCommonConstantMap {

		/** @var array $constants константы класса */
		private $constants;

		/** Конструктор */
		public function __construct() {
			$classReflection = new ReflectionClass(get_class($this));
			$this->constants = $classReflection->getConstants();
		}

		/**
		 * Возвращает значение константы или null, если она не объявлена
		 * @param string $constant имя константы
		 * @return mixed|null
		 */
		public function get($constant) {
			if (isset($this->constants[$constant])) {
				return $this->constants[$constant];
			}

			return null;
		}
	}

