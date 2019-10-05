<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Entity\Collection as AbstractCollection;

	/**
	 * Класс коллекции складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Collection extends AbstractCollection implements iCollection {

		/** @inheritdoc */
		public function filterByStock($id) {
			return $this->filter([
				iMapper::STOCK_ID => [
					self::COMPARE_TYPE_EQUALS => $id
				]
			]);
		}

		/** @inheritdoc */
		public function filterByOffer($id) {
			return $this->filter([
				iMapper::OFFER_ID => [
					self::COMPARE_TYPE_EQUALS => $id
				]
			]);
		}

		/** @inheritdoc */
		public function extractOfferId() {
			return $this->extractField(iMapper::OFFER_ID);
		}

		/** @inheritdoc */
		public function extractUniqueOfferId() {
			return $this->extractUniqueField(iMapper::OFFER_ID);
		}

		/** @inheritdoc */
		public function extractStockId() {
			return $this->extractField(iMapper::STOCK_ID);
		}

		/** @inheritdoc */
		public function extractUniqueStockId() {
			return $this->extractUniqueField(iMapper::STOCK_ID);
		}
	}