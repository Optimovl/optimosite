<?php

	namespace UmiCms\System\Module\Permissions;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;

	/** Загрузчик прав модулей */
	interface iLoader {

		/**
		 * Конструктор.
		 * @param \iCmsController $cmsController контроллер приложения
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 * @param FileFactory $fileFactory фабрика файлов
		 */
		public function __construct(
			\iCmsController $cmsController,
			DirectoryFactory $directoryFactory,
			FileFactory $fileFactory
		);

		/**
		 * Загружает права модуля из файлов permissions.*.php
		 * @param string $module название модуля
		 * @return array
		 * [
		 *     <moduleName> => []
		 * ]
		 */
		public function load($module);
	}
