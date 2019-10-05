<?php

	/** Интерфейс управления объектами */
	interface iUmiObjectsCollection {

		/**
		 * Определяет, нужно ли сортировать элементы справочника по id
		 * @return bool
		 */
		public static function isGuideItemsOrderedById();

		/**
		 * Определяет, является ли переданный аргумент объектом UMI
		 * @param mixed $object
		 * @return bool
		 */
		public function isUmiObject($object);

		/**
		 * Возвращает объект по его имени
		 * @param string $name имя или языковая метка имени объекта
		 * @param mixed $typeId ID типа данных объекта
		 * @return iUmiObject|bool найденный объект или false, если объект не найден
		 */
		public function getObjectByName($name, $typeId = false);

		/**
		 * Возвращает экземпляр объекта или false, в случае неудачи
		 * @param int $id идентификатор объекта
		 * @param array|bool $data полный набор свойств объекта или false
		 *
		 * [
		 *      0 => 'name',
		 *      1 => 'type_id',
		 *      2 => 'is_locked',
		 *      3 => 'owner_id',
		 *      4 => 'guid',
		 *      5 => 'type_guid',
		 *      6 => 'updateTime',
		 *      7 => 'ord'
		 * ]
		 *
		 * @return iUmiObject|bool
		 */
		public function getObject($id, $data = false);

		/**
		 * Возвращает экземпляр объекта с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iUmiObject|bool
		 */
		public function getById($id);

		/**
		 * Возвращает список объектов заданного типа
		 * @param int $id идентификатор типа
		 * @return iUmiObject[]
		 * @throws Exception
		 */
		public function getListByType($id);

		/**
		 * Создает новый объект и возвращает его идентификатор
		 * @param string $name название объекта
		 * @param int $typeId идентификатор типа данных
		 * @param bool $isLocked состояние блокировки
		 * @return int
		 */
		public function addObject($name, $typeId, $isLocked = false);

		/**
		 * Удаляет объект.
		 * Нельзя удалить системных пользователей (гостя, супервайзера и его группу)
		 * или заблокированный объект.
		 * @param int $id идентификатор объекта
		 * @return bool
		 * @throws coreException
		 */
		public function delObject($id);

		/**
		 * Создает копию объекта со всеми свойствами и возвращает идентификатор копии
		 * @param int $id Id копируемого объекта
		 * @throws databaseException
		 * @return int
		 */
		public function cloneObject($id);

		/**
		 * Возвращает отсортированный по имени список всех объектов в справочнике
		 * @param int $guideId id справочника (id типа данных)
		 * @return array [<objectId> => <objectName>]
		 * @throws coreException
		 */
		public function getGuidedItems($guideId);

		/**
		 * Возвращает количество объектов указанного типа
		 * @param int $typeId идентификатор типа данных
		 * @return int
		 */
		public function getCountByTypeId($typeId);

		/**
		 * Выгружает объект из коллекции
		 * @param int $id id объекта
		 * @return bool
		 */
		public function unloadObject($id);

		/** Выгружает все объекты из коллекции */
		public function unloadAllObjects();

		/**
		 * Возвращает список идентификаторов всех объектов, загруженных в коллекцию
		 * @return int[]
		 */
		public function getCollectedObjects();

		/**
		 * Определяет загружен ли в коллекцию объект с заданным идентификатором
		 * @param int $id идентификатор объекта
		 * @return bool
		 */
		public function isLoaded($id);

		/**
		 * Определяет существует ли объект с заданными идентификатором.
		 * Так же учитывает идентификатор типа данных, если он передан.
		 * @param int $id идентификатор объекта
		 * @param int|bool $typeId идентификатор типа данных объекта или false
		 * @return bool
		 */
		public function isExists($id, $typeId = false);

		/**
		 * Возвращает объект по его гуиду
		 * @param string $guid гуид
		 * @return iUmiObject|bool
		 */
		public function getObjectByGUID($guid);

		/**
		 * Возвращает идентификатор объекта по его гуиду
		 * @param string $guid гуид
		 * @return int|bool
		 */
		public function getObjectIdByGUID($guid);

		/**
		 * Возвращает список объектов по id
		 * @param int[] $idList список идентификаторов объектов
		 * @return iUmiObject[]
		 */
		public function getObjectList(array $idList);

		/**
		 * Устанавливает, что объект был изменен во время сессии.
		 * @param int $id идентификатор измененного объекта
		 */
		public function addUpdatedObjectId($id);

		/**
		 * Возвращает список идентификаторов объектов, измененных за текущую сессию
		 * @return int[] массив, состоящий из id измененных значений
		 */
		public function getUpdatedObjects();

		/**
		 * Возвращает максимальное значение атрибута "дата последней модификации"
		 * среди загруженных объектов.
		 * @return int Unix timestamp
		 */
		public function getObjectsLastUpdateTime();

		/** Очищает внутренний кэш класса */
		public function clearCache();

		/**
		 * Меняет индекс сортировки двух объектов
		 * @param iUmiObject $firstObject первый объект
		 * @param iUmiObject $secondObject второй объект
		 * @param string $mode режим изменения сортировки
		 * @return bool
		 */
		public function changeOrder(iUmiObject $firstObject, iUmiObject $secondObject, $mode);

		/**
		 * Перестраивает индекс сортировки у объектов, чей
		 * индекс больше индекса первого объекта
		 * @param iUmiObject $firstObject первый объект
		 * @param string $mode режим перестраивания
		 * @return bool
		 * @throws coreException
		 */
		public function reBuildOrder(iUmiObject $firstObject, $mode);

		/**
		 * Возвращает максимальное значение индекса сортировки
		 * среди объектов заданного типа данных
		 * @param int $objectTypeId идентификатор объектного типа данных
		 * @return int|bool
		 * @throws coreException
		 */
		public function getMaxOrderByTypeId($objectTypeId);
	}
