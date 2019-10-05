<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Composite\Entity;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Trade\Offer\Price\iCurrency;
	use UmiCms\System\Trade\Offer\Price\Currency\iFacade as iCurrencyFacade;

	/**
	 * Класс цены торгового предложения
	 * @package UmiCms\System\Trade\Offer
	 */
	class Price extends Entity implements iPrice {

		/** @var float $value значение */
		protected $value = 0.0;

		/** @var int|null $offerId идентификатор торгового предложения */
		protected $offerId;

		/** @var int|null $currencyId идентификатор валюты */
		protected $currencyId;

		/** @var int|null $typeId идентификатор типа */
		protected $typeId;

		/** @var bool $isMain является ли цена основной */
		protected $isMain = false;

		/** @var iCurrencyFacade $currencyFacade фасад валют */
		private $currencyFacade;

		/** @var iOffer|null $offer торговое предложение */
		protected $offer;

		/** @var iCurrency|null $currency валюта */
		protected $currency;

		/** @var iType|null $type тип цены */
		protected $type;

		/** @inheritdoc */
		public function getValue(iCurrency $currency = null) {
			$currencyFacade = $this->getCurrencyFacade();
			$priceCurrency = $this->getCurrency();
			$outputCurrency = $currency ?: $currencyFacade->getCurrent();
			return $currencyFacade->calculate($this->value, $priceCurrency, $outputCurrency);
		}

		/** @inheritdoc */
		public function setValue($value, iCurrency $currency = null) {
			$value = ($value === 0) ? (float) $value : $value;

			if (!is_float($value) || $value < 0) {
				throw new \ErrorException('Incorrect trade offer price value given');
			}

			$currencyFacade = $this->getCurrencyFacade();
			$priceCurrency = $this->getCurrency();
			$inputCurrency = $currency ?: $currencyFacade->getCurrent();
			$calculatedValue = $currencyFacade->calculate($value, $inputCurrency, $priceCurrency);
			$roundedValue = round($calculatedValue, 2);

			return $this->setDifferentValue('value', $roundedValue);
		}

		/** @inheritdoc */
		public function getOfferId() {
			return $this->offerId;
		}

		/** @inheritdoc */
		public function setOfferId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect trade offer id for price given');
			}

			$this->offer = null;
			return $this->setDifferentValue('offerId', $id);
		}

		/** @inheritdoc */
		public function getCurrencyId() {
			return $this->currencyId;
		}

		/** @inheritdoc */
		public function setCurrencyId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect trade offer price currency id given');
			}

			$this->currency = null;
			return $this->setDifferentValue('currencyId', $id);
		}

		/** @inheritdoc */
		public function getTypeId() {
			return $this->typeId;
		}

		/** @inheritdoc */
		public function setTypeId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect trade offer price type id given');
			}

			$this->type = null;
			return $this->setDifferentValue('typeId', $id);
		}

		/** @inheritdoc */
		public function isMain() {
			return $this->isMain;
		}

		/** @inheritdoc */
		public function setMain($flag = true) {
			if (!is_bool($flag)) {
				throw new \ErrorException('Incorrect trade offer price main flag given');
			}

			return $this->setDifferentValue('isMain', $flag);
		}

		/** @inheritdoc */
		public function getOffer() {
			if ($this->offer === null) {
				$this->loadRelation(Price\iMapper::OFFER);
			}

			return $this->offer;
		}

		/** @inheritdoc */
		public function setOffer(iOffer $offer) {
			return $this->setOfferId($offer->getId())
				->setDifferentValue('offer', $offer);
		}

		/** @inheritdoc */
		public function getCurrency() {
			if ($this->currency === null) {
				$this->loadRelation(Price\iMapper::CURRENCY);
			}

			return $this->currency;
		}

		/** @inheritdoc */
		public function setCurrency(iCurrency $currency) {
			return $this->setCurrencyId($currency->getId())
				->setDifferentValue('currency', $currency);
		}

		/** @inheritdoc */
		public function getType() {
			if ($this->type === null) {
				$this->loadRelation(Price\iMapper::TYPE);
			}

			return $this->type;
		}

		/** @inheritdoc */
		public function setType(iType $type) {
			return $this->setTypeId($type->getId())
				->setDifferentValue('type', $type);
		}

		/**
		 * Устанавливает фасад валют
		 * @param iCurrencyFacade $facade фасад валют
		 * @return $this
		 */
		public function setCurrencyFacade(iCurrencyFacade $facade) {
			$this->currencyFacade = $facade;
			return $this;
		}

		/**
		 * Возвращает фасад валют
		 * @return iCurrencyFacade
		 */
		private function getCurrencyFacade() {
			return $this->currencyFacade;
		}
	}