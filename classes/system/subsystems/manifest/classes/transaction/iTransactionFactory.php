<?php

	/** Интерфейс фабрики транзакций */
	interface iTransactionFactory {

		/**
		 * Создает транзакцию манифеста
		 * @param string $name название
		 * @return iTransaction
		 */
		public function create($name);
	}
