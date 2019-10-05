<?php

	/**
	 * Interface iClassConfigManager
	 * Интерфейс классов управления конфигурациями класса
	 */
	interface iClassConfigManager {

		/**
		 * Устанавливает имя класса, с конфигурациями которого будет производиться управление
		 * @param string $class имя класса
		 */
		public static function setItemClass($class);

		/**
		 * Устанавливает конфигурацию класса
		 * @param string|array $config , если передана строка,
		 * то аргумент будет считаться путем до файла с конфигурацией, если передан массив,
		 * то аргумент будет считаться конфигурацией
		 * @return bool
		 */
		public static function setConfig($config);

		/**
		 * Возвращает конфигурацию класса
		 * @return mixed
		 */
		public static function getConfig();
	}
