<?php
	namespace UmiCms\System\Import\UmiDump\Helper\Entity;

	use \iUmiImportRelations as iBaseSourceIdBinder;

	/**
	 * Интерфейс класса управления связями между идентификаторами импортированных/импортируемых системных сущностей
	 * и их идентификаторами из внешних источников.
	 *
	 * Для каждой импортируемой сущности сохраняется ее оригинальный идентификатор, чтобы при повторном импорте
	 * данных из заданного внешнего источника сущности были обновлены, а не созданы вновь.
	 */
	interface iSourceIdBinder {

		/**
		 * Конструктор
		 * @param iBaseSourceIdBinder $baseSourceIdBinder базовый класс управления связями сущностей при обмене данными
		 * @param \IConnection $connection подключение к бд
		 */
		public function __construct(iBaseSourceIdBinder $baseSourceIdBinder, \IConnection $connection);

		/**
		 * Устанавливает идентификатор ресурса
		 * @param int $id идентификатор ресурса
		 * @return $this
		 * @throws \InvalidArgumentException
		 */
		public function setSourceId($id);

		/**
		 * Устанавливает идентификатор ресурса по его имени
		 * @param string $name имя ресурса
		 * @return $this
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function setSourceIdByName($name);

		/**
		 * Возвращает идентификатор ресурса
		 * @return int
		 */
		public function getSourceId();

		/**
		 * Устанавливает связь между импортируемой сущностью и уже созданной в системе сущностью
		 * @param string $externalId Идентификатор импортируемой сущности
		 * @param int $internalId Идентификатор созданной сущности
		 * @param string $table имя таблицы со связями импорта
		 * @return $this
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function defineRelation($externalId, $internalId, $table);

		/**
		 * Устанавливает список связей импортируемых сущностей с уже созданными
		 * @param array $relationList список связей [external_id => internal_id]
		 * @param string $table имя таблицы со связями импорта
		 * @return $this
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function defineRelationList(array $relationList, $table);

		/**
		 * Возвращает идентификатор созданной сущности
		 * @param string $externalId Идентификатор импортируемой сущности
		 * @param string $table имя таблицы со связями импорта
		 * @return int|null
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function getInternalId($externalId, $table);

		/**
		 * Возвращает идентификатор импортируемой сущности
		 * @param int $internalId Идентификатор созданной сущности
		 * @param string $table имя таблицы со связями импорта
		 * @return string|null
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function getExternalId($internalId, $table);

		/**
		 * Возвращает список внешних идентификаторов сущностей
		 * @param int[] $internalIdList список внутренних идентификаторов сущностей
		 * @param string $table имя таблицы со связями импорта
		 * @return string[]
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function getExternalIdList(array $internalIdList, $table);

		/**
		 * Возвращает список внутренних идентификаторов сущностей
		 * @param string[] $externalIdList список внешних идентификаторов сущностей
		 * @param string $table имя таблицы со связями импорта
		 * @return int[]
		 * @throws \databaseException
		 */
		public function getInternalIdList(array $externalIdList, $table);

		/**
		 * Определяет связана ли импортированная сущность с другими внешними источниками,
		 * то есть обновлялась или создавалась ли она в рамках работы с другими источниками.
		 * @param int $internalId Идентификатор созданной сущности
		 * @param string $table имя таблицы со связями импорта
		 * @return bool
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		public function isRelatedToAnotherSource($internalId, $table);
	}
