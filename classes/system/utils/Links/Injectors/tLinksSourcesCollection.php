<?php

	namespace UmiCms\Classes\System\Utils\Links\Injectors;

	use UmiCms\Classes\System\Utils\Links\iSourcesCollection;

	/**
	 * Трейт инжектора коллекции источников ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Injectors
	 */
	trait tLinksSourcesCollection {

		/** @var iSourcesCollection|null коллекция источников ссылок */
		private $linksSourcesCollection;

		/**
		 * Устанавливает коллекцию источников ссылок
		 * @param iSourcesCollection $collection
		 * @return $this
		 */
		public function setLinksSourcesCollection(iSourcesCollection $collection) {
			$this->linksSourcesCollection = $collection;
			return $this;
		}

		/**
		 * Возвращает коллекцию источников ссылок
		 * @return iSourcesCollection
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getLinksSourcesCollection() {
			if (!$this->linksSourcesCollection instanceof iSourcesCollection) {
				throw new \RequiredPropertyHasNoValueException('You should set Links\iSourcesCollection first');
			}

			return $this->linksSourcesCollection;
		}
	}
