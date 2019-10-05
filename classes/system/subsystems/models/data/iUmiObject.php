<?php

	/** Объект */
	interface iUmiObject extends iUmiEntinty {

		/**
		 * Возвращает название объекта
		 * @param bool $ignoreTranslation игнорировать преобразование языковой метки
		 * @return string
		 */
		public function getName($ignoreTranslation = false);

		/**
		 * Устанавливает название объекта
		 * Устанавливает флаг "Модифицирован".
		 * @param string $name
		 */
		public function setName($name);

		/**
		 * Устанавливает идентификатор типа данных объекта
		 * Используйте этот метод осторожно, потому что он просто переключает id типа данных.
		 * Уже заполненные значения останутся в БД, но станут недоступны через API,
		 * если не переключить тип данных для объекта назад.
		 * Устанавливает флаг "Модифицирован".
		 * @param int $typeId новый id типа данных
		 * @return bool true всегда
		 */
		public function setTypeId($typeId);

		/**
		 * Возвращает идентификатор типа данных объекта
		 * @return int
		 */
		public function getTypeId();

		/**
		 * Возвращает статус блокировки удаления объекта
		 * @return bool true если объект заблокирован
		 */
		public function getIsLocked();

		/**
		 * Устанавливает статус блокировки удаления объекта.
		 * Устанавливает флаг "Модифицирован".
		 * @param bool $isLocked новый статус блокировки
		 */
		public function setIsLocked($isLocked);

		/**
		 * Возвращает id владельца, пользователя который создал этот объект
		 * @return int|null id пользователя. Всегда действительный id для umiObject или NULL если не задан.
		 */
		public function getOwnerId();

		/**
		 * Устанавливает id владельца объекта.
		 * Это означает, что пользователь с заданным id полностью владеет этим объектом:
		 * создал его, может модифицировать, либо удалить.
		 * Устанавливает флаг "Модифицирован".
		 * @param int $ownerId id нового владельца
		 * @return bool
		 */
		public function setOwnerId($ownerId);

		/**
		 * Возвращает GUID (Global Umi ID)
		 * @return string
		 */
		public function getGUID();

		/**
		 * Устанавливает GUID (Global Umi ID)
		 * @param string $guid
		 * @return $this
		 * @throws coreException если GUID уже используется
		 */
		public function setGUID($guid);

		/**
		 * Возвращает ссылку на объект по протоколу uobject
		 * @return string
		 */
		public function getXlink();

		/**
		 * Возвращает время последнего изменения объекта
		 * @return int|null
		 */
		public function getUpdateTime();

		/**
		 * Устанавливает время изменения объекта
		 * Устанавливает флаг "Модифицирован".
		 * @param int $updateTime время в формате Unix timestamp
		 * @return bool
		 */
		public function setUpdateTime($updateTime);

		/**
		 * Возвращает значение индекса сортировки объекта
		 * @return int
		 */
		public function getOrder();

		/**
		 * Устанавливает значение индекса сортировки объекта
		 * Устанавливает флаг "Модифицирован".
		 * @param int $order значение индекса сортировки
		 * @return bool
		 */
		public function setOrder($order);

		/**
		 * Устанавливает флаг модификации объекта.
		 * При необходимости обновляет время модификации.
		 * @param bool $isUpdated флаг модификации
		 */
		public function setIsUpdated($isUpdated = true);

		/**
		 * Возвращает строковой идентификатор типа данных
		 * @return string
		 */
		public function getTypeGUID();

		/**
		 * Возвращает тип объекта
		 * @return iUmiObjectType
		 * @throws coreException
		 */
		public function getType();

		/**
		 * Проверяет, заполнены ли все обязательные поля у объекта
		 * @return bool
		 */
		public function isFilled();

		/**
		 * Возвращает поле объекта по его строковому идентификатору
		 * @param string $name строковой идентификатор свойства
		 * @return iUmiObjectProperty|null
		 */
		public function getPropByName($name);

		/**
		 * Возвращает поле объекта по его числовому идентификатору
		 * @param int $id id поля
		 * @return iUmiObjectProperty|null
		 */
		public function getPropById($id);

		/**
		 * Определяет существует ли поле с заданным числовым идентификатором
		 * @param int $id числовой идентификатор поля
		 * @return bool
		 */
		public function isPropertyExists($id);

		/**
		 * Определяет существует ли поле с заданным строковым идентификатором
		 * @param string $name строковой идентификатор поля
		 * @return bool
		 */
		public function isPropertyNameExist($name);

		/**
		 * Определяет существует ли группа полей с заданным числовым идентификатором
		 * @param int $id числовой идентификатор группы полей
		 * @return bool
		 */
		public function isPropGroupExists($id);

		/**
		 * Возвращает числовой идентификатор группы полей по ее строковому идентификатору
		 * @param string $name строковой идентификатор группы полей
		 * @return int|false
		 */
		public function getPropGroupId($name);

		/**
		 * Возвращает список идентификаторов полей, принадлежащих к заданной группе полей,
		 * по ее строковому идентификатору
		 * @param string $name строковой идентификатор группы полей
		 * @return int[]|bool
		 */
		public function getPropGroupByName($name);

		/**
		 * Возвращает список идентификаторов полей, принадлежащих к заданной группе полей,
		 * по ее числовому идентификатору.
		 * Загружает список идентификаторов полей группы.
		 * @param int $id числовой идентификатор группы полей
		 * @return int[]|bool
		 */
		public function getPropGroupById($id);

		/**
		 * Возвращает список полей объекта
		 * @param bool $onlyVisible возвращать только видимые поля
		 * @return iUmiField[]
		 */
		public function getPropList($onlyVisible = false);

		/**
		 * Возвращает значение поля
		 * @param string $name строковой идентификатор поля
		 * @param array|null $params дополнительные параметры (используются в поле типа "Составное")
		 * @return mixed|false
		 */
		public function getValue($name, $params = null);

		/**
		 * Возвращает значение свойства объекта по ID поля
		 * @param int $fieldId ID поля
		 * @param null|array $params дополнительные параметры
		 * @return bool
		 */
		public function getValueById($fieldId, $params = null);

		/**
		 * Устанавливает значение поля.
		 * Устанавливает флаг "Модифицирован".
		 * @param string $name строковой идентификатор поля
		 * @param mixed $value новое значение поля
		 * @return bool
		 */
		public function setValue($name, $value);

		/** Удаляет объект */
		public function delete();

		/**
		 * Возвращает модуль (имя) иерархического типа, соответствующего объектному типу
		 * @return bool|string
		 */
		public function getModule();

		/**
		 * Возвращает метод (расширение) иерархического типа, соответствующего объектному типу
		 * @return bool|string
		 */
		public function getMethod();

		/**
		 * @internal
		 * Загружает имена и идентификаторы полей объекта
		 * @return bool
		 */
		public function loadFields();
	}
