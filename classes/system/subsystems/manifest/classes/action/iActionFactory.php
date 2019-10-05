<?php

	/** Интерфейс фабрики атомарных команд */
	interface iActionFactory {

		/**
		 * Создает команду транзакции
		 * @param string $name название команды
		 * @param array $params параметры команды:
		 *
		 * [
		 *      # => [
		 *          'name' => 'value'
		 *      ]
		 * ]
		 *
		 * @param string $filePath путь до реализации
		 * @return iAction
		 * @throws Exception
		 */
		public function create($name, array $params = [], $filePath);
	}
