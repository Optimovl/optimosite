<?php

	namespace UmiCms\Classes\System\Entities\Directory;

	/**
	 * Интерфейс фабрики директорий
	 * @package UmiCms\Classes\System\Entities\Directory
	 */
	interface iFactory {

		/**
		 * Создает директорию
		 * @param string $path путь до директории
		 * @return \iUmiDirectory
		 */
		public function create($path);
	}
