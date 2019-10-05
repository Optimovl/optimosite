<?php

	namespace UmiCms\Classes\System\Utils\Links\Injectors;

	use UmiCms\Classes\System\Utils\Links\iCollection;

	/**
	 * Трейт инжектора коллекции ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Injectors
	 */
	trait tLinksCollection {

		/** @var iCollection|null коллекция ссылок */
		private $linksCollection;

		/**
		 * Устанавливает коллекцию ссылок
		 * @param iCollection $collection
		 * @return $this
		 */
		public function setLinksCollection(iCollection $collection) {
			$this->linksCollection = $collection;
			return $this;
		}

		/**
		 * Возвращает коллекцию ссылок
		 * @return iCollection
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getLinksCollection() {
			if (!$this->linksCollection instanceof iCollection) {
				throw new \RequiredPropertyHasNoValueException('You should set Links\iCollection first');
			}

			return $this->linksCollection;
		}
	}
