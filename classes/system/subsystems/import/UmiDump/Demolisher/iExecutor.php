<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher;

	use UmiCms\System\Import\UmiDump\Demolisher\Type\iFactory;
	use UmiCms\System\Import\UmiDump\iDemolisher;

	/**
	 * Интерфейс исполнителя удаления данных
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	interface iExecutor extends iDemolisher {

		/**
		 * Конструктор
		 * @param iFactory $factory экземпляр фабрики классов удаления группы однородных данных
		 */
		public function __construct(iFactory $factory);
	}
