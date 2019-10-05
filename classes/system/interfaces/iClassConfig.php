<?php

	/**
	 * Interface iClassConfig
	 * Интерфейс конфигурации класса
	 */
	interface iClassConfig {

		/**
		 * Конструктор
		 * @param string $class имя класса
		 * @param array $config конфигурация класса
		 * @throws Exception
		 */
		public function __construct($class, $config);

		/**
		 * Возвращает значение из конфигурации. Может принимать неограниченное число аргументов,
		 * которыые разрешают путь к конкретному значению конфигурации.
		 * @return mixed
		 */
		public function get();
	}
