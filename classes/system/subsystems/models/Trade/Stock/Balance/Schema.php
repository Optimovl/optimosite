<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\Entity\Schema as AbstractSchema;

	/**
	 * Класс схемы хранения складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Schema extends AbstractSchema implements iSchema {

		/** @inheritdoc */
		protected function getRelatedContainerCustomNameList() {
			return parent::getRelatedContainerCustomNameList() + [
				iMapper::OFFER_ID => 'cms3_offer_list',
				iMapper::STOCK_ID => 'cms3_objects'
			];
		}

		/** @inheritdoc */
		protected function getRelatedExchangeCustomNameList() {
			return parent::getRelatedExchangeCustomNameList() + [
				iMapper::OFFER_ID => 'cms3_import_offer_list',
				iMapper::STOCK_ID => 'cms3_import_objects'
			];
		}

		/** @inheritdoc */
		protected function getNameSpaceRoot() {
			return 'UmiCms\System\Trade\\';
		}
	}