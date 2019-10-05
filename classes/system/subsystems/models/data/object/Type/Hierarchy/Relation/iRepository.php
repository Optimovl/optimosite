<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy\Relation;

	use UmiCms\System\Data\Object\Type\Hierarchy\iRelation;

	/**
	 * Интерфейс репозитория иерахических связей между объектными типами данных
	 * @package UmiCms\System\Data\Object\Type\Relation
	 */
	interface iRepository {

		/**
		 * Конструктор
		 * @param \IConnection $connection
		 * @param iFactory $factory
		 */
		public function __construct(\IConnection $connection, iFactory $factory);

		/**
		 * Возвращает связи, дочерние заданному типу
		 * @param int $ancestorId идентификатор родительского типа данных
		 * @return iRelation[]
		 * @throws \databaseException
		 */
		public function getChildList($ancestorId);

		/**
		 * Возвращает связи, дочерние заданному типу в определеном домене или для всех доменов
		 * @param int $ancestorId идентификатор родительского типа данных
		 * @param int $domainId идентификатор домена или null
		 * @return iRelation[]
		 * @throws \databaseException
		 */
		public function getChildListWithDomain($ancestorId, $domainId);

		/**
		 * Возвращает список идентификаторов дочерних типов, ближайший по иерархии
		 * @param int $ancestorId идентификатор родительского типа данных
		 * @return int[]
		 * @throws \databaseException
		 */
		public function getNearestChildIdList($ancestorId);

		/**
		 * Возвращает список идентификаторов дочерних типов, ближайший по иерархии в заданном домене или для всех доменов
		 * @param int $ancestorId идентификатор родительского типа данных
		 * @param int $domainId идентификатор домена или null
		 * @return int[]
		 * @throws \databaseException
		 */
		public function getNearestChildIdListWithDomain($ancestorId, $domainId);

		/**
		 * Возвращает идентификатор ближайшего родителя
		 * @param int $childId идентификатор дочернего типа данных
		 * @return int
		 * @throws \databaseException
		 */
		public function getNearestAncestorId($childId);

		/**
		 * Возвращает связи, дочерние заданным типам
		 * @param array $idList список идентификаторов родительских типов данных
		 * @return iRelation[]
		 * @throws \databaseException
		 */
		public function getChildListByAncestorIdList(array $idList);

		/**
		 * Возвращает связи, родительские заданному типу
		 * @param int $childId идентификатор дочернего типа данных
		 * @return iRelation[]
		 * @throws \databaseException
		 */
		public function getAncestorList($childId);

		/**
		 * Создает связь между объектными типами, добавляет в дочерний тип все связи родительского.
		 * @param int $ancestorId идентификатор родительского типа или 0, если тип корневой
		 * @param int $childId идентификатор дочернего типа
		 * @return iRelation
		 * @throws \databaseException
		 */
		public function createRecursively($ancestorId, $childId);

		/**
		 * Создает связь между типами.
		 * @param int $ancestorId идентификатор родительского типа
		 * @param int $childId идентификатор дочернего типа
		 * @param int $level уровень иерархии
		 * @return iRelation
		 * @throws \databaseException
		 */
		public function create($ancestorId, $childId, $level);

		/**
		 * Удаляет все связи, в которых задействован тип данных
		 * @param int $typeId идентификатор типа данных
		 * @return bool
		 * @throws \databaseException
		 */
		public function deleteInvolving($typeId);

		/**
		 * Удаляет все связи
		 * @return $this
		 * @throws \databaseException
		 */
		public function deleteAll();
	}
