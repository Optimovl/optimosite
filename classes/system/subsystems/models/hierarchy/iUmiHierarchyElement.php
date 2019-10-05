<?php

	interface iUmiHierarchyElement extends iUmiEntinty {

		/**
		 * Определяет, удалена ли страница в корзину или нет
		 * @return bool
		 */
		public function getIsDeleted();

		/**
		 * Определяет, активна страница или нет
		 * @return bool
		 */
		public function getIsActive();

		/**
		 * Определяет, видима ли страница в меню или нет
		 * @return bool
		 */
		public function getIsVisible();

		/**
		 * Возвращает id языка (iLang), к которому привязана страница
		 * @return int
		 */
		public function getLangId();

		/**
		 * Возвращает id домена (iDomain), к которому привязана страница
		 * @return int
		 */
		public function getDomainId();

		/**
		 * Возвращает id шаблона дизайна (iTemplate), по которому отображается страница
		 * @return int
		 */
		public function getTplId();

		/**
		 * Возвращает id базового типа (iUmiHierarchyType) страницы
		 * @return int
		 */
		public function getTypeId();

		/**
		 * Возвращает время последней модификации страницы в формате UNIX TIMESTAMP
		 * @return int
		 */
		public function getUpdateTime();

		/**
		 * Возвращает порядок страницы относительно соседних страниц
		 * @return int
		 */
		public function getOrd();

		/**
		 * Возвращает псевдостатический адрес страницы, по которому строится ее адрес
		 * @return string
		 */
		public function getAltName();

		/**
		 * Возвращает флаг "по умолчанию" у страницы
		 * @return bool
		 */
		public function getIsDefault();

		/**
		 * Проверяет есть ли у страницы виртуальные копии
		 * @return bool результат проверки
		 * @throws Exception
		 */
		public function hasVirtualCopy();

		/**
		 * Проверяет является ли страница "оригинальной" по отношению к ее виртуальным копиям,
		 * то есть является ли страница первой созданной с идентификатором его объекта.
		 * @return bool результат проверки
		 */
		public function isOriginal();

		/**
		 * Возвращает объект, который является источником данных для страницы
		 * @return iUmiObject|null
		 */
		public function getObject();

		/**
		 * Возвращает id родительской страницы.
		 * @return int
		 */
		public function getParentId();

		/**
		 * Возвращает название страницы
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает название страницы
		 * @param string $name новое название страницы
		 */
		public function setName($name);

		/**
		 * Возвращает значение свойства объекта, который прикреплен к странице
		 * @param string $propName строковой идентификатор свойства
		 * @param null|mixed $params специальные параметры
		 * @param bool $resetCache @deprecated
		 * @return mixed
		 */
		public function getValue($propName, $params = null, $resetCache = false);

		/**
		 * Устанавливает значение свойства объекта, который прикреплен к странице
		 * @param string $propName строковой идентификатор свойства
		 * @param mixed $propValue новое значение свойства. Тип аргумента зависит от типа поля.
		 * @return bool true если не произошло ошибок
		 */
		public function setValue($propName, $propValue);

		/** Загружает информацию о полях (id поля => string_id поля) связанного объекта */
		public function loadFields();

		/**
		 * Устанавливает флаг, означающий, что страница может быть видима в меню
		 * @param bool $isVisible новое значение флага видимости
		 */
		public function setIsVisible($isVisible = true);

		/**
		 * Устанавливает флаг активности
		 * @param bool $isActive значение флага активности
		 */
		public function setIsActive($isActive = true);

		/**
		 * Устанавливает флаг удаленности (то есть того, что страница находится в корзине)
		 * @param bool $isDeleted значение флага удаленности
		 * @return mixed
		 */
		public function setDeleted($isDeleted = true);

		/**
		 * Устанавливает id базового типа (iUmiHierarchyType) страницы
		 * @param int $typeId id базового типа
		 */
		public function setTypeId($typeId);

		/**
		 * Устанавливает id языка (iLang), к которому привязана страница
		 * @param int $langId id языка
		 */
		public function setLangId($langId);

		/**
		 * Устанавливает шаблон дизайна (iTemplate), по которому отображается страница на сайте
		 * @param int $templateId id шаблона дизайна
		 */
		public function setTplId($templateId);

		/**
		 * Устанавливает домен (iDomain), к которому привязана страница
		 * @param int $domainId id домена
		 */
		public function setDomainId($domainId);

		/**
		 * Устанавливает время последней модификации страницы в формате UNIX TIMESTAMP
		 * @param int $updateTime время последнего изменения страницы.
		 * Если аргумент не передан, берется текущее время.
		 */
		public function setUpdateTime($updateTime = 0);

		/**
		 * Устанавливает номер порядка следования страницы в структуре относительно других страниц
		 * @param int $ord порядковый номер
		 */
		public function setOrd($ord);

		/**
		 * Устанавливает родителя страницы
		 * @param int $parentId id родительской страницы
		 * @throws coreException
		 */
		public function setRel($parentId);

		/**
		 * Устанавливает объект-источник данных страницы
		 * @param iUmiObject $object экземпляр класса umiObject
		 * @param bool $shouldSetUpdated если true, то на переданном объекте
		 * будет выполнен метод setIsUpdated() без параметров
		 */
		public function setObject(iUmiObject $object, $shouldSetUpdated = true);

		/**
		 * Устанавливает псевдостатический адрес, который участвует в формировании адреса страницы
		 * @param string $rawAltName новый псевдостатический адрес
		 * @param bool $autoConvert не указывайте этот параметр
		 */
		public function setAltName($rawAltName, $autoConvert = true);

		/**
		 * Устанавливает значение флаг "по умолчанию"
		 * @param bool $isDefault значение флага "по умолчанию"
		 */
		public function setIsDefault($isDefault = true);

		/**
		 * Возвращает id поля по его строковому идентификатору
		 * @param string $fieldName строковой идентификатор поля
		 * @return int|bool
		 */
		public function getFieldId($fieldName);

		/**
		 * Устанавливает флаг измененности.
		 * Если экземпляр не помечен как измененный, метод commit() блокируется.
		 * @param bool $isUpdated значение флага измененности
		 */
		public function setIsUpdated($isUpdated = true);

		/**
		 * @inheritdoc
		 * @return void
		 */
		public function commit();

		/**
		 * Возвращает id типа данных (iUmiObjectType),
		 * к которому относится объект (iUmiObject) источник данных страницы.
		 * @return int
		 */
		public function getObjectTypeId();

		/**
		 * Возвращает базовый тип, к которому относится страница
		 * @return iUmiHierarchyType
		 */
		public function getHierarchyType();

		/**
		 * Возвращает id объекта (iUmiObject), который служит источником данных для страницы
		 * @return int
		 */
		public function getObjectId();

		/**
		 * Возвращает название модуля базового типа страницы
		 * @return string
		 */
		public function getModule();

		/**
		 * Возвращает название метода базового типа страницы
		 * @return string
		 */
		public function getMethod();

		/** Удаляет страницу */
		public function delete();

		/**
		 * Возвращает id родительской страницы.
		 * @deprecated
		 * @see iUmiHierarchyElement::getParentId()
		 * @return int
		 */
		public function getRel();

		/**
		 * @deprecated
		 * TODO: Вынести из iUmiHierarchyElement
		 */
		public function updateYML();

		/**
		 * @deprecated
		 * @param bool $ignoreChildren не обходить детей текущей страницы
		 * @throws publicAdminException
		 */
		public function updateSiteMap($ignoreChildren = false);
	}
