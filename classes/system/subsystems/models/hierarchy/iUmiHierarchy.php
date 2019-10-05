<?php

	/** Предоставляет доступ к страницам сайта и методы для управления структурой сайта. */
	interface iUmiHierarchy {

		/** @const int приращение порядка вывода новой страницы */
		const INCREMENT_NEW_PAGE_ORDER = 1;

		/**
		 * Возвращает коэффициент похожести двух строк.
		 * Возвращает число от 0 до 100, где 0 - ничего общего, 100 - одинаковые строки.
		 * @param string $first первая строка
		 * @param string $second вторая строка
		 * @return int
		 */
		public static function compareStrings($first, $second);

		/**
		 * Конвертирует псевдостатический адрес в транслит и убирает недопустимые символы
		 * @param string $altName псевдостатический url
		 * @param bool|string $separator разделитель слов
		 * @return string
		 */
		public static function convertAltName($altName, $separator = false);

		/**
		 * Возвращает текущее время в формате UNIX TIMESTAMP
		 * @return int
		 */
		public static function getTimeStamp();

		/**
		 * Определяет, существует ли страница с заданным идентификатором
		 * @param int $id идентификатор страницы
		 * @return bool
		 */
		public function isExists($id);

		/**
		 * Определяет, загружена ли страница с заданным идентификатором
		 * @param int $id идентификатор страницы
		 * @return bool
		 */
		public function isLoaded($id);

		/**
		 * Возвращает экземпляр страницы или false в случае неудачи
		 * @param int $id идентификатор страницы
		 * @param bool $ignorePermissions флаг игнорирования прав доступа
		 * @param bool $ignoreDeleted флаг игнорирования удаленности
		 * @param array|bool $data полный набор свойств объекта или false
		 *
		 * [
		 *      0 => 'parent_id',
		 *      1 => 'type_id',
		 *      2 => 'lang_id',
		 *      3 => 'domain_id',
		 *      4 => 'tpl_id',
		 *      5 => 'object_id',
		 *      6 => 'ord',
		 *      7 => 'alt_name',
		 *      8 => 'is_active',
		 *      9 => 'is_visible',
		 *      10 => 'is_deleted',
		 *      11 => 'update_time',
		 *      12 => 'is_default',
		 *      13 => 'object: name',
		 *      14 => 'object: type_id',
		 *      15 => 'object: is_locked',
		 *      16 => 'object: owner_id',
		 *      17 => 'object: guid',
		 *      18 => 'object: type_guid',
		 *      19 => 'object: update_time',
		 *      20 => 'object: ord',
		 * ]
		 *
		 * @return iUmiHierarchyElement|bool
		 */
		public function getElement($id, $ignorePermissions = false, $ignoreDeleted = false, $data = false);

		/**
		 * Возвращает список страниц
		 * @param int $limit количество страниц
		 * @param int $offset от какой позиции списка отсчитывать количество
		 * @return iUmiHierarchyElement[]
		 */
		public function getList($limit = 15, $offset = 0);

		/**
		 * Возвращает список страниц по их идентификаторам
		 * @param int|int[] $idList список идентификаторов страниц
		 * @return iUmiHierarchyElement[]
		 */
		public function loadElements($idList);

		/**
		 * Помещает страницу в корзину.
		 * Виртуальные копии удаляются из системы сразу, минуя корзину.
		 * @param int $id идентификатор страницы
		 * @param bool $deleteChildren повторить операцию для дочерних страниц
		 * @return bool
		 */
		public function delElement($id, $deleteChildren = true);

		/**
		 * Возвращает оригинальную (первую) страницу, связанную с заданным объектом
		 * @param int $objectId идентификатор объекта
		 * @return bool|iUmiHierarchyElement
		 * @throws Exception
		 */
		public function getOriginalPage($objectId);

		/**
		 * Создает виртуальную копию (подобие symlink в файловых системах) указанной страницы
		 * и возвращает ее идентификатор или false в случае неудачи.
		 * @param int $id идентификатор копируемой страницы
		 * @param int $parentId идентификатор родителя созданной копии
		 * @param bool $copyChildren рекурсивно копировать дочерние страницы
		 * @return int|bool
		 */
		public function copyElement($id, $parentId, $copyChildren = false);

		/**
		 * Создает виртуальную копию в корне иерархии
		 * @param int $originalPageId идентификатор копируемой страницы
		 * @param int|null $domainId идентификатор домена, где требуется разместить копию
		 * @param bool $copyChildren флаг необходимости копировать дочерние страницы
		 * @return iUmiHierarchyElement
		 * @throws databaseException|Exception
		 */
		public function createVirtualCopyOnRootLevel($originalPageId, $domainId = null, $copyChildren = false);

		/**
		 * Создает копию указанной страницы вместе со всеми данными
		 * и возвращает ее идентификатор или false в случае неудачи.
		 * @param int $id идентификатор копируемой страницы
		 * @param int $parentId идентификатор родителя созданной копии
		 * @param bool $copySubPages рекурсивно копировать дочерние страницы
		 * @return int|bool
		 */
		public function cloneElement($id, $parentId, $copySubPages = false);

		/**
		 * Возвращает идентификаторы страниц, помещенных в корзину.
		 * @param int &$total общее число страниц в корзине
		 * @param int $limit число запрошенных страниц
		 * @param int $page страница результатов
		 * @param string $searchName строка поиска по имени элементов
		 * @param int|null $domainId идентификатор домена, если не передан -
		 * домен не будет участвовать в фильтрации.
		 * @param int|null $languageId идентификатор языка, если не передан -
		 * язык не будет участвовать в фильтрации.
		 * @return int[]
		 */
		public function getDeletedList(
			&$total = 0,
			$limit = 20,
			$page = 0,
			$searchName = '',
			$domainId = null,
			$languageId = null
		);

		/**
		 * Восстанавливает страницу из корзины
		 * @param int $id идентификатор страницы
		 * @param bool $restoreChildren повторить операцию для дочерних страниц
		 * @return bool
		 */
		public function restoreElement($id, $restoreChildren = true);

		/**
		 * Удаляет заданную страницу
		 * @param int $parentId идентификатор страницы
		 * @param bool $killChildren повторить операцию для дочерних страниц
		 * @return bool
		 */
		public function killElement($parentId, $killChildren = true);

		/**
		 * Удаляет заданную страницу и всех ее детей из корзины
		 * @param int $parentId идентификатор страницы
		 * @param int $removedSoFar не передавать, используется для внутренних целей
		 * @return bool
		 */
		public function removeDeletedElement($parentId, &$removedSoFar = 0);

		/**
		 * Очищает корзину
		 * @return bool
		 */
		public function removeDeletedAll();

		/**
		 * Очищает корзину с ограничением на $limit страниц
		 * @param mixed $limit число удаляемых страниц
		 * @return int число удаленных страниц
		 */
		public function removeDeletedWithLimit($limit = false);

		/**
		 * Возвращает идентификатор родительской страницы или false в случае неудачи.
		 * @param int $id идентификатор дочерней страницы
		 * @return int|bool
		 */
		public function getParent($id);

		/**
		 * Возвращает идентификаторы всех родительских страниц для указанной страницы
		 * @param int $id идентификатор дочерней страницы
		 * @param bool $includeSelf включить в результат дочернюю страницу
		 * @param bool $ignoreCache не использовать микрокеширование
		 * @return int[]
		 */
		public function getAllParents($id, $includeSelf = false, $ignoreCache = false);

		/**
		 * Возвращает список идентификаторов страниц, дочерних заданной на всю глубину вложенности, в виде дерева
		 * @param int $rootPageId идентификатор родительской страницы
		 * @param bool $allowInactive включить в результат неактивные дочерние страницы
		 * @param bool $allowInvisible включить в результат невидимые в меню дочерние страницы
		 * @param int $depth уровень вложенности, на котором искать дочерние страницы
		 * @param int|bool $hierarchyTypeId идентификатор иерархического типа данных,
		 * к которому должны принадлежать дочерние страницы
		 * @param int|bool $domainId включить в результат только страницы из текущего домена (работает если ищем от корня)
		 * @param int|bool $languageId включить в результат только страницы из текущего языка (работает если ищем от корня)
		 * @return array [
		 *          page_id =>  [
		 *                  page_id => [
		 *                          page_id => []
		 *                         ],
		 *                  page_id => []
		 *                ],
		 *          page_id => []
		 *         ]
		 */
		public function getChildrenTree(
			$rootPageId,
			$allowInactive = true,
			$allowInvisible = true,
			$depth = 0,
			$hierarchyTypeId = false,
			$domainId = false,
			$languageId = false
		);

		/**
		 * Возвращает абсолютный максимальный уровень вложенности
		 * @param int $rootPageId идентификатор корневой страницы
		 * @param int $maxDepth относительный максимальный уровень вложенности
		 * @return int
		 * @throws publicAdminException
		 */
		public function getMaxDepth($rootPageId, $maxDepth);

		/**
		 * Возвращает плоский список идентификаторов страниц, дочерних заданной на всю глубину вложенности
		 * @param int $rootPageId идентификатор родительской страницы
		 * @param bool $allowInactive включить в результат неактивные дочерние страницы
		 * @param bool $allowInvisible включить в результат невидимые в меню дочерние страницы
		 * @param int|bool $hierarchyTypeId идентификатор иерархического типа данных,
		 * к которому должны принадлежать дочерние страницы
		 * @param int|bool $domainId включить в результат только страницы из текущего домена (работает если ищем от корня)
		 * @param bool $includeSelf включить в результат идентификатор родительской страницы
		 * @param int|bool $languageId включить в результат только страницы из текущего языка (работает если ищем от корня)
		 * @return array [
		 *          0 => page_id,
		 *          1 => page_id,
		 *          ...
		 *          n => page_id
		 *         ]
		 */
		public function getChildrenList(
			$rootPageId,
			$allowInactive = true,
			$allowInvisible = true,
			$hierarchyTypeId = false,
			$domainId = false,
			$includeSelf = false,
			$languageId = false
		);

		/**
		 * Возвращает отсортированный по иерархии массив с данными о страницах
		 * @param array $pageIds [
		 *              # => [
		 *                'level' => int уровень вложенности страницы,
		 *                'ord'  => int индекс сортировки страницы в рамках ее уровня вложенности
		 *              ]
		 *             ]
		 * @return array
		 */
		public function sortByHierarchy(array $pageIds);

		/**
		 * Возвращает количество страниц, дочерних заданной на всю глубину вложенности
		 * @param int $rootPageId идентификатор родительской страницы
		 * @param bool $allowInactive включить в результат неактивные дочерние страницы
		 * @param bool $allowInvisible включить в результат невидимые в меню дочерние страницы
		 * @param int $depth уровень вложенности, на котором искать дочерние страницы
		 * @param int|bool $hierarchyTypeId идентификатор иерархического типа данных,
		 * к которому должны принадлежать дочерние страницы
		 * @param int|bool $domainId включить в результат только страницы из текущего домена (работает если ищем от корня)
		 * @param int|bool $languageId включить в результат только страницы из текущего языка (работает если ищем от корня)
		 * @param bool $allowPermissions учитывать прав на просмотр дочерних страниц для текущего пользователя
		 * @return bool|int
		 */
		public function getChildrenCount(
			$rootPageId,
			$allowInactive = true,
			$allowInvisible = true,
			$depth = 0,
			$hierarchyTypeId = false,
			$domainId = false,
			$languageId = false,
			$allowPermissions = false
		);

		/**
		 * Переключает режим генерации адресов между относительным и абсолютным
		 * (в абсолютном режиме включается домен, даже если он совпадает с текущим доменом)
		 * @param bool $isForced (true - абсолютный режим, false - обычный режим)
		 * @return bool предыдущее значение
		 */
		public function forceAbsolutePath($isForced = true);

		/**
		 * Возвращает адрес страницы по ее идентификатору
		 * @param int $elementId идентификатор страницы
		 * @param bool $ignoreLang игнорировать языковой префикс к адресу страницы
		 * @param bool $ignoreIsDefaultStatus игнорировать статус страницы "по умолчанию" и сформировать для нее полный путь
		 * @param bool $ignoreCache игнорировать кеш
		 * @param bool $ignoreUrlSuffix игнорировать суффикс ссылок
		 * @return string
		 */
		public function getPathById(
			$elementId,
			$ignoreLang = false,
			$ignoreIsDefaultStatus = false,
			$ignoreCache = false,
			$ignoreUrlSuffix = false
		);

		/**
		 * Возвращает идентификатор страницы по ее адресу или false в случае неудачи.
		 * @param string $elementPath адрес страницы
		 * @param bool $showDisabled искать среди неактивных страниц
		 * @param int &$errorsCount количество несовпадений при разборе адреса
		 * @param mixed $domainId идентификатор домена
		 * @param mixed $langId идентификатор языка
		 * @return int|bool
		 */
		public function getIdByPath(
			$elementPath,
			$showDisabled = false,
			&$errorsCount = 0,
			$domainId = false,
			$langId = false
		);

		/**
		 * Создает новую страницу и возвращает ее идентификатор
		 * @param int $parentId идентификатор родителя
		 * @param int $hierarchyTypeId идентификатор иерархического типа
		 * @param string $name название
		 * @param string $altName псевдостатический адрес (если не передан, то будет вычислен из $name)
		 * @param bool $objectTypeId идентификатор типа данных (если не передан, то будет вычислен из $hierarchyTypeId)
		 * @param bool|int $domainId идентификатор домена (имеет смысл только если $parentId = 0)
		 * @param bool|int $langId идентификатор языковой версии (имеет смысл только если $parentId = 0)
		 * @param bool|int $templateId идентификатор шаблона
		 * @return int
		 * @throws coreException
		 */
		public function addElement(
			$parentId,
			$hierarchyTypeId,
			$name,
			$altName,
			$objectTypeId = false,
			$domainId = false,
			$langId = false,
			$templateId = false
		);

		/**
		 * Возвращает страницу со статусом "по умолчанию" (главная страница) или false в случае неудачи.
		 * @param null|int $langId идентификатор языковой версии, если не указан, берется текущий язык
		 * @param null|int $domainId идентификатор домена, если не указан, берется текущий домен
		 * @return iUmiHierarchyElement|bool
		 */
		public function getDefaultElement($langId = null, $domainId = null);

		/**
		 * Возвращает идентификатор страницы со статусом "по умолчанию" (главная страница) или false в случае неудачи.
		 * @param bool|int $langId идентификатор языковой версии, если не указан, берется текущий язык
		 * @param bool|int $domainId идентификатор домена, если не указан, берется текущий домен
		 * @return int|bool
		 */
		public function getDefaultElementId($langId = false, $domainId = false);

		/**
		 * Определяет, включен ли режим генерации абсолютных адресов
		 * @return bool
		 */
		public function isPathAbsolute();

		/**
		 * Перемещает указанную страницу
		 * @param int $id идентификатор перемещаемой страницы
		 * @param int $parentId идентификатор новой родительской страницы
		 * @param int|bool $previousElementId идентификатор страницы, перед которой
		 * нужно разместить указанную страницу. Если не задан, страница помещается в конец списка.
		 * @return bool
		 */
		public function moveBefore($id, $parentId, $previousElementId = false);

		/**
		 * Перемещает указанную страницу в начало списка детей родительской страницы
		 * @param int $id идентификатор перемещаемой страницы
		 * @param int $parentId идентификатор новой родительской страницы
		 * @return bool
		 */
		public function moveFirst($id, $parentId);

		/**
		 * Определяет, есть ли у текущего пользователя права на чтение страницы
		 * @param int $id идентификатор страницы
		 * @return bool
		 */
		public function isAllowed($id);

		/**
		 * Возвращает идентификатор объектного типа данных,
		 * которому принадлежит больше всего страниц внутри указанной страницы.
		 * @param int $id идентификатор страницы
		 * @param int $depth глубина поиска
		 * @param int $hierarchyTypeId идентификатор иерархического типа страниц
		 * @param array $excludeHierarchyTypeIds список идентификаторов исключаемых 
		 * из результата иерархических типов страниц
		 * @return int
		 */
		public function getDominantTypeId($id, $depth = 1, $hierarchyTypeId = null, $excludeHierarchyTypeIds = []);

		/**
		 * Помечает страницу как измененную
		 * @param int $id идентификатор страницы
		 */
		public function addUpdatedElementId($id);

		/**
		 * Возвращает список идентификаторов измененных страниц
		 * @return int[]
		 */
		public function getUpdatedElements();

		/**
		 * Возвращает список идентификаторов запрошенных страниц
		 * @return int[]
		 */
		public function getCollectedElements();

		/**
		 * Выгружает страницу из внутреннего кэша
		 * @param int $id идентификатор страницы
		 * @return bool
		 */
		public function unloadElement($id);

		/** Выгружает все страницы из внутреннего кэша */
		public function unloadAllElements();

		/**
		 * Возвращает максимальное значение атрибута "дата последней модификации"
		 * среди загруженных страниц.
		 * @return int дата в формате UNIX TIMESTAMP
		 */
		public function getElementsLastUpdateTime();

		/**
		 * Возвращает максимальный уровень вложенности потомков страницы
		 * @param int $id идентификатор страницы
		 * @return int|bool
		 */
		public function getMaxNestingLevel($id);

		/**
		 * Возвращает идентификаторы страниц, использующие указанный объект в качестве источника данных
		 * @param int $objectId идентификатор объекта
		 * @param bool $ignoreDomain искать независимо от домена
		 * @param bool $ignoreLang искать независимо от языковой версии
		 * @param bool $ignoreDeleted игнорировать страницы в корзине
		 * @return int[]
		 */
		public function getObjectInstances(
			$objectId,
			$ignoreDomain = false,
			$ignoreLang = false,
			$ignoreDeleted = false
		);

		/**
		 * Возвращает идентификатор шаблона, который выставлен у большинства страниц -
		 * потомков указанной страницы.
		 * @param int $parentId идентификатор родительской страницы
		 * @return int|bool
		 */
		public function getDominantTplId($parentId);

		/**
		 * Возвращает список идентификаторов страниц, измененных со времени указанной даты
		 * @param int $limit ограничение на количество результатов
		 * @param int $timestamp = 0 дата в формате UNIX TIMESTAMP
		 * @return int[]
		 */
		public function getLastUpdatedElements($limit, $timestamp = 0);

		/**
		 * Проверяет список страниц на предмет того, имеют ли они виртуальные копии
		 * @param array $elements [$pageId => $_dummy, ...]
		 * @param bool $includeDeleted учитывать ли все (удаленные и неудаленные) страницы
		 * @return array измененный массив со значениями для запрошенных страниц:
		 * true - если виртуальные копии есть, false - если нет
		 */
		public function checkIsVirtual($elements, $includeDeleted = false);

		/**
		 * Перестраивает дерево связей для страницы
		 * @param int $id идентификатор страницы
		 */
		public function rebuildRelationNodes($id);

		/**
		 * Строит дерево связей для страницы относительно родителей
		 * @param int $id идентификатор страницы
		 * @return bool
		 */
		public function buildRelationNewNodes($id);

		/**
		 * Определяет, является ли указанная страница потомком страницы
		 * @param int|iUmiHierarchyElement $child страница-потомок
		 * @param int|iUmiHierarchyElement $parent страница-предок
		 * @return bool
		 */
		public function hasParent($child, $parent);

		/** Очищает внутренний кэш класса */
		public function clearCache();

		/** Очищает внутренний кэш класса для страниц по умолчанию */
		public function clearDefaultElementCache();

		/**
		 * Возвращает скорректированный псевдостатический адрес страницы.
		 * В случае конфликта с уже существующим адресом
		 * к адресу добавляется цифра от 1 до 9, чтобы адрес был уникальным.
		 * Учитываются конфликты с названиями модулей, языками и адресами других страниц.
		 *
		 * @param string $altName псевдостатический адрес
		 * @param iUmiHierarchyElement $element страница
		 * @param bool $denseNumbering использовать все свободные цифры по порядку
		 * @param bool $ignoreCurrentElement не учитывать адрес страницы $element как конфликт
		 * @return string
		 */
		public function getRightAltName(
			$altName,
			$element,
			$denseNumbering = false,
			$ignoreCurrentElement = false
		);
	}
