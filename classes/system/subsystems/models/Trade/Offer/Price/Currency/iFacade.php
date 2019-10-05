<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\iCurrency;
	use UmiCms\System\Trade\Offer\Price\Currency\Favorite\iFacade as iFavoriteCurrencyFacade;

	/**
	 * Интерфейс фасада валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iRepository $repository репозиторий валют
		 * @param iCollection $collection коллекция валют
		 * @param \iConfiguration $configuration конфигурация системы
		 * @param iCalculator $calculator калькулятор валют
		 * @param iFavoriteCurrencyFacade $favoriteCurrencyFacade фасад любимых валют
		 */
		public function __construct(
			iRepository $repository,
			iCollection $collection,
			\iConfiguration $configuration,
			iCalculator $calculator,
			iFavoriteCurrencyFacade $favoriteCurrencyFacade
		);

		/**
		 * Возвращает список валют
		 * @return iCurrency[]
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function getList();

		/**
		 * Возвращает валюту магазина по умолчанию
		 * @return iCurrency
		 * @throws \coreException
		 * @throws \privateException
		 */
		public function getDefault();

		/**
		 * Устанавливает валюту магазина по умолчанию
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function setDefault(iCurrency $currency);

		/**
		 * Определяет является ли валюта валютой по умолчанию
		 * @param iCurrency $currency проверяемая валюта
		 * @return bool
		 * @throws \coreException
		 * @throws \privateException
		 */
		public function isDefault(iCurrency $currency);

		/**
		 * Возвращает текущую валюту покупателя
		 * @return iCurrency
		 * @throws \Exception
		 * @throws \coreException
		 * @throws \privateException
		 */
		public function getCurrent();

		/**
		 * Устанавливает текущую выбранную валюту
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function setCurrent(iCurrency $currency);

		/**
		 * Определяет является ли валюта валютой выбранной пользователем
		 * @param iCurrency $currency проверяемая валюта
		 * @return bool
		 * @throws \coreException
		 * @throws \privateException
		 * @throws \Exception
		 */
		public function isCurrent(iCurrency $currency);

		/**
		 * Возвращает валюту с заданным кодом (ОКВ)
		 * @param string $code код валюты
		 * @return iCurrency
		 * @throws \privateException
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function getByCode($code);

		/**
		 * Возвращает валюту с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iCurrency
		 * @throws \privateException
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function get($id);

		/**
		 * Сохраняет изменения валюты
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function save(iCurrency $currency);

		/**
		 * Пересчитывает цену из одной валюты в другую
		 * @param float $price цена
		 * @param iCurrency|null $from исходная валюта (если не передана - возьмет валюту магазина по умолчанию)
		 * @param iCurrency|null $to целевая валюта (если не передана возьмет текущую валюту покупателя)
		 * @return float
		 * @throws \Exception
		 * @throws \coreException
		 * @throws \privateException
		 */
		public function calculate($price, iCurrency $from = null, iCurrency $to = null);

		/**
		 * Перезагружает коллекцию валют
		 * @return $this
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function reload();
	}

