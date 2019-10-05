<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\Entity\Mapper as AbstractMapper;

	/**
	 * Класс маппера цен
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	class Mapper extends AbstractMapper implements iMapper {

		/** @inheritdoc */
		public function getAttributeSchemaList() {
			return parent::getAttributeSchemaList() + [
				self::CURRENCY_ID => [
					'getCurrencyId',
					'setCurrencyId',
					'int'
				],
				self::VALUE => [
					'getValue',
					'setValue',
					'float'
				],
				self::OFFER_ID => [
					'getOfferId',
					'setOfferId',
					'int'
				],
				self::TYPE_ID => [
					'getTypeId',
					'setTypeId',
					'int'
				],
				self::IS_MAIN => [
					'isMain',
					'setMain',
					'bool'
				],
			];
		}

		/** @inheritdoc */
		public function getRelationSchemaList() {
			return parent::getRelationSchemaList() + [
				self::OFFER => [
					self::OFFER_ID,
					'TradeOfferFacade',
					self::ONE_ID_TO_ONE,
					'getOffer',
					'setOffer'
				],
				self::CURRENCY => [
					self::CURRENCY_ID,
					'Currencies',
					self::ONE_ID_TO_ONE,
					'getCurrency',
					'setCurrency'
				],
				self::TYPE => [
					self::TYPE_ID,
					'TradeOfferPriceTypeFacade',
					self::ONE_ID_TO_ONE,
					'getType',
					'setType'
				]
			];
		}
	}