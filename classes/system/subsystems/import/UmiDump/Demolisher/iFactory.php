<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher\iFileSystem;
	use UmiCms\System\Import\UmiDump\iDemolisher;

	/**
	 * Интерфейс фабрики класса удаления группы однородных данных
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param \iServiceContainer $serviceContainer контейнер сервисов
		 */
		public function __construct(\iServiceContainer $serviceContainer);

		/**
		 * Создает экземпляр класса удаления группы однородных данных
		 * @param string $name имя группы (Directory, Domain, Template etc.)
		 * @return iFileSystem|iDemolisher
		 */
		public function create($name);
	}
