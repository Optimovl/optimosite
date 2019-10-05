<?php

	/** Интерфейс работника с готовностью */
	interface iReadinessWorker {

		/**
		 * Определяет готов или нет
		 * @return bool
		 */
		public function isReady();
	}
