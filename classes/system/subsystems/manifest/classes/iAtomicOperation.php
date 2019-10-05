<?php

	/** Интерфейс атомарного действия */
	interface iAtomicOperation {

		/**
		 * Возвращает название
		 * @return string
		 */
		public function getName();

		/**
		 * Запускает выполнение
		 * @return $this
		 */
		public function execute();

		/**
		 * Откатывает результат выполнения
		 * @return mixed
		 */
		public function rollback();

		/**
		 * Устанавливает обработчик хода выполнения
		 * @param iAtomicOperationCallback $callback
		 */
		public function setCallback(iAtomicOperationCallback $callback);
	}
