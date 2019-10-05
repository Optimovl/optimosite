<?php
	namespace UmiCms\System\Trade;

	use \iUmiObject as iObject;
	use \iUmiObjectType as iType;
	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Composite\Entity;
	use UmiCms\System\Trade\Offer\iCharacteristic;
	use UmiCms\System\Trade\Offer\Price\iCollection as iPriceCollection;
	use UmiCms\System\Trade\Stock\Balance\iCollection as iStockBalanceCollection;
	use UmiCms\System\Trade\Offer\Characteristic\iCollection as iCharacteristicCollection;

	/**
	 * Класс торгового предложения
	 * @package UmiCms\System\Trade
	 */
	class Offer extends Entity implements iOffer {

		/** @var int|null $typeId идентификатор типа данных */
		protected $typeId;

		/** @var int|null $dataObjectId идентификатор объекта данных */
		protected $dataObjectId;

		/** @var string|null $name название */
		protected $name;

		/** @var string|null $vendorCode артикул */
		protected $vendorCode;

		/** @var string|null $barCode штрихкод */
		protected $barCode;

		/** @var int $totalCount общее количество на складе */
		protected $totalCount = 0;

		/** @var bool $isActive активность */
		protected $isActive = true;

		/** @var int $order индекс сортировки */
		protected $order = null;

		/** @var int $weight вес в граммах */
		protected $weight = 0;

		/** @var int $width ширина в миллиметрах */
		protected $width = 0;

		/** @var int $length длина в миллиметрах */
		protected $length = 0;

		/** @var int $height высота в миллиметрах */
		protected $height = 0;

		/** @var iType|null $type тип данных */
		protected $type;

		/** @var iObject|null $dataObject объект данных */
		protected $dataObject;

		/** @var iPriceCollection|null $priceCollection коллекция цен */
		protected $priceCollection;

		/** @var iStockBalanceCollection|null $stockBalanceCollection коллекция складских остатков */
		protected $stockBalanceCollection;

		/** @var iCharacteristicCollection|null $characteristicCollection коллекция характеристик */
		protected $characteristicCollection;


		/** @inheritdoc */
		public function setId($id) {
			parent::setId($id);
			$this->priceCollection = null;
			$this->stockBalanceCollection = null;
			return $this;
		}

		/** @inheritdoc */
		public function getTypeId() {
			return $this->typeId;
		}

		/** @inheritdoc */
		public function setTypeId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect trade offer type id given');
			}

			$this->type = null;
			$this->characteristicCollection = null;
			return $this->setDifferentValue('typeId', $id);
		}

		/** @inheritdoc */
		public function getDataObjectId() {
			return $this->dataObjectId;
		}

		/** @inheritdoc */
		public function setDataObjectId($id) {
			if (!is_int($id) || $id <= 0) {
				throw new \ErrorException('Incorrect trade offer data object id given');
			}

			$this->dataObject = null;
			$this->characteristicCollection = null;
			return $this->setDifferentValue('dataObjectId', $id);
		}

		/** @inheritdoc */
		public function hasDataObjectId() {
			return $this->getDataObjectId() !== null;
		}

		/** @inheritdoc */
		public function getVendorCode() {
			return $this->vendorCode;
		}

		/** @inheritdoc */
		public function setVendorCode($code) {
			if (!is_string($code) || isEmptyString($code)) {
				throw new \ErrorException('Incorrect trade offer vendor code given');
			}

			return $this->setDifferentValue('vendorCode', $code);
		}

		/** @inheritdoc */
		public function hasVendorCode() {
			return $this->getVendorCode() !== null;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			if (!is_string($name) || isEmptyString($name)) {
				throw new \ErrorException('Incorrect trade offer name given');
			}

			return $this->setDifferentValue('name', $name);
		}

		/** @inheritdoc */
		public function getBarCode() {
			return $this->barCode;
		}

		/** @inheritdoc */
		public function setBarCode($code) {
			if (!is_string($code) && $code !== null) {
				throw new \ErrorException('Incorrect trade offer bar code given');
			}

			return $this->setDifferentValue('barCode', $code);
		}

		/** @inheritdoc */
		public function getTotalCount() {
			return $this->totalCount;
		}

		/** @inheritdoc */
		public function setTotalCount($count) {
			if (!is_int($count) || $count < 0) {
				throw new \ErrorException('Incorrect trade offer total count given');
			}

			return $this->setDifferentValue('totalCount', $count);
		}

		/** @inheritdoc */
		public function isActive() {
			return $this->isActive;
		}

		/** @inheritdoc */
		public function setActive($flag = true) {
			if (!is_bool($flag)) {
				throw new \ErrorException('Incorrect trade offer activity status given');
			}

			return $this->setDifferentValue('isActive', $flag);
		}

		/** @inheritdoc */
		public function getOrder() {
			return $this->order;
		}

		/** @inheritdoc */
		public function setOrder($index) {
			if (!is_int($index) || $index < 0) {
				throw new \ErrorException('Incorrect trade offer order index given');
			}

			return $this->setDifferentValue('order', $index);
		}

		/** @inheritdoc */
		public function hasOrder() {
			return $this->getOrder() !== null;
		}

		/** @inheritdoc */
		public function getWeight() {
			return $this->weight;
		}

		/** @inheritdoc */
		public function setWeight($weight) {
			if (!is_int($weight) || $weight < 0) {
				throw new \ErrorException('Incorrect trade offer weight given');
			}

			return $this->setDifferentValue('weight', $weight);
		}

		/** @inheritdoc */
		public function getWidth() {
			return $this->width;
		}

		/** @inheritdoc */
		public function setWidth($width) {
			if (!is_int($width) || $width < 0) {
				throw new \ErrorException('Incorrect trade offer width given');
			}

			return $this->setDifferentValue('width', $width);
		}

		/** @inheritdoc */
		public function getLength() {
			return $this->length;
		}

		/** @inheritdoc */
		public function setLength($length) {
			if (!is_int($length) || $length < 0) {
				throw new \ErrorException('Incorrect trade offer length given');
			}

			return $this->setDifferentValue('length', $length);
		}

		/** @inheritdoc */
		public function getHeight() {
			return $this->height;
		}

		/** @inheritdoc */
		public function setHeight($height) {
			if (!is_int($height) || $height < 0) {
				throw new \ErrorException('Incorrect trade offer height given');
			}

			return $this->setDifferentValue('height', $height);
		}

		/** @inheritdoc */
		public function getType() {
			if ($this->type === null) {
				$this->loadRelation(Offer\iMapper::TYPE);
			}

			return $this->type;
		}

		/** @inheritdoc */
		public function setType(iType $type) {
			return $this->setTypeId($type->getId())
				->setDifferentValue('type', $type);
		}

		/** @inheritdoc */
		public function getDataObject() {
			if ($this->dataObject === null) {
				$this->loadRelation(Offer\iMapper::DATA_OBJECT);
			}

			return $this->dataObject;
		}

		/** @inheritdoc */
		public function setDataObject(iObject $object) {
			return $this->setDataObjectId($object->getId())
				->setDifferentValue('dataObject', $object);
		}

		/** @inheritdoc */
		public function getPriceCollection() {
			if ($this->priceCollection === null) {
				$this->loadRelation(Offer\iMapper::PRICE_COLLECTION);
			}

			return $this->priceCollection;
		}

		/** @inheritdoc */
		public function setPriceCollection(iPriceCollection $collection) {
			$id = $this->getId();

			/** @var iPrice $price */
			foreach ($collection as $price) {
				$price->setOfferId($id);
			}

			return $this->setDifferentValue('priceCollection', $collection);
		}

		/** @inheritdoc */
		public function getStockBalanceCollection() {
			if ($this->stockBalanceCollection === null) {
				$this->loadRelation(Offer\iMapper::STOCK_BALANCE_COLLECTION);
			}

			return $this->stockBalanceCollection;
		}

		/** @inheritdoc */
		public function setStockBalanceCollection(iStockBalanceCollection $collection) {
			$id = $this->getId();

			/** @var iBalance $balance */
			foreach ($collection as $balance) {
				$balance->setOfferId($id);
			}

			return $this->setDifferentValue('stockBalanceCollection', $collection);
		}

		/** @inheritdoc */
		public function getCharacteristicCollection() {
			if ($this->characteristicCollection === null) {
				$this->loadRelation(Offer\iMapper::CHARACTERISTIC_COLLECTION);
			}

			return $this->characteristicCollection;
		}

		/** @inheritdoc */
		public function setCharacteristicCollection(iCharacteristicCollection $collection) {
			$dataObject = $this->getDataObject();

			/** @var iCharacteristic $characteristic */
			foreach ($collection as $characteristic) {
				$characteristic->setDataObject($dataObject);
			}

			return $this->setDifferentValue('characteristicCollection', $collection);
		}
	}