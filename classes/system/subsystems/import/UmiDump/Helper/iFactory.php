<?php

	namespace UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder;

	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Интерфейс фабрики класса, связующего идентификатору импортируемых сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param iSourceIdBinder $sourceIdBinder экземпляр класса, связующего идентификаторы импортируемых сущностей
		 */
		public function __construct(iSourceIdBinder $sourceIdBinder);

		/**
		 * Создает экземпляр класса связывания идентификаторов импортируемых сущностей
		 * @param int $sourceId идентификатор внешнего источник
		 * @return iSourceIdBinder
		 * @throws \InvalidArgumentException
		 */
		public function create($sourceId);

		/**
		 * Создает экземпляр класса связывания идентификаторов импортируемых сущностей
		 * @param string $sourceName имя внешнего источника
		 * @return iSourceIdBinder
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function createBySourceName($sourceName);
	}
