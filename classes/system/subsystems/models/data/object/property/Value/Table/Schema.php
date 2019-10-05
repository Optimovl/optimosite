<?php

	namespace UmiCms\System\Data\Object\Property\Value\Table;

	use UmiCms\System\Data\Object\Property\Value\DomainId;
	use UmiCms\System\Data\Object\Property\Value\OfferId;
	use UmiCms\System\Data\Object\Property\Value\OfferIdList;
	use UmiCms\System\Data\Object\Property\Value\DomainIdList;

	/**
	 * Класс схемы таблиц значений свойств объектов
	 * @package UmiCms\System\Data\Object\Property\Value\Table
	 */
	class Schema implements iSchema {

		/** @inheritdoc */
		public function getTable(\iUmiObjectProperty $property) {
			switch (true) {
				case ($property instanceof \umiObjectPropertyImgFile) :
				case ($property instanceof \umiObjectPropertyMultipleImgFile) : {
					return $this->getImagesTable();
				}
				case ($property instanceof \umiObjectPropertyCounter) : {
					return $this->getCounterTable();
				}
				case ($property instanceof DomainId) :
				case ($property instanceof DomainIdList) : {
					return $this->getDomainIdTable();
				}
				case ($property instanceof OfferId) :
				case ($property instanceof OfferIdList) : {
					return $this->getOfferIdTable();
				}
				default : {
					return $this->getDefaultTable();
				}
			}
		}

		/** @inheritdoc */
		public function getTableByDataType($dataType) {
			switch ($dataType) {
				case 'img_file' :
				case 'multiple_image' : {
					return $this->getImagesTable();
				}
				case 'cnt' :
				case 'counter' : {
					return $this->getCounterTable();
				}
				case 'domain_id' :
				case 'domain_id_list' : {
					return $this->getDomainIdTable();
				}
				case 'offer_id' :
				case 'offer_id_list' : {
					return $this->getOfferIdTable();
				}
				default : {
					return $this->getDefaultTable();
				}
			}
		}

		/** @inheritdoc */
		public function getTableList() {
			return [
				$this->getImagesTable(),
				$this->getCounterTable(),
				$this->getDomainIdTable(),
				$this->getOfferIdTable(),
				$this->getDefaultTable()
			];
		}

		/** @inheritdoc */
		public function getImagesTable() {
			return self::IMAGES_TABLE_NAME;
		}

		/** @inheritdoc */
		public function getCounterTable() {
			return self::COUNTER_TABLE_NAME;
		}

		/** @inheritdoc */
		public function getDomainIdTable() {
			return self::DOMAIN_ID_TABLE_NAME;
		}

		/** @inheritdoc */
		public function getOfferIdTable() {
			return self::OFFER_ID_TABLE_NAME;
		}

		/** @inheritdoc */
		public function getDefaultTable() {
			return self::DEFAULT_TABLE_NAME;
		}
	}