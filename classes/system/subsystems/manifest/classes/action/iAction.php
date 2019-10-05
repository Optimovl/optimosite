<?php

	/** Интерфейс команды */
	interface iAction extends iAtomicOperation {

		/**
		 * Конструктор
		 * @param string $name имя
		 * @param array $params параметры:
		 *
		 * [
		 *      # => [
		 *          'name' => 'value'
		 *      ]
		 * ]
		 */
		public function __construct($name, array $params = []);

		/**
		 * Возвращает заголовок/наименование команды
		 * @return string
		 */
		public function getTitle();
	}
