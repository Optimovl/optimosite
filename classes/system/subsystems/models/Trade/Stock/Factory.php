<?php
	namespace UmiCms\System\Trade\Stock;

	use UmiCms\System\Trade\Stock;
	use \iUmiObject as iDataObject;

	/**
	 * Класс фабрики складов
	 * @package UmiCms\System\Trade\Stock
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create(iDataObject $dataObject) {
			$this->validateDataObject($dataObject);
			return new Stock($dataObject->setSavingInDestructor(false));
		}

		/**
		 * Валидирует объект данных
		 * @param iDataObject $dataObject объект данных
		 * @return $this
		 * @throws \ErrorException
		 */
		private function validateDataObject(iDataObject $dataObject) {
			if ($dataObject->getTypeGUID() !== self::TYPE_GUID) {
				throw new \ErrorException('Incorrect type of stock data object');
			}

			return $this;
		}
	}