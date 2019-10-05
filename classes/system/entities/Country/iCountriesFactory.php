<?php

	namespace UmiCms\Classes\System\Entities\Country;

	/**
	 * Интерфейс фабрики стран
	 * @package UmiCms\Classes\System\Entities\Country;
	 */
	interface iCountriesFactory {

		/**
		 * Создает страну на основе объекта-источника данных адреса доставки
		 * @param \iUmiObject $object объект-источник данных
		 * @return iCountry
		 */
		public static function createByObject(\iUmiObject $object);

		/**
		 * Создает страну на основе идентификатора объекта-источника данных адреса доставки
		 * @param int $objectId идентификатор объекта-источника данных
		 * @return iCountry
		 * @throws \expectObjectException
		 */
		public static function createByObjectId($objectId);

		/**
		 * Создает страну на основе ее ISO кода
		 * @param string $code ISO код
		 * @return Country|iCountry
		 */
		public static function createByISO($code);
	}
