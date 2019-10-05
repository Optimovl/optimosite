<?php

	/** Трейт работника с файлами изображений */
	trait tUmiImageFileInjector {

		/** @var \iUmiImageFile $imagesHandler обработчик файлов изображений */
		private $imageFileHandler;

		/**
		 * Возвращает экземпляр обработчика файлов изображений
		 * @return \iUmiImageFile
		 * @throws Exception
		 */
		public function getImageFileHandler() {
			if (!$this->imageFileHandler instanceof \iUmiImageFile) {
				throw new Exception('You should set iUmiImageFile first');
			}

			return $this->imageFileHandler;
		}

		/**
		 * Устанавливает экземпляр обработчика файлов изображений
		 * @param \iUmiImageFile $imageFileHandler экземпляр обработчика файлов изображений
		 * @return $this
		 */
		public function setImageFileHandler(\iUmiImageFile $imageFileHandler) {
			$this->imageFileHandler = $imageFileHandler;
			return $this;
		}
	}
