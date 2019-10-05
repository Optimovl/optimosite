<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy\Relation;

	/**
	 * Интерфейс класса миграции связей иерархии объектных типов данных
	 * @package UmiCms\System\Data\Object\Type\Hierarchy\Relation
	 */
	interface iMigration {

		/**
		 * Конструктор
		 * @param \IConnection $connection подключение к бд
		 * @param iRepository $repository репозиторий иерархических связей типов данных
		 */
		public function __construct(\IConnection $connection, iRepository $repository);

		/**
		 * Мигрирует иерархическую связь типа
		 * @param int $typeId идентификатор типа
		 * @return $this
		 */
		public function migrate($typeId);
	}
