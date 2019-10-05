<?php

	namespace UmiCms\Utils\SiteMap;

	use UmiCms\System\Events\iEventPointFactory as EventFactory;

	/** Интерфейс класса обновления карты сайта */
	interface iUpdater {

		/**
		 * Конструктор
		 * @param \iConnection $connection подключение к бд
		 * @param \iUmiHierarchy $hierarchy модель иерархии
		 * @param EventFactory $eventFactory фабрика событий
		 */
		public function __construct(\IConnection $connection, \iUmiHierarchy $hierarchy, EventFactory $eventFactory);

		/**
		 * Обновляет карту, используя страницу
		 * @param \iUmiHierarchyElement $element страница
		 * @return iUpdater
		 */
		public function update(\iUmiHierarchyElement $element);

		/**
		 * Обновляет карту, используя список страниц
		 * @param \iUmiHierarchyElement[] $elementList список страниц
		 * @return iUpdater
		 */
		public function updateList(array $elementList);

		/**
		 * Удаляет из карты сайта данные страницы по заданному идентификатору
		 * @param int $elementId идентификатор страницы
		 * @return iUpdater
		 */
		public function delete($elementId);

		/**
		 * Удаляет из карты сайта данные страниц по заданному списку идентификаторов
		 * @param array $elementIdList список идентификаторов страниц
		 * @return iUpdater
		 */
		public function deleteList(array $elementIdList);

		/**
		 * Удаляет все содержимое карты сайта
		 * @return iUpdater
		 */
		public function deleteAll();

		/**
		 * Удаляет из карты сайта страницы заданного домена
		 * @param int $id идентификатор домена
		 * @return $this
		 */
		public function deleteByDomain($id);
	}
