<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Data\Field\Restriction\iCollection;
	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления ограничений полей
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Restriction extends Demolisher {

		/** @var iCollection $restrictionCollection коллекция ограничений полей */
		private $restrictionCollection;

		/**
		 * Конструктор
		 * @param iCollection $restrictionCollection коллекция ограничений полей
		 */
		public function __construct(iCollection $restrictionCollection) {
			$this->restrictionCollection = $restrictionCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$restrictionCollection = $this->getRestrictionCollection();

			foreach ($this->getRestrictionExtIdList() as $extId) {
				$id = $sourceIdBinder->getNewRestrictionIdRelation($sourceId, $extId);

				if ($id === false || $sourceIdBinder->isRestrictionRelatedToAnotherSource($sourceId, $id)) {
					$this->pushLog(sprintf('Restriction "%s" was ignored', $extId));
					continue;
				}

				$restrictionCollection->delete($id);
				$this->pushLog(sprintf('Restriction "%s" was deleted', $id));
			}
		}

		/**
		 * Возвращает список внешних идентификаторов удаляемых ограничений полей
		 * @return string[]
		 */
		private function getRestrictionExtIdList() {
			return $this->getNodeValueList('/umidump/restrictions/restriction/@id');
		}

		/**
		 * Возвращает коллекцию ограничений полей
		 * @return iCollection
		 */
		private function getRestrictionCollection() {
			return $this->restrictionCollection;
		}
	}
