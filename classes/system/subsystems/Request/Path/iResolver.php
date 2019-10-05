<?php

	namespace UmiCms\System\Request\Path;

	use UmiCms\System\Request\Http\iGet;

	/**
	 * Интерфейс распознавателя обрабатываемого пути
	 * @package UmiCms\System\Request\Path
	 */
	interface iResolver {

		/**
		 * Конструктор
		 * @param iGet $getContainer контейнера GET параметров
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(iGet $getContainer, \iConfiguration $configuration);

		/**
		 * Возвращает путь
		 * @return string
		 */
		public function get();

		/**
		 * Возвращает части пути
		 * @return array
		 */
		public function getParts();
	}
