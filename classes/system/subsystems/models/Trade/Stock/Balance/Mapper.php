<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\Entity\Mapper as AbstractMapper;

	/**
	 * Класс маппера складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Mapper extends AbstractMapper implements iMapper {


		/** @inheritdoc */
		public function getAttributeSchemaList() {
			return parent::getAttributeSchemaList() + [
				self::OFFER_ID => [
					'getOfferId',
					'setOfferId',
					'int'
				],
				self::STOCK_ID => [
					'getStockId',
					'setStockId',
					'int'
				],
				self::VALUE => [
					'getValue',
					'setValue',
					'int'
				]
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
				self::STOCK => [
					self::STOCK_ID,
					'TradeStockFacade',
					self::ONE_ID_TO_ONE,
					'getStock',
					'setStock'
				]
			];
		}
	}