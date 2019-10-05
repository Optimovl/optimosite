<?php

	interface iUmiImageFile extends iUmiFile {

		/**
		 * Возвращает mime тип изображения
		 * @return string
		 * @throws ErrorException
		 */
		public function getMimeType();

		/**
		 * Возвращает альтернативный текст для отображения
		 * @return string|null
		 */
		public function getAlt();

		/**
		 * Устанавливает альтернативный текст для отображения
		 * @param string $alt альтернативный текст для отображения
		 * @return iUmiImageFile
		 */
		public function setAlt($alt);

		/**
		 * Возвращает наименование изображения
		 * @return string|null
		 */
		public function getTitle();

		/**
		 * Устанавливает наименование изображения
		 * @param string $title наименование изображения
		 * @return iUmiImageFile
		 */
		public function setTitle($title);

		/**
		 * Получить ширину изображения
		 * @return int
		 */
		public function getWidth();

		/**
		 * Получить высоту изображения
		 * @return int
		 */
		public function getHeight();
	}
