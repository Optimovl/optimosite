<?php

	namespace UmiCms\System\Response\UpdateTime;

	/**
	 * Класс вычислителя времени последнего обновления данных ответа
	 * @package UmiCms\System\Cache\Response
	 */
	class Calculator implements iCalculator {

		/** @var \iUmiHierarchy $pageCollection коллекция страниц */
		private $pageCollection;

		/** @var \iUmiObjectsCollection $objectCollection коллекция объектов */
		private $objectCollection;

		/** @inheritdoc */
		public function __construct(\iUmiHierarchy $pageCollection, \iUmiObjectsCollection $objectCollection) {
			$this->pageCollection = $pageCollection;
			$this->objectCollection = $objectCollection;
		}

		/** @inheritdoc */
		public function calculate() {
			$pageLastUpdateTime = $this->getPageCollection()
				->getElementsLastUpdateTime();
			$objectLastUpdateTime = $this->getObjectCollection()
				->getObjectsLastUpdateTime();
			return max($pageLastUpdateTime, $objectLastUpdateTime);
		}

		/**
		 * Возвращает коллекцию страниц
		 * @return \iUmiHierarchy
		 */
		private function getPageCollection() {
			return $this->pageCollection;
		}

		/**
		 * Возвращает коллекцию объектов
		 * @return \iUmiObjectsCollection
		 */
		private function getObjectCollection() {
			return $this->objectCollection;
		}
	}
