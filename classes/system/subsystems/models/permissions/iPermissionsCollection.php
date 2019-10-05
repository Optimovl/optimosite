<?php

	/** Управляет правами доступа на страницы и ресурсы модулей */
	interface iPermissionsCollection {

		/**
		 * Возвращает список групп пользователя или его идентификатор, если группы не заданы
		 * @param int $ownerId id пользователя или группы
		 * @return int|array
		 */
		public function getOwnerType($ownerId);

		/**
		 * Внутрисистемный метод, не является частью публичного API
		 * @param int $ownerId id пользователя или группы
		 * @param bool $ignoreSelf
		 * @return string фрагмент SQL-запроса
		 */
		public function makeSqlWhere($ownerId, $ignoreSelf = false);

		/**
		 * Определяет, разрешен ли пользователю или группе доступ к модулю
		 * @param int $ownerId id пользователя или группы пользователей
		 * @param string $module название модуля
		 * @return bool
		 */
		public function isAllowedModule($ownerId, $module);

		/**
		 * Определяет, разрешен ли пользователю или группе доступ к методу модуля
		 * @param int $ownerId id пользователя или группы пользователей
		 * @param string $module название модуля
		 * @param string $method название метода
		 * @param bool $ignoreSelf
		 * @return bool
		 */
		public function isAllowedMethod($ownerId, $module, $method, $ignoreSelf = false);

		/**
		 * @todo: отвратительное название для метода, который возвращает массив
		 * Возвращает права пользователя или группы пользователей на страницу
		 * @param int $ownerId id пользователя или группы пользователей
		 * @param int $objectId id страницы, доступ к которой проверяется
		 * @param bool $resetCache игнорировать кеш
		 * @return array [
		 *  0 => bool права на просмотр страницы,
		 *  1 => bool права на редактирование страницы,
		 *  2 => bool права на создание дочерней страницы,
		 *  3 => bool права на удаление страницы,
		 *  4 => bool права на перемещение страницы
		 * ]
		 */
		public function isAllowedObject($ownerId, $objectId, $resetCache = false);

		/**
		 * Определяет, разрешено ли текущему пользователю быстрое редактирование.
		 * @return bool
		 */
		public function isAllowedEditInPlace();

		/**
		 * Определяет, разрешено ли пользователю или группе пользователей администрировать домен
		 * @param int $ownerId Идентификатор пользователя или группы пользователей
		 * @param int $domainId Идентификатор домена
		 * @return int 1, если доступ разрешен, 0 если нет
		 */
		public function isAllowedDomain($ownerId, $domainId);

		/**
		 * Устанавливает права пользователю или группе на администрирование домена
		 * @param int $ownerId id пользователя или группы пользователей
		 * @param int $domainId id домена
		 * @param bool|int $allow если true, то доступ разрешен
		 * @return bool
		 */
		public function setAllowedDomain($ownerId, $domainId, $allow = 1);

		/**
		 * Определяет доступна ли страница для просмотра пользователю или группе
		 * @param int $ownerId идентификатор пользователя или группы
		 * @param int $pageId идентификатор страницы
		 * @return bool
		 */
		public function isPageCanBeViewed($ownerId, $pageId);

		/**
		 * Определяет, является ли пользователь или группа пользователей супервайзером
		 * @param int|bool $userId id пользователя (по умолчанию используется id текущего пользователя)
		 * @return bool
		 */
		public function isSv($userId = false);

		/**
		 * Определяет, является ли пользователь администратором,
		 * т.е. есть ли у него доступ к администрированию хотя бы одного модуля
		 * @param int|bool $userId id пользователя (по умолчанию используется id текущего пользователя)
		 * @param bool $ignoreCache
		 * @return bool
		 */
		public function isAdmin($userId = false, $ignoreCache = false);

		/**
		 * Определяет, является ли пользователь владельцем объекта
		 * @param int $objectId id объекта (iUmiObject)
		 * @param int|bool $userId id пользователя
		 * @return bool
		 */
		public function isOwnerOfObject($objectId, $userId = false);

		/**
		 * Устанавливает для страницы права по умолчанию и возвращает результат операции
		 * @param int $elementId идентификатор страницы
		 * @return bool
		 */
		public function setDefaultPermissions($elementId);

		/**
		 * Копирует права с родительского элемента
		 * @param int $elementId идентификатор элемента, на который устанавливаем права
		 * @return bool
		 */
		public function setInheritedPermissions($elementId);

		/**
		 * Удаляет все права на страницу для пользователя или группы
		 * @param int $elementId id страницы (iUmiHierarchyElement)
		 * @param int|bool $ownerId id пользователя или группы, чьи права сбрасываются.
		 * Если false, то права сбрасываются для всех пользователей.
		 * @return bool
		 */
		public function resetElementPermissions($elementId, $ownerId = false);

		/**
		 * Удаляет права на все страницы для пользователя или группы
		 * @param int $ownerId ид пользователя или группы
		 */
		public function deleteElementsPermissionsByOwnerId($ownerId);

		/**
		 * Сбрасывает все права на модули и методы для пользователя или группы
		 * @param int $ownerId id пользователя или группы пользователей
		 * @param array|null $modules массив, который указывает модули, для которых сбросить права.
		 *   По умолчанию, сбрасываются права на все модули.
		 * @return bool
		 */
		public function resetModulesPermissions($ownerId, $modules = null);

		/**
		 * Устанавливает определенные права на страницу для пользователя или группы
		 * @param int $ownerId id пользователя или группы пользователей
		 * @param int $elementId id страницы (iUmiHierarchyElement), для которой меняются права
		 * @param int $level уровень выставляемых прав от "0" до "31":
		 * ---------------------------------------------------------------------------
		 * | значение | чтение | редактирование | создание | удаление |  перемещение |
		 * |    0     |   -    |       -        |    -     |    -     |       -      |
		 * |    1     |   +    |       -        |    -     |    -     |       -      |
		 * |    3     |   +    |       +        |    -     |    -     |       -      |
		 * |    7     |   +    |       +        |    +     |    -     |       -      |
		 * |    15    |   +    |       +        |    +     |    +     |       -      |
		 * |    31    |   +    |       +        |    +     |    +     |       +      |
		 * ---------------------------------------------------------------------------
		 * @return bool true если не произошло ошибки
		 */
		public function setElementPermissions($ownerId, $elementId, $level);

		/**
		 * Устанавливает пользователю или группе права на $module/$method
		 * @param int $ownerId id пользователя или группы пользователей
		 * @param string $module название модуля
		 * @param string|bool $method название метода
		 * @param bool $cleanupPermissions
		 * @return bool
		 */
		public function setModulesPermissions(
			$ownerId,
			$module,
			$method = false,
			$cleanupPermissions = true
		);

		/**
		 * Удаляет права на использование модуля для пользователя или группы
		 * @param int $ownerId идентификатор пользователя или группы
		 * @param string $module идентификатор модуля
		 * @return $this
		 */
		public function deleteModulePermission($ownerId, $module);

		/**
		 * Удаляет права на использование группы прав на методы модуля для пользователя или группы
		 * @param int $ownerId идентификатор пользователя или группы
		 * @param string $module идентификатор модуля
		 * @param string $method идентификатор группы прав модуля
		 * @return $this
		 */
		public function deleteMethodPermission($ownerId, $module, $method);

		/**
		 * Устанавливает пользователю или группе права на выполнение метода модуля
		 * @param int $ownerId идентификатор пользователя или группы пользователей
		 * @param string $module название модуля
		 * @param string $method название метода
		 * @param bool $cleanupPermissions нужно ли удалить все права на модули
		 * и методы для пользователя или группы, если права меньше, чем у гостя
		 * @return bool
		 * @throws databaseException
		 */
		public function setMethodPermissions($ownerId, $module, $method, $cleanupPermissions = true);

		/**
		 * Определяет, имеет ли пользователь или группа права на какие-нибудь страницы
		 * @param int $ownerId id пользователя или группы
		 * @return bool false, если записей нет
		 */
		public function hasUserPermissions($ownerId);

		/**
		 * Определяет, имеет ли пользователь или группа права на какие-нибудь модули
		 * @param int $ownerId id пользователя или группы
		 * @return bool
		 */
		public function hasUserModulesPermissions($ownerId);

		/**
		 * Копирует права на все страницы от одного пользователя к другому
		 * @param int $fromUserId id пользователя или группы пользователей, из которых копируются права
		 * @param int $toUserId id пользователя или группы пользователей, в которые копируются права
		 * @return bool
		 */
		public function copyHierarchyPermissions($fromUserId, $toUserId);

		/**
		 * Возвращает права на модуль, загружаемые из файлов permissions.*.php
		 * @param string $module название модуля
		 * @param bool $skipCache запрещает использование ранее закэшированных прав
		 * @return array
		 */
		public function getStaticPermissions($module, $skipCache = false);

		/** Удаляет все записи о правах на модули и методы для пользователей, если они ниже, чем у гостя */
		public function cleanupBasePermissions();

		/**
		 * Устанавливает права по умолчанию для страницы по отношению к пользователю
		 * @param iUmiHierarchyElement $element экземпляр страницы
		 * @param int $ownerId id пользователя или группы пользователей
		 * @return int уровень доступа к странице, который был выбран системой
		 */
		public function setDefaultElementPermissions(iUmiHierarchyElement $element, $ownerId);

		/**
		 * Сбрасывает для пользователя или группы $owner_id права на все страницы на дефолтные
		 * @param int $ownerId id пользователя или группы пользователей
		 */
		public function setAllElementsDefaultPermissions($ownerId);

		/**
		 * Возвращает список всех пользователей или групп, имеющих права на страницу
		 * @param int $elementId id страницы
		 * @param int $level = 1 искомый уровень прав
		 * @return array массив id пользователей или групп, имеющих права на страницу
		 */
		public function getUsersByElementPermissions($elementId, $level = 1);

		/**
		 * Возвращает список сохраненных прав для страницы $elementId
		 * @param int $elementId
		 * @return array $ownerId => $permissionsLevel
		 */
		public function getRecordedPermissions($elementId);

		public function getPrivileged($perms);

		/**
		 * Очищает внутренний кеш класса
		 */
		public function clearCache();
	}
