<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher;

	use UmiCms\System\Import\UmiDump\iDemolisher;

	/**
	 * Интерфейс класса удаления элементов файловой системы.
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	interface iFileSystem extends iDemolisher {

		/**
		 * Устанавливает корневую директорию.
		 * Используется для удаления файлов и директорий.
		 * @param string $path абсолютный путь до корневой директории системы.
		 * @return iFileSystem
		 */
		public function setRootPath($path);
	}
