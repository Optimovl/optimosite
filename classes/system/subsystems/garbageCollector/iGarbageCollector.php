<?php

	/** Интерфейс класса сбора мусора */
	interface iGarbageCollector {

		/**
		 * Запускает сборщик мусора
		 * @return iGarbageCollector
		 */
		public function run();

		/**
		 * Устанавливает максимальное количество выполняемых итераций
		 * @param int $maxIterationsCount максимальное количество выполняемых итераций
		 * @return iGarbageCollector
		 */
		public function setMaxIterationCount($maxIterationsCount);

		/**
		 * Возвращает максимальное количество выполняемых итераций
		 * @return int
		 */
		public function getMaxIterationCount();

		/**
		 * Возвращает количество выполненных итераций
		 * @return int
		 */
		public function getExecutedIterationsCount();
	}
