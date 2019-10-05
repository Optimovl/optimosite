<?php
	namespace UmiCms\System\Import\UmiDump\Entity\BaseImporter;

	use UmiCms\System\Import\UmiDump\Entity\iBaseImporter;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Интерфейс фабрики базовых импортеров сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity\BaseImporter
	 */
	interface iFactory {

		/**
		 * Создает базовый импортер сущностей
		 * @param \DOMXPath $parser парсер импортирумоего файла в формате umiDump
		 * @param iSourceIdBinder $sourceIdBinder связыватель внешних и внутренних идентификаторов
		 * @return iBaseImporter
		 */
		public function create(\DOMXPath $parser, iSourceIdBinder $sourceIdBinder);
	}