<?php

	/** Интерфейс класса обработки изображений */
	interface iImageProcessor {

		/**
		 * Обрезает изображение до указанных размеров в заданной области
		 * @param string $imagePath путь до изображения
		 * @param int $top - координата y области
		 * @param int $left - координата x области
		 * @param int $width - новая ширина
		 * @param int $height - новая высота
		 * @return bool выполнена ли операция
		 */
		public function crop($imagePath, $top, $left, $width, $height);

		/**
		 * Поворачивает изображение на 90 градусов
		 * @param string $imagePath путь до изображения
		 * @return bool выполнена ли операция
		 */
		public function rotate($imagePath);

		/**
		 * Обрезает изображение до указанных размеров
		 * @param string $imagePath путь до изображения
		 * @param int $width - новая ширина
		 * @param int $height - новая высота
		 * @return bool выполнена ли операция
		 */
		public function resize($imagePath, $width, $height);

		/**
		 * Оптимизирует размер изображения
		 * @param string $imagePath путь до изображения
		 * @param int $quality качество изображение
		 * @return bool выполнена ли операция
		 */
		public function optimize($imagePath, $quality = 75);

		/**
		 * Возвращает mime-type изображения и его размеры
		 * @param string $imagePath путь до изображения
		 * @return array
		 *
		 * [
		 *        'mime' => string,
		 *        'height' => int,
		 *        'width' => int
		 * ]
		 *
		 */
		public function info($imagePath);

		/**
		 * Создает миниатюру изображения
		 * @param string $imagePath путь до изображения
		 * @param string $thumb путь до миниатюры
		 * @param int $width ширина миниатюры
		 * @param int $height высота миниатюры
		 * @param int|bool $quality качество миниатюры
		 * @return bool выполнена ли операция
		 * @throws coreException
		 */
		public function thumbnail($imagePath, $thumb, $width, $height, $quality = false);

		/**
		 * Обрезает изображение и создает из него миниатюру
		 * @param string $imagePath путь до изображения
		 * @param string $thumbPath путь до миниатюры
		 * @param int $width ширина миниатюры
		 * @param int $height высота миниатюры
		 * @param int $cropWidth ширина до которой обрезается исходное изображение
		 * @param int $cropHeight высота до которой обрезается исходное изображение
		 * @param int $xCord координата от которой начинается обрезка изображения по оси x
		 * @param int $yCord координата от которой начинается обрезка изображения по оси y
		 * @param bool $isSharpen повышать четкость изображения
		 * @param int $quality качество изображения
		 * @return bool выполнена ли операция
		 * @throws coreException
		 */
		public function cropThumbnail(
			$imagePath,
			$thumbPath,
			$width,
			$height,
			$cropWidth,
			$cropHeight,
			$xCord,
			$yCord,
			$isSharpen,
			$quality = 75
		);

		/**
		 * Возвращает тип обработчика изображений
		 * @return string
		 */
		public function getLibType();
	}
