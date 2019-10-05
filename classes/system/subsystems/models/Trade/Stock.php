<?php
	namespace UmiCms\System\Trade;

	use \iUmiObject as iDataObject;

	/**
	 * Класс склада
	 * @package UmiCms\System\Trade
	 */
	class Stock implements iStock {

		/** @var $dataObject iDataObject объект данных */
		private $dataObject;

		/** @inheritdoc */
		public function __construct(iDataObject $object) {
			$this->setDataObject($object);
		}

		/** @inheritdoc */
		public function getId() {
			return (int) $this->getDataObject()
				->getId();
		}

		/** @inheritdoc */
		public function getName() {
			return (string) $this->getDataObject()
				->getName();
		}

		/** @inheritdoc */
		public function setName($name) {
			if (!is_string($name) || isEmptyString($name)) {
				throw new \ErrorException('Incorrect stock name given');
			}

			$this->getDataObject()
				->setName($name);
			return $this;
		}

		/** @inheritdoc */
		public function isDefault() {
			return (bool) $this->getDataObject()
				->getValue('primary');
		}

		/** @inheritdoc */
		public function setDefault($flag = true) {
			if (!is_bool($flag)) {
				throw new \ErrorException('Incorrect stock default flag given');
			}

			$this->getDataObject()
				->setValue('primary', $flag);
			return $this;
		}

		/** @inheritdoc */
		public function getDataObject() {
			return $this->dataObject;
		}

		/** @inheritdoc */
		public function getBalanceViewType() {
			return 'number';
		}

		/** @inheritdoc */
		public function getBalanceTitle() {
			return getLabel('label-trade-offer-stock-balance', false, $this->getName());
		}

		/**
		 * Устанавливает объект данных
		 * @param iDataObject $object объект данных
		 * @return $this
		 */
		private function setDataObject(iDataObject $object) {
			$this->dataObject = $object;
			return $this;
		}
	}