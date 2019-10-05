<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use \iUmiField as iField;
	use \iUmiObjectsCollection as iObjectFacade;
	use UmiCms\System\Trade\Offer\iCharacteristic;

	/**
	 * Интерфейс фабрики характеристик торговых предложений
	 * @package UmiCms\System\Trade\Offer\Characteristic
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param iObjectFacade $objectFacade фасад объектов
		 */
		public function __construct(iObjectFacade $objectFacade);

		/**
		 * Создает характеристику торгового предложения
		 * @param iField $field поле
		 * @return iCharacteristic
		 */
		public function create(iField $field);

		/**
		 * Создает список характеристик торгового предложения
		 * @param iField[] $fieldList список полей
		 * @return iCharacteristic[]
		 */
		public function createList(array $fieldList);
	}