<?php
	use UmiCms\Service;
	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Trade\Stock\Balance\iMapper;

	/** Класс xml транслятора (сериализатора) складского остатка */
	class StockBalanceWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iBalance $object складской остаток
		 * @throws ErrorException
		 * @throws ReflectionException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует складской остаток в массив с разметкой для последующей сериализации в xml
		 * @param iBalance $balance складской остаток
		 * @return array
		 * @throws ErrorException
		 * @throws ReflectionException
		 */
		protected function translateData(iBalance $balance) {
			$result = [];

			foreach (Service::TradeStockBalanceFacade()->extractAttributeList($balance) as $attribute => $value) {
				if ($attribute === iMapper::OFFER_ID) {
					continue;
				}

				$result[sprintf('@%s', $attribute)] = $value;
			}

			return $result + [
				'@stock_name' => $balance->getStock()->getName()
			];
		}
	}