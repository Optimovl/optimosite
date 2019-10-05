<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления объектов
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Objects extends Demolisher {

		/** @var \iUmiObjectsCollection $objectCollection коллекция объектов */
		private $objectCollection;

		/**
		 * Конструктор
		 * @param \iUmiObjectsCollection $objectCollection коллекция объектов
		 */
		public function __construct(\iUmiObjectsCollection $objectCollection) {
			$this->objectCollection = $objectCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$objectCollection = $this->getObjectCollection();

			foreach ($this->getObjectExtIdList() as $extId) {
				$id = $sourceIdBinder->getNewObjectIdRelation($sourceId, $extId);

				if ($id === false || $sourceIdBinder->isObjectRelatedToAnotherSource($sourceId, $id)) {
					$this->pushLog(sprintf('Object "%s" was ignored', $extId));
					continue;
				}

				$objectCollection->delObject($id);
				$this->pushLog(sprintf('Object "%s" was deleted', $id));
			}
		}

		/**
		 * Возвращает список внешних идентификаторов удаляемых объектов
		 * @return string[]
		 */
		private function getObjectExtIdList() {
			return $this->getNodeValueList('/umidump/objects/object/@id');
		}

		/**
		 * Возвращает коллекцию объектов
		 * @return \iUmiObjectsCollection
		 */
		private function getObjectCollection() {
			return $this->objectCollection;
		}
	}
