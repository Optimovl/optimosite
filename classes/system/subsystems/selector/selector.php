<?php

	use UmiCms\Service;

	/**
	 * Selector - механизм формирования выборок, который должен заменить старый механизм выборок
	 * с помощью umiSelection и umiSelectionParser. Класс selector создан для того, чтобы избавиться
	 * от необходимости использования дополнительных классов и упростить определение искомых полей.
	 */
	class selector implements IteratorAggregate {

		/** @var string[] Доступные режимы работы */
		protected static $modes = ['objects', 'pages'];

		/** @var string[] Системные свойства страниц при фильтрации */
		protected static $sysPagesWhereFields = [
			'id',
			'name',
			'owner',
			'domain',
			'lang',
			'is_deleted',
			'is_active',
			'is_visible',
			'updatetime',
			'is_default',
			'template_id',
			'alt_name',
			'ord',
			'*',
		];

		/** @var string[] Системные свойства страниц при группировке */
		protected static $sysPagesGroupFields = [
			'name',
			'owner',
			'domain',
			'lang',
			'is_deleted',
			'is_active',
			'is_visible',
			'updatetime',
			'is_default',
			'template_id',
			'obj_id',
			'obj_type_id',
			'ord',
		];

		/** @var string[] Системные свойства объектов при фильтрации */
		protected static $sysObjectsWhereFields = ['id', 'name', 'owner', 'guid', 'updatetime', 'ord', '*'];

		/** @var string[] Системные свойства объектов при группировке */
		protected static $sysObjectsGroupFields = ['id', 'name', 'owner', 'guid', 'updatetime', 'ord'];

		/** @var string[] Системные свойства при сортировке */
		protected static $sysOrderFields = ['name', 'ord', 'rand', 'updatetime', 'id'];

		/** @var string Режим работы */
		protected $mode;

		/** @var selectorExecutor Исполнитель выборки */
		protected $executor;

		/** @var selectorOption[] Опции селектора */
		protected $options = [];

		/** @var selectorWherePermissions свойства прав выбираемых сущностей */
		protected $permissions;

		/** @var int ограничение на количество сущностей */
		protected $limit;

		/** @var int отступ в выборке сущностей */
		protected $offset;

		/** @var selectorType[] типы выбираемых сущностей */
		protected $types = [];

		/** @var selectorWhereHierarchy[] свойства расположения в иерархии выбираемых страниц */
		protected $hierarchy = [];

		/** @var selectorWhereSysProp[] Системные свойства, по которым идет выборка */
		protected $whereSysProps = [];

		/** @var selectorGroupSysProp[] Системные свойства, по которым идет группировка */
		protected $groupSysProps = [];

		/** @var selectorOrderSysProp[] Системные свойства, по которым идет сортировка */
		protected $orderSysProps = [];

		/** @var selectorWhereFieldProp[] Свойства, по которым идет выборка */
		protected $whereFieldProps = [];

		/** @var selectorGroupFieldProp[] Свойства, по которым идет группировка */
		protected $groupFieldProps = [];

		/** @var selectorOrderFieldProp[] Свойства, по которым идет сортировка */
		protected $orderFieldProps = [];

		/** @var array|int Результаты выборки */
		protected $result;

		/** @var int Количество результатов без учета LIMIT */
		protected $length;

		/**
		 * @var string[] Список доступных свойств селектора
		 * @see selector::__get()
		 */
		private $allowedPropertyList = [
			'mode',
			'offset',
			'limit',
			'whereFieldProps',
			'orderFieldProps',
			'groupFieldProps',
			'whereSysProps',
			'orderSysProps',
			'groupSysProps',
			'types',
			'permissions',
			'hierarchy',
			'options',
		];

		/**
		 * Метод используется для запроса сущностей заданного типа.
		 * Возвращает экземпляр @see selectorGetter, на котором нужно вызвать метод для получения сущности.
		 *
		 * Примеры использования:
		 *
		 * 1) Получить объект (класс umiObject) по id
		 * selector::get('object')->id(50);
		 *
		 * 2) Получить страницу (класс umiHierarchyElement) по id
		 * selector::get('page')->id(50);
		 *
		 * 3) Получить объектный тип данных (класс umiObjectType) по id
		 * selector::get('object-type')->id(50);
		 *
		 * 4) Получить объектный тип данных по связке модуль/метод базового типа
		 * selector::get('object-type')->name('users', 'user');
		 *
		 * 5) Получить базовый тип данных (класс umiHierarchyType) по связке модуль/метод
		 * selector::get('hierarchy-type')->name('users', 'user');
		 *
		 * 6) Получить поле (класс umiField) по id
		 * selector::get('field')->id(50);
		 *
		 * 7) Получить тип поля (класс umiFieldType) по id
		 * selector::get('field-type')->id(50);
		 *
		 * 8) Получить домен (класс domain) по id
		 * selector::get('domain')->id(50);
		 *
		 * 9) Получить домен по доменному имени
		 * selector::get('domain')->host('site.ru');
		 *
		 * 10) Получить язык (класс lang) по id
		 * selector::get('lang')->id(50);
		 *
		 * 11) Получить язык по префиксу
		 * selector::get('lang')->prefix('ru');
		 *
		 * @param string $requestedType тип искомой сущности
		 * @return selectorGetter
		 */
		public static function get($requestedType) {
			return new selectorGetter($requestedType);
		}

		/** @param string $mode Режим: 'objects' или 'pages' */
		public function __construct($mode) {
			$this->setMode($mode);
		}

		/**
		 * Указать тип, по которому ведется выборка
		 * @param bool|string $typeClass тип ('object-type' или 'hierarchy-type')
		 * @return array|selectorType
		 */
		public function types($typeClass = false) {
			$this->throwIfAlreadyExecuted();
			if ($typeClass === false) {
				return $this->types;
			}
			return $this->types[] = new selectorType($typeClass);
		}

		/**
		 * Указать фильтр по полю
		 * @param string $fieldName название поля
		 * @return selectorWhereProp|selectorWhereHierarchy|selectorWherePermissions
		 * @throws selectorException если поле выбрано неверно или не существует
		 */
		public function where($fieldName) {
			$this->throwIfAlreadyExecuted();
			if ($fieldName == 'hierarchy') {
				if ($this->mode == 'objects') {
					throw new selectorException('Hierarchy filter is not suitable for "objects" selector mode');
				}

				return $this->hierarchy[] = new selectorWhereHierarchy();
			}

			if ($fieldName == 'permissions') {
				if ($this->mode == 'objects') {
					throw new selectorException('Permissions filter is not suitable for "objects" selector mode');
				}

				if ($this->permissions === null) {
					$this->permissions = new selectorWherePermissions();
				}

				return $this->permissions;
			}

			if (in_array($fieldName, ($this->mode == 'pages')
				? self::$sysPagesWhereFields
				: self::$sysObjectsWhereFields)) {
				return $this->whereSysProps[] = new selectorWhereSysProp($fieldName);
			}

			$fieldIdList = $this->searchField($fieldName);
			$fieldIdList = is_array($fieldIdList) ? $fieldIdList : [$fieldIdList];

			if (empty($fieldIdList)) {
				throw new selectorException(
					__METHOD__ . ": Field \"{$fieldName}\" is not presented in selected object types"
				);
			}

			if (umiCount($fieldIdList) > 1) {
				/** @var selectorOption $option */
				$option = $this->option('or-mode');
				$optionValue = (array) $option->value;
				$optionFields = isset($optionValue['fields']) ? $optionValue['fields'] : [];
				$optionFields[] = $fieldName;

				call_user_func_array([$option, 'fields'], $optionFields);
			}

			return $this->whereFieldProps[] =
				new selectorWhereFieldProp($fieldIdList, $this->option('search-in-related-object')->value);
		}

		/**
		 * Сортировать результат по полю
		 * @param string $fieldName Имя поля для сортировки
		 * @return selectorOrderField
		 * @throws selectorException если поле не существует
		 */
		public function order($fieldName) {
			$this->throwIfAlreadyExecuted();

			if (in_array($fieldName, self::$sysOrderFields)) {
				return $this->orderSysProps[] = new selectorOrderSysProp($fieldName);
			}

			$fieldIdList = $this->searchField($fieldName);
			$fieldIdList = is_array($fieldIdList) ? $fieldIdList : [$fieldIdList];

			if (empty($fieldIdList)) {
				throw new selectorException(
					__METHOD__ . ": Field \"{$fieldName}\" is not presented in selected objects types"
				);
			}

			return $this->orderFieldProps[] = new selectorOrderFieldProp($fieldIdList);
		}

		/**
		 * Группировать результаты по полю
		 * @param string $fieldName поле для группировки
		 * @return selectorGroupField
		 * @throws selectorException если поле не существует
		 */
		public function group($fieldName) {
			$this->throwIfAlreadyExecuted();

			if (in_array($fieldName, ($this->mode == 'pages')
				? self::$sysPagesGroupFields
				: self::$sysObjectsGroupFields)) {
				return $this->groupSysProps[] = new selectorGroupSysProp($fieldName);
			}

			$fieldIdList = $this->searchField($fieldName);
			$fieldIdList = is_array($fieldIdList) ? $fieldIdList : [$fieldIdList];

			if (empty($fieldIdList)) {
				throw new selectorException(
					__METHOD__ . ": Field \"{$fieldName}\" is not presented in selected objects types"
				);
			}

			return $this->groupFieldProps[] = new selectorGroupFieldProp($fieldIdList);
		}

		/**
		 * Ограничить количество результатов выборки
		 * @param int $offset отступ
		 * @param int|string $limit нужное число результатов или ключевое слово,
		 * отключающее ограничение - config.ini [kernel] grab-all-keyword
		 * @return $this
		 * @throws selectorException
		 */
		public function limit($offset, $limit) {
			$this->throwIfAlreadyExecuted();

			if ($limit === KEYWORD_GRAB_ALL) {
				return $this;
			}

			$this->limit = (int) $limit;
			$this->offset = (int) $offset;
			return $this;
		}

		/**
		 * Отключает ограничение количества результатов выборки
		 * @return $this
		 */
		public function unsetLimit() {
			$this->limit(0, 0);
			return $this;
		}

		/**
		 * Результат работы селектора
		 * Запускает executor для объекта
		 * @return array|int выбранные сущности|их количество
		 */
		public function result() {
			if ($this->result === null) {
				if (umiCount($this->orderSysProps) == 0) {
					$this->order('ord')->asc();
				}
				if ($this->mode == 'pages' && $this->permissions === null && !$this->option('no-permissions')->value) {
					$this->where('permissions');
				}
				$return = $this->option('return')->value;

				if (is_array($return) && in_array('count', $return)) {
					$this->result = $this->executor()->length();
				} else {
					$this->result = $this->executor()->result();
					$this->length = $this->executor()->length;
				}
			}

			$this->unloadExecutor();
			return $this->result;
		}

		/**
		 * Получить количество элементов в выборке
		 * @return int
		 */
		public function length() {
			if ($this->length === null) {
				if ($this->mode == 'pages' && $this->permissions === null) {
					$this->where('permissions');
				}
				$length = $this->executor()->length();

				if (in_array('count', $this->option('return')->value)) {
					$this->result = $length;
				} else {
					$this->result = $this->executor()->result();
				}
				$this->length = $length;
			}

			$this->unloadExecutor();
			return $this->length;
		}

		/**
		 * Добавить опцию
		 * @param string $name название опции
		 * @param mixed $value значение опции
		 * @return mixed
		 * @throws selectorException
		 */
		public function option($name, $value = null) {
			$this->throwIfAlreadyExecuted();
			if (!isset($this->options[$name])) {
				$selectorOption = new selectorOption($name);
				$this->options[$name] = $selectorOption;
			}
			if ($value !== null) {
				$this->options[$name]->value($value);
			}
			return $this->options[$name];
		}

		/** Сбросить результат выборки для повторного использования селектора */
		public function flush() {
			$this->result = null;
			$this->length = null;
		}

		/**
		 * Возвращает свойство селектора
		 * @param string $property название свойства
		 * @return mixed
		 */
		public function __get($property) {
			switch ($property) {
				case 'length':
				case 'total':
					return $this->length();
				case 'result':
					return $this->result();
				case 'first':
					return $this->first();
				case 'last':
					return $this->last();
			}

			if (in_array($property, $this->allowedPropertyList)) {
				return $this->$property;
			}

			return null;
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $property название свойства
		 * @return bool
		 */
		public function __isset($property) {
			return in_array($property, [
				'length',
				'total',
				'result',
				'first',
				'last',
				'mode',
				'offset',
				'limit',
				'whereFieldProps',
				'orderFieldProps',
				'groupFieldProps',
				'whereSysProps',
				'orderSysProps',
				'groupSysProps',
				'types',
				'permissions',
				'hierarchy',
				'options'
			]);
		}

		/**
		 * Реализация интерфейса IteratorAggregate
		 * @return ArrayIterator
		 */
		public function getIterator() {
			$this->result();
			return new ArrayIterator($this->result);
		}

		/**
		 * Получить запрос, сформированный executor'ом
		 * @return string
		 */
		public function query() {
			if ($this->mode == 'pages') {
				if (umiCount($this->orderSysProps) == 0) {
					$this->order('ord')->asc();
				}
				if ($this->permissions === null) {
					$this->where('permissions');
				}
			}
			return $this->executor()->query();
		}

		/**
		 * Получает ID поля по его имени
		 * @param string $fieldName имя поля
		 * @param boolean $returnFirst , если true, то будет возвращен ID первого поля
		 * @return int|array|null ID поля или массив ID полей
		 */
		public function searchField($fieldName, $returnFirst = false) {
			$fieldIds = [];

			if ($this->mode == 'pages' && umiCount($this->types) == 0) {
				$type = new selectorType('object-type');
				$type->guid('root-pages-type');
				$this->types[] = $type;
			}

			foreach ($this->types as $type) {
				$fieldId = $type->getFieldsId($fieldName);
				if ($fieldId) {
					if (is_array($fieldId)) {
						$fieldIds = array_unique(array_merge($fieldIds, $fieldId));
					} else {
						$fieldIds[] = $fieldId;
					}
				}
			}

			if (umiCount($fieldIds) === 1) {
				return (int) array_shift($fieldIds);
			}

			return $returnFirst ? array_shift($fieldIds) : $fieldIds;
		}

		/**
		 * Прервать попытку второго исполнения
		 * @throws selectorException
		 */
		protected function throwIfAlreadyExecuted() {
			if ($this->executor && $this->executor->getSkipExecutedCheckState()) {
				return;
			}
			if ($this->result !== null || $this->length !== null) {
				$message = getLabel('error-selector-executed');
				throw new selectorException($message);
			}
		}

		/** Инициализировать и вернуть экземпляр selectorExecutor */
		protected function executor() {
			if (!$this->executor) {
				$this->executor = new selectorExecutor($this);
			}
			return $this->executor;
		}

		/** Выгрузить из памяти selectorExecutor, если выборка уже сделана */
		protected function unloadExecutor() {
			if ($this->length !== null && $this->result !== null) {
				unset($this->executor);
			}
		}

		/**
		 * Установить режим работы селектора
		 * @param string $mode режим
		 * @throws selectorException
		 */
		protected function setMode($mode) {
			if (!in_array($mode, self::$modes)) {
				$modes = implode(', ', self::$modes);
				throw new selectorException("This mode \"{$mode}\" is not supported, choose one of these: {$modes}");
			}
			$this->mode = $mode;
			if ($mode == 'pages') {
				$this->setDefaultPagesWhere();
			}
		}

		/**
		 * Устанавливает значения системных свойств по умолчанию для выборки по страницам
		 * @throws selectorException
		 */
		protected function setDefaultPagesWhere() {
			$this->where('domain')->equals(Service::DomainDetector()->detectId());
			$this->where('lang')->equals(Service::LanguageDetector()->detectId());
			$this->where('is_deleted')->equals(0);

			if (Service::Request()->isNotAdmin()) {
				$this->where('is_active')->equals(1);
			}
		}

		/**
		 * Возвращает первый элемент результата выборки, если он существует
		 * @return mixed
		 */
		public function first() {
			$result = $this->result();
			if (is_array($result) && count($result) > 0) {
				/** @noinspection OffsetOperationsInspection */
				return $result[0];
			}
			return null;
		}

		/**
		 * Возвращает последний элемент результата выборки, если он существует
		 * @return mixed
		 */
		public function last() {
			$result = $this->result();
			if (is_array($result) && count($result) > 0) {
				/** @noinspection OffsetOperationsInspection */
				return $result[count($result) - 1];
			}
			return null;
		}
	}
