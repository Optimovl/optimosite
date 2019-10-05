<?php

	/** Интерфейс обработчика хода выполнения атомарного действия */
	interface iAtomicOperationCallback {

		/**
		 * Обработчик, запускаемый перед выполнением действия
		 * @param iAtomicOperation $operation действие
		 */
		public function onBeforeExecute(iAtomicOperation $operation);

		/**
		 * Обработчик, запускаемый после выполнения действия
		 * @param iAtomicOperation $operation действие
		 */
		public function onAfterExecute(iAtomicOperation $operation);

		/**
		 * Обработчик, запускаемый перед откатом действия
		 * @param iAtomicOperation $operation действие
		 */
		public function onBeforeRollback(iAtomicOperation $operation);

		/**
		 * Обработчик, запускаемый после отката действия
		 * @param iAtomicOperation $operation действие
		 */
		public function onAfterRollback(iAtomicOperation $operation);

		/**
		 * Обработчик, запускаемый при получении исключения
		 * @param iAtomicOperation $operation действие
		 * @param Exception $exception
		 */
		public function onException(iAtomicOperation $operation, Exception $exception);

		/**
		 * Возвращает журнал
		 * @return array
		 */
		public function getLog();
	}
