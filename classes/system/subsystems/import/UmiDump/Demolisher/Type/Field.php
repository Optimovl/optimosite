<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления полей
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Field extends Demolisher {

		/** @var \iUmiFieldsCollection $fieldCollection коллекция полей */
		private $fieldCollection;

		/** @var \iUmiObjectTypesCollection $objectTypeCollection коллекция объектных типов */
		private $objectTypeCollection;

		/**
		 * Конструктор
		 * @param \iUmiFieldsCollection $fieldCollection коллекция полей
		 * @param \iUmiObjectTypesCollection $objectTypeCollection коллекция объектных типов
		 */
		public function __construct(\iUmiFieldsCollection $fieldCollection, \iUmiObjectTypesCollection $objectTypeCollection) {
			$this->fieldCollection = $fieldCollection;
			$this->objectTypeCollection = $objectTypeCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$objectTypeCollection = $this->getObjectTypeCollection();
			$fieldCollection = $this->getFieldCollection();

			foreach ($this->getFieldExtNameTree() as $typeExtId => $fieldExtNameList) {
				$typeId = $sourceIdBinder->getNewTypeIdRelation($sourceId, $typeExtId);
				$type = $objectTypeCollection->getType($typeId);

				if (!$type instanceof \iUmiObjectType) {
					$this->pushLog(sprintf('Field of type "%s" was ignored', $typeExtId));
					continue;
				}

				foreach ($fieldExtNameList as $fieldExtName) {
					$fieldId = $sourceIdBinder->getNewFieldId($sourceId, $typeId, $fieldExtName);

					if ($fieldId === false || $sourceIdBinder->isFieldRelatedToAnotherSource($sourceId, $fieldId)) {
						$this->pushLog(sprintf('Field "%s" was ignored', $fieldExtName));
						continue;
					}

					$fieldCollection->delById($fieldId);
					$this->pushLog(sprintf('Field "%s" was deleted', $fieldId));
				}
			}
		}

		/**
		 * Возвращает список внешних идентификаторов (названий) удаляемых полей, сгруппированный по внешним
		 * идентификаторам объектных типов данных
		 * @return array
		 *
		 * [
		 *      extTypeId => [
		 *          extFieldName
		 *      ]
		 * ]
		 */
		private function getFieldExtNameTree() {
			$result = [];

			$result = $this->getNodeValueTree(
				$result,
				'/umidump/types/type',
				'id',
				'fieldgroups/group/field/@name'
			);

			$result = $this->getNodeValueTree(
				$result,
				'/umidump/pages/page',
				'type-id',
				'properties/group/property/@name'
			);

			$result = $this->getNodeValueTree(
				$result,
				'/umidump/objects/object',
				'type-id',
				'properties/group/property/@name'
			);

			return $result;
		}

		/**
		 * Возвращает коллекцию полей
		 * @return \iUmiFieldsCollection
		 */
		private function getFieldCollection() {
			return $this->fieldCollection;
		}

		/**
		 * Возвращает коллекцию объектных типов
		 * @return \iUmiObjectTypesCollection
		 */
		private function getObjectTypeCollection() {
			return $this->objectTypeCollection;
		}
	}
