<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления объектных типов данных
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class ObjectType extends Demolisher {

		/** @var \iUmiObjectTypesCollection $objectTypeCollection коллекция объектных типов данных */
		private $objectTypeCollection;

		/**
		 * Конструктор
		 * @param \iUmiObjectTypesCollection $objectTypeCollection коллекция объектных типов данных
		 */
		public function __construct(\iUmiObjectTypesCollection $objectTypeCollection) {
			$this->objectTypeCollection = $objectTypeCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$objectTypeCollection = $this->getObjectTypeCollection();

			foreach ($this->getObjectTypeExtIdList() as $extId) {
				$id = $sourceIdBinder->getNewTypeIdRelation($sourceId, $extId);

				if ($id === false || $sourceIdBinder->isTypeRelatedToAnotherSource($sourceId, $id)) {
					$this->pushLog(sprintf('Object type "%s" was ignored', $extId));
					continue;
				}

				$objectTypeCollection->delType($id);
				$this->pushLog(sprintf('Object type "%s" was deleted', $id));
			}
		}

		/**
		 * Возвращает список внешних идентификаторов удаляемых объектных типов данных
		 * @return string[]
		 */
		private function getObjectTypeExtIdList() {
			return $this->getNodeValueList('/umidump/types/type/@id');
		}

		/**
		 * Возвращает коллекцию объектных типов данных
		 * @return \iUmiObjectTypesCollection
		 */
		private function getObjectTypeCollection() {
			return $this->objectTypeCollection;
		}
	}
