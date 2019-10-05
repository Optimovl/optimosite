<?php
	use UmiCms\System\Trade\Offer\Price\iCollection;

	/** Класс xml транслятора (сериализатора) коллекции цен торгового предложения */
	class OfferPriceCollectionWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iCollection $object коллекции цен торгового предложения
		 * @throws coreException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует коллекцию цен торгового предложения в массив с разметкой для последующей сериализации в xml
		 * @param iCollection $priceCollection коллекция цен торгового предложения
		 * @return array
		 * @throws coreException
		 */
		protected function translateData(iCollection $priceCollection) {
			$result = [
				'@count' => $priceCollection->getCount(),
				'nodes:price' => []
			];

			foreach ($priceCollection as $price) {
				$result['nodes:price'][] = translatorWrapper::get($price)->translate($price);
			}

			return $result;
		}
	}