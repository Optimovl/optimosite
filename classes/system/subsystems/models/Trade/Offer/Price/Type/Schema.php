<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\Entity\Schema as AbstractSchema;

	/**
	 * Класс схемы хранения типов цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	class Schema extends AbstractSchema implements iSchema {

		/** @inheritdoc */
		protected function getNameSpaceRoot() {
			return 'UmiCms\System\Trade\\';
		}
	}