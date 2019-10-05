<?php

	namespace UmiCms\Classes\System\Utils\Links\Injectors;

	use UmiCms\Classes\System\Utils\Links\iCollection;

	/**
	 * Интерфейс инжектора коллекции ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Injectors
	 */
	interface iLinksCollection {

		/**
		 * Устанавливает коллекцию ссылок
		 * @param iCollection $collection
		 * @return $this
		 */
		public function setLinksCollection(iCollection $collection);

		/**
		 * Возвращает коллекцию ссылок
		 * @return iCollection
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getLinksCollection();
	}
