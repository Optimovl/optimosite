<?php
	namespace UmiCms\System\Import\UmiDump\Entity\BaseImporter;

	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Класс фабрики базовых импортеров сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity\BaseImporter
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create(\DOMXPath $parser, iSourceIdBinder $sourceIdBinder) {
			return new \xmlEntityImporter($parser, $sourceIdBinder);
		}
	}