<?php
	use UmiCms\System\Trade\Offer\Characteristic\iCollection;

	/** Класс xml транслятора (сериализатора) коллекции характеристик торгового предложения */
	class OfferCharacteristicCollectionWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iCollection $object коллекция характеристик торгового предложения
		 * @throws coreException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует коллекцию характеристик торгового предложения в массив с разметкой для последующей сериализации в xml
		 * @param iCollection $characteristicCollection коллекция характеристик торгового предложения
		 * @return array
		 * @throws coreException
		 */
		protected function translateData(iCollection $characteristicCollection) {
			$result = [
				'@count' => $characteristicCollection->getCount(),
				'nodes:characteristic' => []
			];

			foreach ($characteristicCollection as $characteristic) {
				$result['nodes:characteristic'][] = translatorWrapper::get($characteristic)->translate($characteristic);
			}

			return $result;
		}
	}