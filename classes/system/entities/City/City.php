<?php

	namespace UmiCms\Classes\System\Entities\City;

	/**
	 * Класс города, замещает объект соответствующего типа
	 * @package UmiCms\Classes\System\Entities\City;
	 */
	class City extends \umiObjectProxy implements iCity {

		/** @const string CITY_TYPE_GUID гуид типа данных объекта-источника данных */
		const CITY_TYPE_GUID = 'sytem-citylist';

		/** @inheritdoc */
		public function __construct(\iUmiObject $object) {
			parent::__construct($object);
			$this->validateObjectTypeGUID(self::CITY_TYPE_GUID);
		}

		/** @inheritdoc */
		public function getName() {
			return (string) $this->getObject()
				->getName();
		}
	}
