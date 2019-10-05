<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\Entity\Mapper as AbstractMapper;

	/**
	 * Класс маппера типов цен
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	class Mapper extends AbstractMapper implements iMapper {

		/** @inheritdoc */
		public function getAttributeSchemaList() {
			return parent::getAttributeSchemaList() + [
				self::NAME => [
					'getName',
					'setName',
					'string'
				],
				self::TITLE => [
					'getTitle',
					'setTitle',
					'string'
				],
				self::IS_DEFAULT => [
					'isDefault',
					'setDefault',
					'boolean'
				]
			];
		}
	}