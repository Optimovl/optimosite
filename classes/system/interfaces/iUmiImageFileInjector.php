<?php

	/** Интерфейс работника с файлами изображений */
	interface iUmiImageFileInjector {

		/**
		 * Возвращает экземпляр обработчика файлов изображений
		 * @return \iUmiImageFile
		 * @throws Exception
		 */
		public function getImageFileHandler();

		/**
		 * Устанавливает экземпляр обработчика файлов изображений
		 * @param \iUmiImageFile $imageFileHandler экземпляр обработчика файлов изображений
		 * @return $this
		 */
		public function setImageFileHandler(\iUmiImageFile $imageFileHandler);
	}
