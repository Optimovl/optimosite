<?php

	/** Фабрика транзакций */
	class TransactionFactory implements iTransactionFactory {

		use tAtomicOperationStateFile;

		/**
		 * Создает транзакцию манифеста
		 * @param string $name название
		 * @return iTransaction
		 */
		public function create($name) {
			$transaction = new Transaction($name);

			$stateFilePath = $this->getFilePath($transaction);
			$transaction->setStatePath($stateFilePath);
			$transaction->loadState();

			return $transaction;
		}
	}
