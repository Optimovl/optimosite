<?php

	/** Коллекция для работы с типами данных (iUmiObjectType) */
	interface iUmiObjectTypesCollection {

		/**
		 * Возвращает тип данных по его идентификатору или false в случае ошибки
		 * @param int|string $id id или guid типа данных
		 * @return iUmiObjectType|bool
		 * @throws coreException Если не удалось загрузить тип
		 */
		public function getType($id);

		/**
		 * Возвращает список типов данных
		 * @param int[] $idList список идентификаторов типов
		 * @return iUmiObjectType[]
		 * @throws databaseException
		 */
		public function getTypeList(array $idList);

		/**
		 * Возвращает тип данных по его GUID или false в случае ошибки
		 * @param string $guid Global Umi Identifier
		 * @return iUmiObjectType|bool
		 */
		public function getTypeByGUID($guid);

		/**
		 * Возвращает идентификатор типа данных, у которого есть указанное поле
		 * @param int $id идентификатор поля
		 * @return int|bool
		 */
		public function getTypeIdByFieldId($id);

		/**
		 * Возвращает идентификатор типа по его GUID
		 * @param string $guid Global Umi Identifier
		 * @param bool $ignoreCache не используется
		 * @return int|bool
		 * @throws coreException
		 */
		public function getTypeIdByGUID($guid, $ignoreCache = false);

		/**
		 * Создает тип данных с названием $name, дочерний от типа $parentId
		 * @param int $parentId id родительского типа данных, от которого будут унаследованы поля и группы полей
		 * @param string $name название создаваемого типа данных
		 * @param bool $isLocked =false статус блокировки. Этот параметр указывать не надо
		 * @param bool $ignoreParentGroups не наследовать группы и поля родительского типа данных
		 * @throws databaseException
		 * @return int идентификатор созданного типа
		 */
		public function addType($parentId, $name, $isLocked = false, $ignoreParentGroups = false);

		/**
		 * Удаляет тип данных с идентификатором $typeId
		 * Все объекты этого типа будут автоматически удалены без возможности восстановления.
		 * Все дочерние типы от $typeId будут удалены рекурсивно.
		 * @param int $typeId идентификатор типа данных, который будет удален
		 * @return bool
		 * @throws publicAdminException
		 * @throws databaseException
		 */
		public function delType($typeId);

		/**
		 * Удаляет тип данных из внутреннего кеша
		 * @param int $id идентификатор типа данных
		 * @return $this
		 */
		public function unloadType($id);

		/**
		 * Возвращает список идентификаторов дочерних типов данных, по отношению к заданному типу.
		 * Поиск осуществляется на ближайшем уровне иерархии.
		 * @param int $typeId идентификатор родительского типа данных
		 * @return int[]
		 * @throws databaseException
		 */
		public function getSubTypesList($typeId);

		/**
		 * Возвращает список идентификаторов дочерних типов данных, по отношению к заданному типу
		 * для указанного домена или для всех доменов.
		 * Поиск осуществляется на ближайшем уровне иерархии.
		 * @param int $typeId идентификатор родительского типа данных
		 * @param int|null $domainId идентификатор домена или null
		 * @return int[]
		 */
		public function getSubTypeListByDomain($typeId, $domainId);

		/**
		 * Возвращает идентификатор родительского типа с ближайшего уровня иерархии.
		 * @param int $typeId идентификатор дочернего типа данных
		 * @return int
		 * @throws databaseException
		 */
		public function getParentTypeId($typeId);

		/**
		 * Возвращает список идентификаторов дочерних типов данных, по отношению к заданному типу.
		 * Поиск осуществляется на всех уровнях иерархии.
		 * @param int $typeId идентификатор родительского типа данных
		 * @param mixed $children не используется
		 * @return int[]
		 * @throws databaseException
		 */
		public function getChildTypeIds($typeId, $children = false);

		/**
		 * Возвращает список идентификаторов дочерних типов данных, по отношению к заданному типу.
		 * Поиск осуществляется на всех уровнях иерархии, для заданного домена или для всех
		 * @param int $typeId идентификатор родительского типа данных
		 * @param int $domainId идентификатор домена или null
		 * @return int[]
		 * @throws databaseException
		 */
		public function getChildIdListByDomain($typeId, $domainId);

		/**
		 * Возвращает список идентификаторов дочерних типов данных, по отношению к заданным типам.
		 * Поиск осуществляется на всех уровнях иерархии.
		 * @param array $idList список идентификаторов родительских типов
		 * @return int[]
		 * @throws databaseException
		 */
		public function getChildIdListByParentIdList(array $idList);

		/**
		 * Возвращает список идентификаторов типов данных, у которых имя похоже на заданное, из указанного домена
		 * @param string $name искомое имя
		 * @param int|null $domainId идентификатор домена или null
		 * @return int[]
		 * @throws databaseException
		 */
		public function getIdListByNameLike($name, $domainId);

		/**
		 * Возвращает список типов данных, которые можно использовать в качестве справочника
		 * @param bool $publicOnly искать только среди публичных типов данных
		 * @param int|null $parentTypeId искать только в этом родителе
		 * @return array [<typeId> => <typeName>]
		 */
		public function getGuidesList($publicOnly = false, $parentTypeId = null);

		/**
		 * Возвращает список параметров объектных типов данных, связанных с базовым типом (umiHierarchyType)
		 * @param int $baseTypeId идентификатор базового типа
		 * @param bool $ignoreMicroCache = false не использовать микрокеширование результата
		 * @return array
		 *
		 * [
		 *       $baseTypeId => [
		 *          $typeId => $typeName
		 *      ]
		 * ]
		 *
		 * @throws databaseException
		 */
		public function getTypesByHierarchyTypeId($baseTypeId, $ignoreMicroCache = false);

		/**
		 * Возвращает список типов с заданным базовым типом и доменом
		 * @param int $baseTypeId идентификатор базового типа
		 * @param int $domainId идентификатор домена
		 * @return iUmiObjectType[]
		 */
		public function getListByBaseTypeAndDomain($baseTypeId, $domainId);

		/**
		 * Возвращает идентификатор типа данных, связанного с указанным
		 * базовым типом (iUmiHierarchyType), или false в случае ошибки
		 * @param int $id id базового типа
		 * @param bool $ignoreMicroCache не использовать микрокеширование результата
		 * @return int|bool
		 * @throws coreException
		 */
		public function getTypeIdByHierarchyTypeId($id, $ignoreMicroCache = false);

		/**
		 * Возвращает список данных типов
		 * @return array
		 *
		 * [
		 *      'id' => [
		 *          'id',
		 *          'name',
		 *          'guid',
		 *          'is_locked',
		 *          'parent_id',
		 *          'is_guidable',
		 *          'is_public',
		 *          'hierarchy_type_id',
		 *          'sortable'
		 *      ]
		 * ]
		 *
		 * @throws databaseException
		 */
		public function getAllTypes();

		/**
		 * Возвращает идентификатор типа данных, связанного с указанным
		 * базовым типом (iUmiHierarchyType), или false в случае ошибки
		 * @param string $module имя модуля базового типа
		 * @param string $method имя метода базового типа
		 * @return int|bool
		 */
		public function getTypeIdByHierarchyTypeName($module, $method = '');

		/** Очищает внутренний кэш класса */
		public function clearCache();

		/**
		 * Возвращает идентификатор иерархического типа данных,
		 * связанного с объектным типом данных, или false в случае ошибки
		 * @param int $id ид объектного типа данных
		 * @return int|bool
		 */
		public function getHierarchyTypeIdByObjectTypeId($id);

		/**
		 * Возвращает список идентификаторов объектных типов данных
		 * @param int $limit количество типов
		 * @param int $offset от какой позиции списка отсчитывать количество
		 * @return int[]
		 * @throws databaseException
		 */
		public function getIdList($limit = 15, $offset = 0);
	}
