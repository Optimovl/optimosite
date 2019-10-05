<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Entity\Facade as AbstractFacade;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;

	/**
	 * Класс фасада фасада складких остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Facade extends AbstractFacade implements iFacade {

		/** @inheritdoc */
		public function create(array $attributeList = []) {
			if (!isset($attributeList[iMapper::OFFER_ID], $attributeList[iMapper::STOCK_ID])) {
				throw new \ErrorException('Offer id or stock id expected');
			}

			return parent::create($attributeList);
		}

		/** @inheritdoc */
		public function createByOfferAndStock($offerId, $stockId) {
			return $this->create([
				iMapper::OFFER_ID => $offerId,
				iMapper::STOCK_ID => $stockId
			]);
		}

		/** @inheritdoc */
		public function getCollectionByOfferList(array $idList) {
			return $this->getCollectionByValueList(iMapper::OFFER_ID, $idList);
		}

		/** @inheritdoc */
		public function getCollectionByOfferCollection(iOfferCollection $offerCollection) {
			return $this->getCollectionByValueList(iMapper::OFFER_ID, $offerCollection->extractId());
		}

		/** @inheritdoc */
		public function getCollectionByStock($id) {
			return $this->getCollectionBy(iMapper::STOCK_ID, $id);
		}

		/** @inheritdoc */
		public function getCollectionByOffer($id) {
			return $this->getCollectionBy(iMapper::OFFER_ID, $id);
		}

		/** @inheritdoc */
		protected function isValidEntity($entity) {
			return $entity instanceof iBalance;
		}
	}