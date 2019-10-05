<?php

	namespace UmiCms\Classes\System\Entities\Country;

	/**
	 * Класс страны, замещает объект соответствующего типа
	 * @package UmiCms\Classes\System\Entities\Country;
	 */
	class Country extends \umiObjectProxy implements iCountry {

		/** @const string COUNTRY_TYPE_GUID гуид типа данных объекта-источника данных */
		const COUNTRY_TYPE_GUID = 'd69b923df6140a16aefc89546a384e0493641fbe';

		/** @const string ISO_CODE_FIELD имя поля кода страны адреса доставки */
		const ISO_CODE_FIELD = 'country_iso_code';

		/** @inheritdoc */
		public function __construct(\iUmiObject $object) {
			parent::__construct($object);
			$this->validateObjectTypeGUID(self::COUNTRY_TYPE_GUID);
		}

		/** @inheritdoc */
		public function getName() {
			return (string) $this->getObject()
				->getName();
		}

		/** @inheritdoc */
		public function getISOCode() {
			return (string) $this->getObject()
				->getValue(self::ISO_CODE_FIELD);
		}
	}
