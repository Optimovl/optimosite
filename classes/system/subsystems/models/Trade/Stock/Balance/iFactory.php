<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Entity\iFactory as iAbstractFactory;

	/**
	 * Интерфейс фабрики складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	interface iFactory extends iAbstractFactory {

		/**
		 * Создает складской остаток
		 * @return iBalance
		 */
		public function create();
	}