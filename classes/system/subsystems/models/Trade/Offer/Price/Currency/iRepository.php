<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\iCurrency;
	use UmiCms\System\Selector\iFactory as SelectorFactory;
	use UmiCms\System\Trade\Offer\Price\Currency\iFactory as CurrencyFactory;

	/**
	 * Интерфейс репозитория валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	interface iRepository {

		/**
		 * Конструктор
		 * @param CurrencyFactory $currencyFactory фабрика валют
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct(CurrencyFactory $currencyFactory, SelectorFactory $selectorFactory);

		/**
		 * Возвращает валюту с заданным идентификатором
		 * @param int $id идентификатор валюты
		 * @return iCurrency|null
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function get($id);

		/**
		 * Возвращает все валюты
		 * @return iCurrency[]
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function getAll();

		/**
		 * Сохраняет валюту
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function save(iCurrency $currency);
	}
