<?php
	use UmiCms\Service;
	use UmiCms\System\Trade\iOffer;

	/** Класс xml транслятора (сериализатора) торгового предложения */
	class OfferWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iOffer $object торговое предложение
		 * @throws \coreException
		 * @throws \ErrorException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует торговое предложение в массив с разметкой для последующей сериализации в xml
		 * @param iOffer $offer торговое предложение
		 * @return array
		 * @throws \coreException
		 * @throws \ErrorException
		 */
		protected function translateData(iOffer $offer) {
			$result = [];

			foreach (Service::TradeOfferFacade()->extractPropertyList($offer) as $name => $value) {

				if (!is_object($value)) {
					$result[sprintf('@%s', $name)] = $value;
					continue;
				}

				$result[$name] = translatorWrapper::get($value)->translate($value);
			}

			return $result;
		}
	}