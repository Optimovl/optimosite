<?php

	/**
	 * Интерфейс транзакции.
	 * Транзакция состоит из команд (iAction).
	 */
	interface iTransaction extends iReadinessWorker, iAtomicOperation {

		/**
		 * Конструктор
		 * @param string $name название транзакции
		 */
		public function __construct($name);

		/**
		 * Возвращает заголовок/наименование
		 * @return string
		 */
		public function getTitle();

		/**
		 * Добавляет команду в транзакцию
		 * @param iAction $action команда
		 */
		public function addAction(iAction $action);
	}
