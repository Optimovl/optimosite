<?php
	namespace UmiCms\System\Trade\Stock;

	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Trade\iStock;
	use UmiCms\System\Orm\Composite\Entity;

	/**
	 * Класс складского остатка
	 * @package UmiCms\System\Trade\Stock
	 */
	class Balance extends Entity implements iBalance {

		/** @var int|null $offerId идентификатор торгового предложения */
		protected $offerId;

		/** @var int|null $stockId идентификатор склада */
		protected $stockId;

		/** @var int $value остаток */
		protected $value = 0;

		/** @var iOffer|null $offer торговое предложение */
		protected $offer;

		/** @var iStock|null $stock склад */
		protected $stock;

		/** @inheritdoc */
		public function getOfferId() {
			return $this->offerId;
		}

		/** @inheritdoc */
		public function setOfferId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect trade stock balance offer id given');
			}

			$this->offer = null;
			return $this->setDifferentValue('offerId', $id);
		}

		/** @inheritdoc */
		public function getStockId() {
			return $this->stockId;
		}

		/** @inheritdoc */
		public function setStockId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect trade stock balance stock id given');
			}

			$this->stock = null;
			return $this->setDifferentValue('stockId', $id);
		}

		/** @inheritdoc */
		public function getValue() {
			return $this->value;
		}

		/** @inheritdoc */
		public function setValue($value) {
			if (!is_int($value) || $value < 0) {
				throw new \ErrorException('Incorrect trade stock balance value given');
			}

			return $this->setDifferentValue('value', $value);
		}

		/** @inheritdoc */
		public function getOffer() {
			if ($this->offer === null) {
				$this->loadRelation(Balance\iMapper::OFFER);
			}

			return $this->offer;
		}

		/** @inheritdoc */
		public function setOffer(iOffer $offer) {
			return $this->setOfferId($offer->getId())
				->setDifferentValue('offer', $offer);
		}

		/** @inheritdoc */
		public function getStock() {
			if ($this->stock === null) {
				$this->loadRelation(Balance\iMapper::STOCK);
			}

			return $this->stock;
		}

		/** @inheritdoc */
		public function setStock(iStock $stock) {
			return $this->setStockId($stock->getId())
				->setDifferentValue('stock', $stock);
		}
	}