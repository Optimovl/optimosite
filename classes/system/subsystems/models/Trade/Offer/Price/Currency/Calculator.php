<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\iCurrency;

	/**
	 * Класс валютного калькулятора цен
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	class Calculator implements iCalculator {

		/** @inheritdoc */
		public function calculate($price, iCurrency $from, iCurrency $to) {
			if ($from->getId() === $to->getId()) {
				return $price;
			}

			$denominationFrom = $from->getDenomination() ?: 1;
			$rateFrom = $from->getRate() ?: 1;
			$price = $price * $denominationFrom * $rateFrom;

			$denominationTo = $to->getDenomination() ?: 1;
			$rateTo = $to->getRate() ?: 1;
			$price = $price / $rateTo / $denominationTo;
			return round($price, 2);
		}
	}
