<?php

	namespace UmiCms\Classes\System\Entities\City;

	/**
	 * Фабрика городов
	 * namespace UmiCms\Classes\System\Entities\City;
	 */
	class CitiesFactory implements iCitiesFactory {

		/** @inheritdoc */
		public static function createByObject(\iUmiObject $object) {
			return new City($object);
		}

		/** @inheritdoc */
		public static function createByObjectId($objectId) {
			$objectId = (int) $objectId;
			$object = \umiObjectsCollection::getInstance()
				->getObject($objectId);

			if (!$object instanceof \iUmiObject) {
				$exceptionMessage = sprintf(getLabel('error-cannot-get-city-by-id'), $objectId);
				throw new \expectObjectException($exceptionMessage);
			}

			return self::createByObject($object);
		}

		/** @inheritdoc */
		public static function createByName($name) {
			$cityId = self::getCityIdByName($name);
			return self::createByObjectId($cityId);
		}

		/**
		 * Возвращает идентификатор города по его названию
		 * @param string $name название города
		 * @return int
		 * @throws \expectObjectException
		 */
		private static function getCityIdByName($name) {
			$query = new \selector('objects');
			$query->types('object-type')->guid(City::CITY_TYPE_GUID);
			$query->where('name')->equals($name);
			$query->option('no-length', true);
			$query->option('ignore-children-types', true);
			$query->option('return', 'id');
			$query->limit(0, 1);
			$cityId = $query->result();

			if (!isset($cityId[0]['id'])) {
				$exceptionMessage = sprintf(getLabel('error-cannot-get-city-by-name'), $name);
				throw new \expectObjectException($exceptionMessage);
			}

			return (int) $cityId[0]['id'];
		}
	}
