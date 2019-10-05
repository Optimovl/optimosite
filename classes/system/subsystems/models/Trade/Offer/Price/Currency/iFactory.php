<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\iCurrency;

	/**
	 * Интерфейс фабрики валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	interface iFactory {

		/**
		 * Создает валюту
		 * @param \iUmiObject $dataObject объект данных валюты
		 * @return iCurrency
		 * @throws \wrongParamException
		 */
		public function create(\iUmiObject $dataObject);
	}
