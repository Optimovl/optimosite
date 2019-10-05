<?php

	namespace UmiCms\Classes\System\Entities\City;

	/**
	 * Интерфейс фабрики городов
	 * namespace UmiCms\Classes\System\Entities\City;
	 */
	interface iCitiesFactory {

		/**
		 * Создает город на основе объекта-источника данных адреса доставки
		 * @param \iUmiObject $object объект-источник данных
		 * @return iCity
		 */
		public static function createByObject(\iUmiObject $object);

		/**
		 * Создает город на основе идентификатора объекта-источника данных адреса доставки
		 * @param int $objectId идентификатор объекта-источника данных
		 * @return iCity
		 * @throws \expectObjectException
		 */
		public static function createByObjectId($objectId);

		/**
		 * Создает город на основе его названия
		 * @param string $name название города
		 * @return iCity
		 * @throws \expectObjectException
		 */
		public static function createByName($name);
	}
