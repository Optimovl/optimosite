<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency\Favorite;

	use UmiCms\System\Cookies\iCookieJar;
	use UmiCms\System\Trade\Offer\Price\Currency\iFavorite as iAbstractFavorite;

	/**
	 * Интерфейс любимой валюты покупателя
	 * @package UmiCms\System\Trade\Offer\Price\Currency\Favorite
	 */
	interface iCustomer extends iAbstractFavorite {

		/** @var string COOKIE_NAME имя куки для хранения валюты  */
		const COOKIE_NAME = 'customer_currency';

		/**
		 * Конструктор
		 * @param iCookieJar $cookieJar фасада кук
		 */
		public function __construct(iCookieJar $cookieJar);
	}