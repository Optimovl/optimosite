<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use \iUmiField as iField;
	use \iUmiObjectsCollection as iObjectFacade;
	use UmiCms\System\Trade\Offer\Characteristic;

	/**
	 * Класс фабрики характеристик торговых предложений
	 * @package UmiCms\System\Trade\Offer\Characteristic
	 */
	class Factory implements iFactory {

		/** @var iObjectFacade $objectFacade фасад объектов данных */
		private $objectFacade;

		/** @inheritdoc */
		public function __construct(iObjectFacade $objectFacade) {
			$this->objectFacade = $objectFacade;
		}

		/** @inheritdoc */
		public function create(iField $field) {
			return new Characteristic($field, $this->getObjectFacade());
		}

		/** @inheritdoc */
		public function createList(array $fieldList) {
			$characteristicList = [];

			foreach ($fieldList as $field) {
				$characteristicList[] = $this->create($field);
			}

			return $characteristicList;
		}

		/**
		 * Возвращает фасад объектов
		 * @return iObjectFacade
		 */
		private function getObjectFacade() {
			return $this->objectFacade;
		}
	}