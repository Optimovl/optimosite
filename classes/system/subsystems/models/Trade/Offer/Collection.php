<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Orm\Entity\Collection as AbstractCollection;
	use UmiCms\System\Trade\Offer\Price\iCollection as iPriceCollection;
	use UmiCms\System\Trade\Stock\Balance\iCollection as iStockBalanceCollection;
	use UmiCms\System\Trade\Offer\Characteristic\iCollection as iCharacteristicCollection;

	/**
	 * Класс коллекции торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	class Collection extends AbstractCollection implements iCollection {

		/** @inheritdoc */
		public function sortByPriceCollection(iPriceCollection $collection) {
			return $this->sortByIdList($collection->extractOfferId());
		}

		/** @inheritdoc */
		public function sortByStockBalanceCollection(iStockBalanceCollection $collection) {
			return $this->sortByIdList($collection->extractOfferId());
		}

		/** @inheritdoc */
		public function sortByCharacteristicCollection(iCharacteristicCollection $collection) {
			return $this->sortByValueList(iMapper::DATA_OBJECT_ID, $collection->extractDataObjectId());
		}

		/** @inheritdoc */
		public function extractDataObjectId() {
			return $this->extractField(iMapper::DATA_OBJECT_ID);
		}

		/** @inheritdoc */
		public function extractUniqueDataObjectId() {
			return $this->extractUniqueField(iMapper::DATA_OBJECT_ID);
		}

		/** @inheritdoc */
		public function extractTypeId() {
			return $this->extractField(iMapper::TYPE_ID);
		}

		/** @inheritdoc */
		public function extractUniqueTypeId() {
			return $this->extractUniqueField(iMapper::TYPE_ID);
		}
	}