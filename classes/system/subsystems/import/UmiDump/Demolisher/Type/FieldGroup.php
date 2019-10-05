<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления групп полей
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class FieldGroup extends Demolisher {

		/** @var \iUmiObjectTypesCollection $objectTypeCollection коллекция объектных типов данных */
		private $objectTypeCollection;

		/** @inheritdoc */
		public function __construct(\iUmiObjectTypesCollection $objectTypeCollection) {
			$this->objectTypeCollection = $objectTypeCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$objectTypeCollection = $this->getObjectTypeCollection();

			foreach ($this->getFieldGroupExtNameTree() as $typeExtId => $groupExtNameList) {
				$typeId = $sourceIdBinder->getNewTypeIdRelation($sourceId, $typeExtId);
				$type = $objectTypeCollection->getType($typeId);

				if (!$type instanceof \iUmiObjectType) {
					$this->pushLog(sprintf('Field group of type "%s" was ignored', $typeExtId));
					continue;
				}

				foreach ($groupExtNameList as $groupExtName) {
					$groupId = $sourceIdBinder->getNewGroupId($sourceId, $typeId, $groupExtName);

					if ($groupId === false || $sourceIdBinder->isGroupRelatedToAnotherSource($sourceId, $groupId)) {
						$this->pushLog(sprintf('Field group "%s" was ignored', $groupExtName));
						continue;
					}

					$type->delFieldsGroup($groupId);
					$this->pushLog(sprintf('Field group "%s" was deleted', $groupId));
				}
			}
		}

		/**
		 * Возвращает список внешних идентификаторов (названий) удаляемых групп полей, сгруппированный по внешним
		 * идентификаторам объектных типов данных
		 * @return array
		 *
		 * [
		 *      extTypeId => [
		 *          extGroupName
		 *      ]
		 * ]
		 */
		private function getFieldGroupExtNameTree() {
			$result = [];

			$result = $this->getNodeValueTree(
				$result,
				'/umidump/types/type',
				'id',
				'fieldgroups/group/@name'
			);

			$result = $this->getNodeValueTree(
				$result,
				'/umidump/pages/page',
				'type-id',
				'properties/group/@name'
			);

			$result = $this->getNodeValueTree(
				$result,
				'/umidump/objects/object',
				'type-id',
				'properties/group/@name'
			);

			return $result;
		}

		/**
		 * Возвращает коллекцию объектных типов данных
		 * @return \iUmiObjectTypesCollection
		 */
		private function getObjectTypeCollection() {
			return $this->objectTypeCollection;
		}
	}
