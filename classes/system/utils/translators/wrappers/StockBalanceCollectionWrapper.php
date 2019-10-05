<?php
	use UmiCms\System\Trade\Stock\Balance\iCollection;

	/** Класс xml транслятора (сериализатора) коллекции складских остатков */
	class StockBalanceCollectionWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iCollection $object коллекция складских остатков
		 * @throws coreException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует коллекцию складских остатков в массив с разметкой для последующей сериализации в xml
		 * @param iCollection $stockBalanceCollection коллекция складских остатков
		 * @return array
		 * @throws coreException
		 */
		protected function translateData(iCollection $stockBalanceCollection) {
			$result = [
				'@count' => $stockBalanceCollection->getCount(),
				'nodes:balance' => []
			];
			foreach ($stockBalanceCollection as $stockBalance) {
				$result['nodes:balance'][] = translatorWrapper::get($stockBalance)->translate($stockBalance);
			}

			return $result;
		}
	}