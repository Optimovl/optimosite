<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Orm\Entity\Facade as AbstractFacade;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;
	use UmiCms\System\Trade\Offer\Price\Type\iFacade as iTypeFacade;
	use UmiCms\System\Trade\Offer\Price\Currency\iFacade as iCurrencyFacade;

	/**
	 * Класс фасада цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	class Facade extends AbstractFacade implements iFacade {

		/** @var iCurrencyFacade $currencyFacade фасад валют */
		private $currencyFacade;

		/** @var iTypeFacade $typeFacade фасад типов цен */
		private $typeFacade;

		/** @inheritdoc */
		public function getListByOfferIdList(array $idList) {
			return $this->getListByValueList(iMapper::OFFER_ID, $idList);
		}

		/** @inheritdoc */
		public function getCollectionByOffer($id) {
			return $this->getCollectionBy(iMapper::OFFER_ID, $id);
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
		public function create(array $attributeList = []) {
			$currencyFacade = $this->getCurrencyFacade();

			if (!isset($attributeList[iMapper::CURRENCY_ID])) {
				$currency = $currencyFacade->getDefault();
			} else {
				$currency = $currencyFacade->get($attributeList[iMapper::CURRENCY_ID]);
			}

			$attributeList[iMapper::CURRENCY_ID] = $currency->getId();
			$typeFacade = $this->getTypeFacade();

			if (!isset($attributeList[iMapper::TYPE_ID])) {
				$type = $typeFacade->getDefault();
			} else {
				$type = $typeFacade->get($attributeList[iMapper::TYPE_ID]);
			}

			if (!$type instanceof iType) {
				throw new \privateException('Cannot get price type');
			}

			$attributeList[iMapper::TYPE_ID] = $type->getId();

			if (!isset($attributeList[iMapper::OFFER_ID])) {
				throw new \ErrorException('Trade offer id expected');
			}

			if ($type->isDefault()) {
				$attributeList[iMapper::IS_MAIN] = true;
			}

			return parent::create($attributeList);
		}

		/** @inheritdoc */
		public function createByOfferAndType($offerId, $typeId) {
			return $this->create([
				iMapper::OFFER_ID => $offerId,
				iMapper::TYPE_ID => $typeId
			]);
		}

		/** @inheritdoc */
		public function createMainPrice($offerId) {
			return $this->create([
				iMapper::OFFER_ID => $offerId,
				iMapper::IS_MAIN => true
			]);
		}

		/** @inheritdoc */
		public function setCurrencyFacade(iCurrencyFacade $facade) {
			$this->currencyFacade = $facade;
			return $this;
		}

		/** @inheritdoc */
		public function setTypeFacade(iTypeFacade $facade) {
			$this->typeFacade = $facade;
			return $this;
		}

		/** @inheritdoc */
		protected function isValidEntity($entity) {
			return $entity instanceof iPrice;
		}

		/**
		 * Возвращает фасад валют
		 * @return iCurrencyFacade
		 */
		private function getCurrencyFacade() {
			return $this->currencyFacade;
		}

		/**
		 * Возвращает фасад типов цен
		 * @return iTypeFacade
		 */
		private function getTypeFacade() {
			return $this->typeFacade;
		}
	}