<?php
	namespace UmiCms\System\Trade\Offer\Vendor\Code;

	use UmiCms\System\Trade\iOffer;

	/**
	 * Интерфейс генератора артикула
	 * @package UmiCms\System\Trade\Offer\Vendor\Code
	 */
	interface iGenerator {

		/**
		 * Генерирует артикул для торгового предложения
		 * @param iOffer $offer торговое предложение
		 * @return string
		 */
		public function generate(iOffer $offer);
	}