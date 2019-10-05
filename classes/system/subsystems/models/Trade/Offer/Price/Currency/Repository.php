<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\Currency;
	use UmiCms\System\Trade\Offer\Price\iCurrency;
	use UmiCms\System\Selector\iFactory as SelectorFactory;
	use UmiCms\System\Trade\Offer\Price\Currency\iFactory as CurrencyFactory;

	/**
	 * Класс репозитория валют
	 * @todo: Реализовать создание и удаление валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	class Repository implements iRepository {

		/** @var CurrencyFactory $currencyFactory фабрика валют */
		private $currencyFactory;

		/** @var SelectorFactory $selectorFactory фабрика селекторов */
		private $selectorFactory;

		/** @inheritdoc */
		public function __construct(CurrencyFactory $currencyFactory, SelectorFactory $selectorFactory) {
			$this->currencyFactory = $currencyFactory;
			$this->selectorFactory = $selectorFactory;
		}

		/** @inheritdoc */
		public function get($id) {
			if (!is_numeric($id) || $id <= 0) {
				return null;
			}

			$selector = $this->getSelector();
			$selector->where('id')->equals($id);
			$selector->limit(0, 1);

			$dataObjectList = $selector->result();

			if (!isset($dataObjectList[0]) || !$dataObjectList[0] instanceof \iUmiObject) {
				return null;
			}

			$dataObject = array_shift($dataObjectList);

			return $this->getCurrencyFactory()
				->create($dataObject);
		}

		/** @inheritdoc */
		public function getAll() {
			$selector = $this->getSelector();
			$currencyList = [];
			$currencyFactory = $this->getCurrencyFactory();

			foreach ($selector->result() as $dataObject) {
				$currencyList[] = $currencyFactory->create($dataObject);
			}

			return $currencyList;
		}

		/** @inheritdoc */
		public function save(iCurrency $currency) {
			/** @var Currency $currency */
			$currency->getDataObject()
				->commit();
			return $this;
		}

		/**
		 * Возвращает фабрику валют
		 * @return CurrencyFactory
		 */
		private function getCurrencyFactory() {
			return $this->currencyFactory;
		}

		/**
		 * Возвращает фабрику селекторов
		 * @return SelectorFactory
		 */
		private function getSelectorFactory() {
			return $this->selectorFactory;
		}

		/**
		 * Возвращает селектор
		 * @return \selector
		 * @throws \selectorException
		 */
		private function getSelector() {
			$selector = $this->getSelectorFactory()
				->createObjectTypeGuid(iCurrency::TYPE_GUID);
			$selector->option('ignore-children-types', true);
			$selector->option('no-length', true);
			$selector->option('load-all-props', true);
			return $selector;
		}
	}
