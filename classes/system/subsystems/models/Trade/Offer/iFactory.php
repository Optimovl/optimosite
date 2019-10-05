<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Entity\iFactory as iAbstractFactory;

	/**
	 * Интерфейс фабрики торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iFactory extends iAbstractFactory {

		/**
		 * Создает торговое предложение
		 * @return iOffer
		 * @throws \Exception
		 */
		public function create();
	}