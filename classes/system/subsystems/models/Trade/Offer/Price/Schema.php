<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\Entity\Schema as AbstractSchema;

	/**
	 * Класс схемы хранения цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	class Schema extends AbstractSchema implements iSchema {

		/** @inheritdoc */
		protected function getRelatedContainerCustomNameList() {
			return parent::getRelatedContainerCustomNameList() + [
				iMapper::CURRENCY_ID => 'cms3_objects'
			];
		}

		/** @inheritdoc */
		protected function getRelatedExchangeCustomNameList() {
			return parent::getRelatedExchangeCustomNameList() + [
				iMapper::CURRENCY_ID => 'cms3_import_objects'
			];
		}

		/** @inheritdoc */
		protected function getNameSpaceRoot() {
			return 'UmiCms\System\Trade\\';
		}
	}