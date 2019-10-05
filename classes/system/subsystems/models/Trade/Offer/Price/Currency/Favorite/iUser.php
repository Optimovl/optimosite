<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency\Favorite;

	use UmiCms\System\Auth\iAuth;
	use \iUmiObjectsCollection as iObjectCollection;
	use UmiCms\System\Trade\Offer\Price\Currency\iFavorite as iAbstractFavorite;

	/**
	 * Интерфейс любимой валюты пользователя
	 * @package UmiCms\System\Trade\Offer\Price\Currency\Favorite
	 */
	interface iUser extends iAbstractFavorite {

		/** @var string FIELD_NAME имя поля для хранения валюты  */
		const FIELD_NAME = 'preffered_currency';

		/**
		 * Конструктор
		 * @param iAuth $auth фасад авторизации и аутентификации
		 * @param iObjectCollection $objectsCollection коллекция объектов
		 */
		public function __construct(iAuth $auth, iObjectCollection $objectsCollection);
	}