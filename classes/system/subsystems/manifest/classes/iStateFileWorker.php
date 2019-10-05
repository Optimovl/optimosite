<?php

	/** Интерфейс работника с файлом состояния */
	interface iStateFileWorker {

		/**
		 * Устанавливает путь до файла состояния
		 * @param string $filePath путь до файла
		 * @return $this
		 * @throws Exception
		 */
		public function setStatePath($filePath);

		/**
		 * Загружает состояние.
		 * @return $this
		 */
		public function loadState();
	}
