<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency\Favorite;

	use UmiCms\System\Trade\Offer\Price\Currency\iFavorite as iAbstractFavorite;

	/**
	 * Интерфейс фасада любимых валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency\Favorite
	 */
	interface iFacade extends iAbstractFavorite {

		/**
		 * Конструктор
		 * @param iUser $userFavoriteCurrency любимая валюта пользователя
		 * @param iCustomer $customerFavoriteCurrency любимая валюта покупателя
		 */
		public function __construct(iUser $userFavoriteCurrency, iCustomer $customerFavoriteCurrency);
	}