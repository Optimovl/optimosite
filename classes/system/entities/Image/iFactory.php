<?php
	namespace UmiCms\Classes\System\Entities\Image;

	/**
	 * Интерфейс фабрики изображений
	 * @package UmiCms\Classes\System\Entities\Image
	 */
	interface iFactory {

		/**
		 * Создает изображение
		 * @param string $path путь до изображения
		 * @return \iUmiImageFile
		 */
		public function create($path);

		/**
		 * Создает изображение c переданными атрибутами
		 * @param string $path путь до изображения
		 * @param array $attributeList список атрибутов
		 * @example
		 * [
		 *  'alt' => 'alt изображения',
		 *  'title' => 'title изображения',
		 *  'order' => 'Порядок изображений для множественных изображений'
		 * ]
		 * @return \iUmiImageFile
		 */
		public function createWithAttributes($path, $attributeList = []);
	}