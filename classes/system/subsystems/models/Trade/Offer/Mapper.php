<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Orm\Entity\Mapper as AbstractMapper;

	/**
	 * Класс маппера торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	class Mapper extends AbstractMapper implements iMapper {

		/** @inheritdoc */
		public function getAttributeSchemaList() {
			return parent::getAttributeSchemaList() + [
				self::TYPE_ID => [
					'getTypeId',
					'setTypeId',
					'int'
				],
				self::DATA_OBJECT_ID => [
					'getDataObjectId',
					'setDataObjectId',
					'int'
				],
				self::NAME => [
					'getName',
					'setName',
					'string'
				],
				self::VENDOR_CODE => [
					'getVendorCode',
					'setVendorCode',
					'string'
				],
				self::BAR_CODE => [
					'getBarCode',
					'setBarCode',
					'string'
				],
				self::TOTAL_COUNT => [
					'getTotalCount',
					'setTotalCount',
					'int'
				],
				self::IS_ACTIVE => [
					'isActive',
					'setActive',
					'bool'
				],
				self::ORDER => [
					'getOrder',
					'setOrder',
					'int'
				],
				self::WEIGHT => [
					'getWeight',
					'setWeight',
					'int'
				],
				self::WIDTH => [
					'getWidth',
					'setWidth',
					'int'
				],
				self::LENGTH => [
					'getLength',
					'setLength',
					'int'
				],
				self::HEIGHT => [
					'getHeight',
					'setHeight',
					'int'
				]
			];
		}

		/** @inheritdoc */
		public function getRelationSchemaList() {
			return parent::getRelationSchemaList() + [
				self::TYPE => [
					self::TYPE_ID,
					'TradeOfferDataObjectTypeFacade',
					self::ONE_ID_TO_ONE,
					'getType',
					'setType'
				],
				self::DATA_OBJECT => [
					self::DATA_OBJECT_ID,
					'TradeOfferDataObjectFacade',
					self::ONE_ID_TO_ONE,
					'getDataObject',
					'setDataObject'
				],
				self::PRICE_COLLECTION => [
					self::ID,
					'TradeOfferPriceFacade',
					self::ONE_ID_TO_COLLECTION,
					'getPriceCollection',
					'setPriceCollection'
				],
				self::STOCK_BALANCE_COLLECTION => [
					self::ID,
					'TradeStockBalanceFacade',
					self::ONE_ID_TO_COLLECTION,
					'getStockBalanceCollection',
					'setStockBalanceCollection'
				],
				self::CHARACTERISTIC_COLLECTION => [
					self::DATA_OBJECT_ID,
					'TradeOfferCharacteristicFacade',
					self::ONE_ENTITY_TO_COLLECTION,
					'getCharacteristicCollection',
					'setCharacteristicCollection'
				]
			];
		}
	}