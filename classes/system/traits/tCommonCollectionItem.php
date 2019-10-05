<?php

	/**
	 * Трейт общего функционала элемента коллекции.
	 * Можно применять только в классах, удовлетворяющих интерфейсу iUmiCollectionItem.
	 */
	trait tCommonCollectionItem {

		/** @var bool $isUpdated сущность была изменена */
		private $isUpdated;

		/** @var int $id идентификатор сущности */
		private $id;

		/** @inheritdoc */
		public function __construct(array $params, iUmiConstantMap $map) {
			$this->map = $map;
			/** @var tCommonCollectionItem|iUmiCollectionItem $this */
			/** @var ClassConfig $classConfig */
			$classConfig = self::getConfig();
			$params = $this->executeConstructorCallback('before', $params);

			foreach ($classConfig->get('fields') as $fieldConfig) {
				$isRequired = isset($fieldConfig['required']) ? $fieldConfig['required'] : false;
				$fieldName = $this->getFieldName($fieldConfig);

				if ($isRequired) {
					$this->checkField($fieldName, $params);
				}

				if (isset($params[$fieldName])) {
					$processedValue = $this->processValue($params[$fieldName], $fieldConfig);
					$this->executeSetter($this->getSetter($fieldConfig), $processedValue);
				}
			}

			$this->setUpdatedStatus(false);
			$this->executeConstructorCallback('after', $params);
		}

		/** @inheritdoc */
		public function setValue($name, $value) {
			if (!$this->isExistsProp($name)) {
				throw new Exception('Property "' . $name . '" not exists');
			}

			$fieldConfig = $this->getFieldConfig($name);

			if ($fieldConfig === null) {
				throw new Exception('Property "' . $name . '" not expected');
			}

			$isUnchangeable = isset($fieldConfig['unchangeable']) && $fieldConfig['unchangeable'];

			if ($isUnchangeable) {
				throw new Exception("{$name} cannot be changed");
			}

			return $this->executeSetter($this->getSetter($fieldConfig), $value);
		}

		/**
		 * Устанавливает значение свойства в случае, если оно отличается от текущего
		 * @param string $property имя свойства
		 * @param mixed $value новое значение свойства
		 * @param string $type имя типа значения свойства (элементарный PHP-тип)
		 * @return bool
		 */
		protected function setDifferentValue($property, $value, $type) {
			$newValue = $value;
			settype($newValue, $type);

			if ($this->$property != $newValue) {
				$this->setUpdatedStatus(true);
				$this->$property = $newValue;
				return true;
			}

			return false;
		}

		/** @inheritdoc */
		public function getValue($name) {
			if (!$this->isExistsProp($name)) {
				throw new Exception('Property "' . $name . '" not exists');
			}

			$fieldConfig = $this->getFieldConfig($name);

			if ($fieldConfig === null) {
				throw new Exception('Property "' . $name . '" not expected');
			}

			return $this->executeGetter($this->getGetter($fieldConfig));
		}

		/** @inheritdoc */
		public function getPropsList() {
			/** @var ClassConfig $classConfig */
			$classConfig = self::getConfig();
			$properties = [];

			foreach ($classConfig->get('fields') as $field) {
				$properties[] = $this->map->get($field['name']);
			}

			return $properties;
		}

		/**
		 * Возвращает идентификатор сущности
		 * @return int
		 */
		public function getId() {
			return $this->id;
		}

		/**
		 * Возвращает массив полей/свойств/атрибутов сущности со значениями
		 * @return array ['name' => 'value]
		 */
		public function export() {
			/** @var iUmiCollectionItem $this */
			$data = [];

			foreach ($this->getPropsList() as $propName) {
				$data[$propName] = $this->getValue($propName);
			}

			return $data;
		}

		/**
		 * Импортирует данные в поля/свойства/атрибуты сущности
		 * @param array $data данные ['name' => 'value]
		 * @return bool
		 */
		public function import(array $data) {
			/** @var iUmiCollectionItem $this */
			foreach ($data as $propName => $propValue) {
				$this->setValue($propName, $propValue);
			}

			return true;
		}

		/**
		 * Существует ли у сущности поле/свойство/атрибут с заданным именем
		 * @param string $name имя поля/свойства/атрибута
		 * @return bool
		 * @throws Exception
		 */
		public function isExistsProp($name) {
			/** @var iUmiCollectionItem $this */
			if (!is_string($name)) {
				throw new Exception('Wrong value for prop name given');
			}

			return in_array($name, $this->getPropsList());
		}

		/**
		 * Была ли сущности изменена
		 * @return bool
		 */
		public function isUpdated() {
			return $this->isUpdated;
		}

		/**
		 * Изменяет значение флага "была обновлена" сущности
		 * @param bool $isUpdated значение флага
		 */
		public function setUpdatedStatus($isUpdated) {
			$this->isUpdated = (bool) $isUpdated;
		}

		/**
		 * Перемещает текущую сущность по отношению к заданной
		 * @param \iUmiCollectionItem $baseEntity заданная сущность
		 * @param string $mode режим перемещения
		 * @return $this
		 */
		public function move(\iUmiCollectionItem $baseEntity, $mode) {
			return $this;
		}

		/**
		 * Проверяет на наличие входного значения поля
		 * @param string $name имя поля
		 * @param array $params входные параметры
		 * @throws Exception
		 */
		protected function checkField($name, $params) {
			if (!isset($params[$name])) {
				throw new Exception('Key "' . $name . '" expected');
			}
		}

		/**
		 * Возвращает имя сеттера поля
		 * @param array $config конфигурация поля
		 * @return string
		 */
		protected function getSetter($config) {
			return $this->getXetter($config, 'setter');
		}

		/**
		 * Возвращает имя сеттера поля
		 * @param array $config конфигурация поля
		 * @return string
		 */
		protected function getGetter($config) {
			return $this->getXetter($config, 'getter');
		}

		/**
		 * Возвращает геттер или сеттер
		 * @param array $config
		 * @param string $method название метода(сеттер или геттер)
		 * @return string
		 * @throws Exception
		 */
		protected function getXetter($config, $method) {
			if (isset($config[$method])) {
				return $config[$method];
			}

			$fieldName = $this->getFieldName($config);

			switch ($method) {
				case 'setter':
					return 'set' . $fieldName;
				case 'getter':
					return 'get' . $fieldName;
			}

			throw new Exception("Метод {$method} не поддерживается");
		}

		/**
		 * Возвращает имя поля
		 * @param array $config конфигурация поля
		 * @return mixed|null
		 */
		protected function getFieldName($config) {
			return (isset($config['name']) ? $this->map->get($config['name']) : null);
		}

		/**
		 * Исполняет сеттер
		 * @param string $setter имя сеттера
		 * @param mixed $value значение для сеттера
		 * @return mixed
		 */
		protected function executeSetter($setter, $value) {
			if (is_callable([$this, $setter])) {
				return $this->$setter($value);
			}

			return null;
		}

		/**
		 * Исполняет геттер и возвращает результат его исполнения
		 * @param string $getter имя геттера
		 * @return mixed
		 */
		protected function executeGetter($getter) {
			if (is_callable([$this, $getter])) {
				return $this->$getter();
			}

			return null;
		}

		/**
		 * Исполняет метод обратного вызова конструктора
		 * и возвращает измененные входные параметры
		 * @param string $mode режим вызова
		 * @param string $params входные параметры
		 * @return array
		 */
		protected function executeConstructorCallback($mode, $params) {
			/** @var ClassConfig $classConfig */
			$classConfig = self::getConfig();
			$callback = $classConfig->get('constructor', 'callback', $mode);

			if (method_exists($this, $callback)) {
				return $this->$callback($params);
			}

			return $params;
		}

		/**
		 * Обрабатывает значение
		 * @param mixed $value обрабатываемое значение
		 * @param array $fieldConfig конфигурация поля
		 * @return mixed
		 */
		protected function processValue($value, $fieldConfig) {
			if (!isset($fieldConfig['process'])) {
				return $value;
			}

			$processingMethod = $fieldConfig['process'];

			if (method_exists($this, $processingMethod)) {
				return $this->$processingMethod($value);
			}

			return $value;
		}

		/**
		 * Возвращает конфигурацию поля
		 * @param string $name имя поля
		 * @return mixed
		 */
		protected function getFieldConfig($name) {
			/** @var ClassConfig $config */
			$config = self::getConfig();

			$fields = array_filter(
				$config->get('fields'),
				function ($field) use ($name) {
					return ($this->map->get($field['name']) === $name);
				}
			);

			return array_shift($fields);
		}

		/**
		 * Возвращает экранированное значение константы из карты констант
		 * @param string $name название константы
		 * @return string
		 */
		protected function getEscapedConstant($name) {
			/** @var iUmiDataBaseInjector|iUmiConstantMapInjector $this */
			$connection = $this->getConnection();
			$map = $this->getMap();

			return $connection->escape($map->get($name));
		}

		/**
		 * Устанавливает идентификатор сущности
		 * @param int $id идентификатор сущности
		 * @return $this
		 * @throws Exception
		 */
		protected function setId($id) {
			if (!is_numeric($id)) {
				throw new Exception('Wrong value for id given');
			}

			$this->id = $id;
			return $this;
		}

		/**
		 * Возвращает название столбца таблицы
		 * @param string $columnConstant название константы, которая хранит название столбца таблицы
		 * @return string
		 */
		protected function getColumnName($columnConstant) {
			/** @var iUmiDataBaseInjector|iUmiConstantMapInjector $this */
			$connection = $this->getConnection();
			$map = $this->getMap();

			return $connection->escape($map->get($columnConstant));
		}
	}

