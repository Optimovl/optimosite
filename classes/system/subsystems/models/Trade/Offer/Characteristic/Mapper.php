<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use UmiCms\System\Orm\Entity\Mapper as AbstractMapper;

	/**
	 * Класс маппера характеристики торгового предложения
	 * @package UmiCms\System\Trade\Offer\Characteristic
	 */
	class Mapper extends AbstractMapper implements iMapper {

		/** @inheritdoc */
		public function getAttributeSchemaList() {
			return parent::getAttributeSchemaList() + [
				self::NAME => [
					'getName'
				],
				self::TITLE => [
					'getTitle'
				],
				self::FIELD_TYPE => [
					'getFieldType'
				],
				self::VIEW_TYPE => [
					'getViewType'
				],
				self::IS_MULTIPLE => [
					'isMultiple'
				],
				self::DATA_OBJECT_ID => [
					'getDataObjectId'
				],
				self::VALUE => [
					'getValue'
				]
			];
		}
	}