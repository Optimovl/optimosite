<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\iCharacteristic;
	use UmiCms\System\Orm\Entity\Collection as AbstractCollection;

	/**
	 * Класс коллекции характеристик торговых предложений
	 * @package UmiCms\System\Trade\Offer\Characteristic
	 */
	class Collection extends AbstractCollection implements iCollection {

		/** @inheritdoc */
		public function filterByDataObject($id) {
			return $this->filter([
				iMapper::DATA_OBJECT_ID => [
					self::COMPARE_TYPE_EQUALS => $id
				]
			]);
		}

		/** @inheritdoc */
		public function filterByField($name) {
			return $this->filter([
				iMapper::NAME => [
					self::COMPARE_TYPE_EQUALS => $name
				]
			]);
		}

		/** @inheritdoc */
		public function extractDataObjectId() {
			return $this->extractField(iMapper::DATA_OBJECT_ID);
		}

		/** @inheritdoc */
		protected function isPushed(iEntity $entity) {
			/** @var iCharacteristic $existEntity */
			$existEntity = $this->get($entity);

			if ($existEntity === null) {
				return false;
			}

			/** @var iCharacteristic $entity */
			if ($existEntity->hasDataObject() && $entity->hasDataObject()) {
				return $existEntity->getDataObjectId() === $entity->getDataObjectId();
			}

			return false;
		}
	}