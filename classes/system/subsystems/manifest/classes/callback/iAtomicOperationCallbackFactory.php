<?php

	/** Интерфейс фабрики обработчиков хода выполнения атомарного действия */
	interface iAtomicOperationCallbackFactory {

		/** @const string JSON тип обработчика, который логгирует процесс буфер в виде списка js функций */
		const JSON = 'Json';

		/** @const string DEFAULT тип обработчика, который логгирует процесс буфер в виде простого текста */
		const COMMON = 'Common';

		/**
		 * Создает обработчик манифеста
		 * @param string $type тип обработчика манифеста
		 * @return iAtomicOperationCallback
		 * @throws Exception
		 */
		public function create($type = iAtomicOperationCallbackFactory::COMMON);
	}
