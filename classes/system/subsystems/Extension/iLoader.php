<?php

	namespace UmiCms\System\Extension;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;

	/**
	 * Интерфейс загрузчика расширений модулей
	 * @package UmiCms\System\Extension
	 */
	interface iLoader {

		/**
		 * Конструктор
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 * @param FileFactory $fileFactory фабрика файлов
		 */
		public function __construct(DirectoryFactory $directoryFactory, FileFactory $fileFactory);

		/**
		 * Устанавливает модуль
		 * @param \def_module $module модуль
		 * @return $this
		 */
		public function setModule(\def_module $module);

		/**
		 * Загружает общие расширения
		 * @return $this
		 */
		public function loadCommon();

		/**
		 * Загружает административные расширения
		 * @return $this
		 */
		public function loadAdmin();

		/**
		 * Загружает сайтовые расширения
		 * @return $this
		 */
		public function loadSite();
	}
