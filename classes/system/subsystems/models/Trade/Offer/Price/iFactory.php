<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Orm\Entity\iFactory as iAbstractFactory;

	/**
	 * Интерфейс фабрики цен торгового предложения
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	interface iFactory extends iAbstractFactory {

		/**
		 * Создает цену торгового предложения
		 * @return iPrice
		 */
		public function create();
	}