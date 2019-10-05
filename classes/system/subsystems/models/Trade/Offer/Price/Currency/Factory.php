<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\Currency;
	use UmiCms\System\Trade\Offer\Price\iCurrency;

	/**
	 * Класс фабрики валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create(\iUmiObject $dataObject) {
			$this->validate($dataObject);
			return new Currency($dataObject);
		}

		/**
		 * Валидирует объект данных валюты
		 * @param \iUmiObject $dataObject объект данных валюты
		 * @throws \wrongParamException
		 */
		private function validate(\iUmiObject $dataObject) {
			if ($dataObject->getTypeGUID() !== iCurrency::TYPE_GUID) {
				$message = sprintf('Data object for currency must have type "%s"', iCurrency::TYPE_GUID);
				throw new \wrongParamException($message);
			}
		}
	}
