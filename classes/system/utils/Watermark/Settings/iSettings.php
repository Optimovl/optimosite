<?php

	namespace UmiCms\Classes\System\Utils\Watermark\Settings;

	use UmiCms\Classes\System\Utils\Settings\iSettings as iMainSettings;

	/**
	 * Интерфейс настроек водяного знака
	 * @package UmiCms\Classes\System\Utils\Watermark\Settings
	 */
	interface iSettings extends iMainSettings {

		/**
		 * Возвращает путь до накладываемого изображения
		 * @return string
		 */
		public function getImagePath();

		/**
		 * Устанавливает путь до накладываемого изображения
		 * @param string $path путь
		 * @return $this
		 */
		public function setImagePath($path);

		/**
		 * Возвращает прозрачность (от 0 до 100, где 100 - непрозрачный)
		 * @return int
		 */
		public function getAlpha();

		/**
		 * Устанавливает прозрачность
		 * @param int $alpha новое значение (от 0 до 100, где 100 - непрозрачный)
		 * @return $this
		 */
		public function setAlpha($alpha);

		/**
		 * Возвращает вертикальное положение
		 * @return string
		 */
		public function getVerticalAlign();

		/**
		 * Устанавливает вертикальное положение
		 * @param string $align новое значение
		 * @return $this
		 */
		public function setVerticalAlign($align);

		/**
		 * Возвращает горизонтальное положение
		 * @return string
		 */
		public function getHorizontalAlign();

		/**
		 * Устанавливает горизонтальное положение
		 * @param string $align
		 * @return $this
		 */
		public function setHorizontalAlign($align);
	}
