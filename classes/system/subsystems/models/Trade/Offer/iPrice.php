<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Composite\iEntity;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Trade\Offer\Price\iCurrency;
	use UmiCms\System\Trade\Offer\Price\Currency\iFacade as iCurrencyFacade;

	/**
	 * Интерфейс цены торгового предложения
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iPrice extends iEntity {

		/**
		 * Возвращает значение цены в валюте
		 * @param iCurrency|null $currency необходимая валюта
		 * @return float
		 * @throws \coreException
		 * @throws \privateException
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function getValue(iCurrency $currency = null);

		/**
		 * Устанавливает значение цены
		 * @param float $value значение
		 * @param iCurrency|null $currency валюта значения
		 * @return $this
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \privateException
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		public function setValue($value, iCurrency $currency = null);

		/**
		 * Возвращает идентификатор предложения
		 * @return int|null
		 */
		public function getOfferId();

		/**
		 * Устанавливает идентификатор предложения
		 * @param int $id идентификатор предложения
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setOfferId($id);

		/**
		 * Возвращает идентификатор валюты
		 * @return int|null
		 */
		public function getCurrencyId();

		/**
		 * Устанавливает идентификатор валюты
		 * @param int $id идентификатор валюты
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setCurrencyId($id);

		/**
		 * Возвращает идентификатор типа
		 * @return int|null
		 */
		public function getTypeId();

		/**
		 * Устанавливает идентификатор типа
		 * @param int $id идентификатор типа
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setTypeId($id);

		/**
		 * Определяет является ли цена основной
		 * @return bool
		 */
		public function isMain();

		/**
		 * Устанавливает, что цена является основной
		 * @param bool $flag значение флага
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setMain($flag = true);

		/**
		 * Возвращает торговое предложение
		 * @return iOffer|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getOffer();

		/**
		 * Устанавливает торговое предложение
		 * @param iOffer $offer торговое предложение
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setOffer(iOffer $offer);

		/**
		 * Возвращает валюту
		 * @return iCurrency|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getCurrency();

		/**
		 * Устанавливает валюту
		 * @param iCurrency $currency валюта
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setCurrency(iCurrency $currency);

		/**
		 * Возвращает тип
		 * @return iType|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getType();

		/**
		 * Устанавливает тип
		 * @param iType $type тип
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setType(iType $type);

		/**
		 * Устанавливает фасад валют
		 * @param iCurrencyFacade $facade фасад валют
		 * @return $this
		 */
		public function setCurrencyFacade(iCurrencyFacade $facade);
	}