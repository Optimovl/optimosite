<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Orm\Entity\Collection as AbstractCollection;

	/**
	 * Класс коллекции цен торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	class Collection extends AbstractCollection implements iCollection {

		/** @inheritdoc */
		public function filterByType($id) {
			return $this->filter([
				iMapper::TYPE_ID => [
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
		public function extractTypeId() {
			return $this->extractField(iMapper::TYPE_ID);
		}

		/** @inheritdoc */
		public function extractUniqueTypeId() {
			return $this->extractUniqueField(iMapper::TYPE_ID);
		}

		/** @inheritdoc */
		public function extractCurrencyId() {
			return $this->extractField(iMapper::CURRENCY_ID);
		}

		/** @inheritdoc */
		public function extractUniqueCurrencyId() {
			return $this->extractUniqueField(iMapper::CURRENCY_ID);
		}

		/** @inheritdoc */
		public function getMain() {
			return $this->getFirstBy(iMapper::IS_MAIN, true);
		}
	}