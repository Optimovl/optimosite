<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\iCurrency;

	/**
	 * Интерфейс калькулятора валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	interface iCalculator {

		/**
		 * Пересчитывает цену из одной валюты в другую
		 * @param float $price цена
		 * @param iCurrency $from исходная валюта
		 * @param iCurrency $to целевая валюта
		 * @return float
		 */
		public function calculate($price, iCurrency $from, iCurrency $to);
	}
