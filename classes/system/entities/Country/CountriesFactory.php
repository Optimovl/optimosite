<?php

	namespace UmiCms\Classes\System\Entities\Country;

	/**
	 * Фабрика стран
	 * @package UmiCms\Classes\System\Entities\Country;
	 */
	class CountriesFactory implements iCountriesFactory {

		/** @inheritdoc */
		public static function createByObject(\iUmiObject $object) {
			return new Country($object);
		}

		/** @inheritdoc */
		public static function createByObjectId($objectId) {
			$objectId = (int) $objectId;
			$object = \umiObjectsCollection::getInstance()
				->getObject($objectId);

			if (!$object instanceof \iUmiObject) {
				$exceptionMessage = sprintf(getLabel('error-cannot-get-country-by-id'), $objectId);
				throw new \expectObjectException($exceptionMessage);
			}

			return self::createByObject($object);
		}

		/** @inheritdoc */
		public static function createByISO($code) {
			$countryId = self::getCountryIdByISO($code);
			return self::createByObjectId($countryId);
		}

		/**
		 * Возвращает идентификатор страны по ee ISO коду
		 * @param string $code ISO код
		 * @return int
		 * @throws \expectObjectException
		 */
		private static function getCountryIdByISO($code) {
			$query = new \selector('objects');
			$query->types('object-type')->guid(Country::COUNTRY_TYPE_GUID);
			$query->where(Country::ISO_CODE_FIELD)->equals($code);
			$query->option('no-length', true);
			$query->option('ignore-children-types', true);
			$query->option('return', 'id');
			$query->limit(0, 1);
			$countryId = $query->result();

			if (!isset($countryId[0]['id'])) {
				$exceptionMessage = sprintf(getLabel('error-cannot-get-country-by-iso'), $code);
				throw new \expectObjectException($exceptionMessage);
			}

			return (int) $countryId[0]['id'];
		}
	}
