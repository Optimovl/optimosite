<?php

	/** Объектный тип данных */
	interface iUmiObjectType extends iUmiEntinty {

		/**
		 * Возвращает название типа.
		 * @return string название типа
		 */
		public function getName();

		/**
		 * Устанавливает название типа.
		 * @param string $name новое название типа данных
		 */
		public function setName($name);

		/**
		 * Определяет, заблокирован ли тип данных.
		 * Если тип данных заблокирован, то его нельзя удалить из системы.
		 * @return bool true если тип данных заблокирован
		 */
		public function getIsLocked();

		/**
		 * Устанавливает флаг блокировки у типа данных.
		 * Если тип данных заблокирован, его нельзя будет удалить.
		 * @param bool $isLocked флаг блокировки
		 */
		public function setIsLocked($isLocked);

		/**
		 * Возвращает id родительского типа данных, от которого унаследованы группы полей и поля
		 * @return int id родительского типа данных
		 */
		public function getParentId();

		/**
		 * Определяет, помечен ли тип данных как справочник.
		 * @return bool true, если тип данных помечен как справочник
		 */
		public function getIsGuidable();

		/**
		 * Устанавливает флаг "Справочник" у типа данных.
		 * @param bool $usedAsGuide тип данных можно использовать в качестве справочника
		 */
		public function setIsGuidable($usedAsGuide);

		/**
		 * Устанавливает флаг "Общедоступный" для справочника.
		 * Не имеет значение, если тип данных не является справочником.
		 * @return bool true если тип данных общедоступен
		 */
		public function getIsPublic();

		/**
		 * Устанавливает значение флага "Общедоступен" для типа данных.
		 * Не имеет значения, если тип данных не является справочником.
		 * @param bool $isPublic новое значение флага "Общедоступен"
		 */
		public function setIsPublic($isPublic);

		/**
		 * Определяет, является ли тип публичным справочником
		 * @return bool
		 */
		public function isPublicGuide();

		/**
		 * Возвращает id базового типа (iUmiHierarchyType), к которому привязан тип данных.
		 * @return int
		 */
		public function getHierarchyTypeId();

		/**
		 * Определяет, являются ли объекты этого типа сортируемыми
		 * @return bool состояние сортировки
		 */
		public function getIsSortable();

		/**
		 * Устанавливает тип сортируемым
		 * @param bool $isSortable флаг сортировки
		 */
		public function setIsSortable($isSortable = false);

		/**
		 * Устанавливает базовый тип (iUmiHierarchyType), к которому привязан тип данных.
		 * @param int $hierarchyTypeId новый id базового типа
		 */
		public function setHierarchyTypeId($hierarchyTypeId);

		/**
		 * Устанавливает идентификатор домена, которому привязан тип
		 * @param int|null $domainId идентификатор домена или null, если тип общий
		 * @return $this
		 */
		public function setDomainId($domainId);

		/**
		 * Возвращает идентификатор домена или null, если тип общий
		 * @return int|null
		 */
		public function getDomainId();

		/**
		 * Создает новую группу полей в типе данных
		 * @param string $name имя группы полей
		 * @param string $title заголовок группы полей
		 * @param bool $isActive активность группы полей
		 * @param bool $isVisible видимость группы полей
		 * @param string $tip текст подсказки группы полей
		 * @return int ID созданной группы полей
		 * @throws coreException в случае ошибки MySQL или
		 * возникновении ошибки загрузки дочерних типов данных
		 */
		public function addFieldsGroup($name, $title, $isActive = true, $isVisible = true, $tip = '');

		/**
		 * Удаляет группу полей (iUmiFieldsGroup)
		 * @param int $groupId id группы, которую необходимо удалить
		 * @return bool true, если удаление прошло успешно, false если группа не существует
		 * @throws coreException При ошибке удаления в БД
		 */
		public function delFieldsGroup($groupId);

		/**
		 * Возвращает группу полей (iUmiFieldsGroup) по ее строковому идентификатору
		 * @param string $fieldGroupName строковой идентификатор группы полей
		 * @param bool $allowDisabled разрешить получать не активные группы
		 * @return iUmiFieldsGroup|bool
		 */
		public function getFieldsGroupByName($fieldGroupName, $allowDisabled = false);

		/**
		 * Возвращает группу полей (iUmiFieldsGroup) по ее id
		 * @param int $fieldGroupId id группы полей
		 * @param bool $ignoreIsActive false, если поиск ведется только среди активных групп
		 * @return iUmiFieldsGroup|bool
		 */
		public function getFieldsGroup($fieldGroupId, $ignoreIsActive = false);

		/**
		 * Возвращает список всех групп полей у типа данных
		 * @param bool $showDisabledGroups = false включить в результат неактивные группы полей
		 * @return iUmiFieldsGroup[]
		 */
		public function getFieldsGroupsList($showDisabledGroups = false);

		/**
		 * Устанавливает порядок следования группы полей
		 * @param int $groupId идентификатор группы полей, порядок которой нужно изменить
		 * @param int $newOrd новый порядковый номер группы полей
		 * @param bool $isLast хотим ли сделать группу полей последней в списке
		 * @return bool true, если порядок успешно изменен, false в противном случае
		 * @throws coreException
		 */
		public function setFieldGroupOrd($groupId, $newOrd, $isLast);

		/**
		 * Возвращает список всех полей типа данных
		 * @param bool $returnOnlyVisibleFields вернуть только видимые поля
		 * @return iUmiField[]
		 */
		public function getAllFields($returnOnlyVisibleFields = false);

		/**
		 * Возвращает id поля по его строковому идентификатору
		 * @param string $fieldName строковой идентификатор поля
		 * @param bool $ignoreInactiveGroups true, если нужно найти поле только в активных группах
		 * @return int|bool id поля, либо false если такого поля не существует
		 */
		public function getFieldId($fieldName, $ignoreInactiveGroups = true);

		/**
		 * Возвращает название модуля иерархического типа, если такой есть у этого типа данных
		 * @return string название модуля
		 */
		public function getModule();

		/**
		 * Возвращает название метода иерархического типа, если такой есть у этого типа данных
		 * @return string название метода
		 */
		public function getMethod();

		/**
		 * Возвращает GUID
		 * @return string
		 */
		public function getGUID();

		/**
		 * Устанавливает GUID
		 * @param string $guid
		 */
		public function setGUID($guid);

		/**
		 * Возвращает список полей группы
		 * @param string $name имя группы
		 * @return iUmiField[]
		 * @throws ExpectFieldGroupException
		 */
		public function getFieldListFromGroup($name);
	}
