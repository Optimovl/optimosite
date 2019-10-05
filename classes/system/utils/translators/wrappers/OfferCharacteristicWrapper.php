<?php
	use UmiCms\System\Trade\Offer\iCharacteristic;

	/** Класс xml транслятора (сериализатора) характеристики торгового предложения */
	class OfferCharacteristicWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iCharacteristic $object характеристика торгового предложения
		 * @throws coreException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует характеристику торгового предложения в массив с разметкой для последующей сериализации в xml
		 * @param iCharacteristic $characteristic характеристики торгового предложения
		 * @return array
		 * @throws coreException
		 */
		protected function translateData(iCharacteristic $characteristic) {
			$property = $characteristic->getProperty();
			return translatorWrapper::get($property)->translate($property);
		}
	}