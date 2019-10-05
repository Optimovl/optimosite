<?php

	/** Базовый класс синглтона */
	abstract class singleton {

		private static $instances = [];

		/** Конструктор, который необходимо перегрузить в дочернем классе */
		abstract protected function __construct();

		/**
		 * Получить экземпляр класса, необходимо перегрузить в дочернем классе:
		 * parent::getInstance(__CLASS__)
		 * @param string $c имя класса
		 * @return singleton|mixed экземпляр класса
		 */
		public static function getInstance($c = null) {
			if (!isset(singleton::$instances[$c])) {
				singleton::$instances[$c] = new $c;
			}
			return singleton::$instances[$c];
		}

		/** Запрещаем копирование */
		public function __clone() {
			throw new coreException('Singletone clonning is not permitted.');
		}

		/**
		 * @static
		 * Выставляет экземпляр для синглтона
		 * Использовать только для написания unit-тестов
		 * @param self $instance экземпляр
		 * @param string|null $className имя класса-синглтона
		 * @return singleton
		 * @throws coreException
		 */
		public static function setInstance($instance, $className = null) {
			if ($className === null) {
				throw new coreException('Unknown class name for set instance.');
			}
			return singleton::$instances[$className] = $instance;
		}

		/**
		 * Получить языкозависимую строку по ее ключу
		 * @param string $label ключ строки
		 * @return string значение строки в текущей языковой версии
		 */
		protected function translateLabel($label) {
			$str = startsWith($label, 'i18n::') ? getLabel(mb_substr($label, 6)) : getLabel($label);
			return $str === null ? $label : $str;
		}

		/** @deprecated */
		protected function disableCache() {
			return null;
		}
	}
