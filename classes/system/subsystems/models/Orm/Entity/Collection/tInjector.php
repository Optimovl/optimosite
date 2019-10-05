<?php
	namespace UmiCms\System\Orm\Entity\Collection;

	use UmiCms\System\Orm\Entity\iCollection;

	/**
	 * Трейт инжектора коллекции сущностей
	 * @package UmiCms\System\Orm\Entity\Collection
	 */
	trait tInjector {

		/** @var iCollection $collection коллекция сущностей */
		private $collection;

		/**
		 * Возвращает коллекцию сущностей
		 * @return iCollection
		 */
		protected function getCollection() {
			return $this->collection;
		}

		/**
		 * Устанавливает коллекцию сущностей
		 * @param iCollection $collection коллекция
		 * @return $this
		 */
		protected function setCollection(iCollection $collection) {
			$this->collection = $collection;
			return $this;
		}
	}