<?php
	namespace UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder;

	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Класс фабрики класса, связующего идентификатору импортируемых сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder
	 */
	class Factory implements iFactory {

		/** @var iSourceIdBinder $sourceIdBinder экземпляр класса, связующего идентификаторы импортируемых сущностей */
		private $sourceIdBinder;

		/** @inheritdoc */
		public function __construct(iSourceIdBinder $sourceIdBinder) {
			$this->setSourceIdBinder($sourceIdBinder);
		}

		/** @inheritdoc */
		public function create($sourceId) {
			$sourceIdBinder = clone $this->getSourceIdBinder();
			return $sourceIdBinder->setSourceId($sourceId);
		}

		/** @inheritdoc */
		public function createBySourceName($sourceName) {
			$sourceIdBinder = clone $this->getSourceIdBinder();
			return $sourceIdBinder->setSourceIdByName($sourceName);
		}

		/**
		 * Возвращает экземпляр класса, связующего идентификаторы импортируемых сущностей
		 * @return iSourceIdBinder
		 */
		private function getSourceIdBinder() {
			return $this->sourceIdBinder;
		}

		/**
		 * Устанавливает экземпляр класса, связующего идентификаторы импортируемых сущностей
		 * @param iSourceIdBinder $sourceIdBinder
		 * @return $this
		 */
		private function setSourceIdBinder(iSourceIdBinder $sourceIdBinder) {
			$this->sourceIdBinder = $sourceIdBinder;
			return $this;
		}
	}
