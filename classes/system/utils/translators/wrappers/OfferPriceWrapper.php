<?php
	use UmiCms\Service;
	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Trade\Offer\Price\iMapper;

	/** Класс xml транслятора (сериализатора) цены торгового предложения */
	class OfferPriceWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iPrice $object цена торгового предложения
		 * @throws \Exception
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \privateException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует цену торгового предложения в массив с разметкой для последующей сериализации в xml
		 * @param iPrice $price цена торгового предложения
		 * @return array
		 * @throws \Exception
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \privateException
		 */
		protected function translateData(iPrice $price) {
			$result = [];

			foreach (Service::TradeOfferPriceFacade()->extractAttributeList($price) as $attribute => $value) {

				if ($attribute === iMapper::CURRENCY_ID) {
					continue;
				}

				$result[sprintf('@%s', $attribute)] = $value;
			}

			$currencyFacade = Service::CurrencyFacade();
			$currencyCurrency = $currencyFacade->getCurrent();
			$calculatedPrice = $currencyFacade->calculate($price->getValue(), $price->getCurrency(), $currencyCurrency);
			return $result + [
				'@value' => $calculatedPrice,
				'@formatted_value' => sprintf('%.2f %s', $calculatedPrice, $currencyCurrency->getSuffix()),
				'@type_title' => $price->getType()->getTitle()
			];
		}
	}