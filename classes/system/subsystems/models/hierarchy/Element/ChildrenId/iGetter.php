<?php
	namespace UmiCms\System\Hierarchy\Element\ChildrenId;

	/**
	 * Интерфейс получателя идентификаторов дочерних страниц
	 * @package UmiCms\System\Hierarchy\Element\ChildrenId
	 */
	interface iGetter {

		/** @var int DEFAULT_PART_LIMIT ограчение на размер списка по умолчанию */
		const DEFAULT_PART_LIMIT = 50;

		/**
		 * Конструктор
		 * @param \IConnection $connection подключение к базе данных
		 */
		public function __construct(\IConnection $connection);

		/**
		 * Возвращает часть списка идентификаторов дочерних страниц
		 * @param int $parentId идентификатор родительской страницы
		 * @param int $limit ограничение на размер списка
		 * @return int[]
		 */
		public function get($parentId, $limit = self::DEFAULT_PART_LIMIT);

		/**
		 * Возвращает часть списка идентификаторов дочерних удаленных страниц
		 * @param int $parentId идентификатор родительской страницы
		 * @param int $limit ограничение на размер списка
		 * @return int[]
		 */
		public function getDeleted($parentId, $limit = self::DEFAULT_PART_LIMIT);

		/**
		 * Возвращает часть списка идентификаторов дочерних активных страниц
		 * @param int $parentId идентификатор родительской страницы
		 * @param int $limit ограничение на размер списка
		 * @return int[]
		 */
		public function getActive($parentId, $limit = self::DEFAULT_PART_LIMIT);

		/**
		 * Возвращает часть списка идентификаторов дочерних неактивных страниц
		 * @param int $parentId идентификатор родительской страницы
		 * @param int $limit ограничение на размер списка
		 * @return int[]
		 */
		public function getInactive($parentId, $limit = self::DEFAULT_PART_LIMIT);
	}