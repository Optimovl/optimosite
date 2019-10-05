<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Orm\Entity\iFactory as iAbstractFactory;

	/**
	 * Интерфейс фабрики типов цен торгового предложения
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	interface iFactory extends iAbstractFactory {

		/**
		 * Создает тип цены торгового предложения
		 * @return iType
		 */
		public function create();
	}