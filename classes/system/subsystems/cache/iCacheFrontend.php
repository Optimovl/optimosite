<?php

	use UmiCms\System\Cache\iEngineFactory;
	use UmiCms\System\Cache\Key\iGenerator;
	use UmiCms\System\Cache\Key\Validator\iFactory;
	use UmiCms\System\Request\Mode\iDetector;

	/** Интерфейс фасада для работы с кешем */
	interface iCacheFrontend {

		/** @const int DEFAULT_TIME_TO_LIVE время жизни кеша по умолчанию */
		const DEFAULT_TIME_TO_LIVE = 86400;

		/**
		 * Конструктор
		 * @param iEngineFactory $engineFactory фабрика хранилищ
		 * @param iGenerator $keyGenerator генератор ключей
		 * @param iConfiguration $configuration конфигурация
		 * @param iFactory $keyValidatorFactory фабрика валидаторов ключей
		 * @param iDetector $modeDetector определитель режима работы системы
		 */
		public function __construct(
			iEngineFactory $engineFactory,
			iGenerator $keyGenerator,
			iConfiguration $configuration,
			iFactory $keyValidatorFactory,
			iDetector $modeDetector
		);

		/**
		 * Определяет доступно ли кеширование
		 * @return bool
		 */
		public function isCacheEnabled();

		/**
		 * Сохранят сущность
		 * @param iUmiEntinty $entity сущность
		 * @param string|null $storeType тип объекта (@see iUmiEntinty::getStoreType())
		 * @param int $expire время жизни кеша
		 * @return bool
		 */
		public function save(iUmiEntinty $entity, $storeType = null, $expire = self::DEFAULT_TIME_TO_LIVE);

		/**
		 * Загружает сущность
		 * @param int $entityId идентификатор сущности
		 * @param string|null $storeType тип объекта (@see iUmiEntinty::getStoreType())
		 * @return iUmiEntinty|bool
		 */
		public function load($entityId, $storeType = null);

		/**
		 * Сохраняет результат выполнения sql запроса
		 * @param string $query текст запроса
		 * @param mixed $result результат запроса
		 * @param int $expire время жизни кеша
		 * @return bool
		 */
		public function saveSql($query, $result, $expire = self::DEFAULT_TIME_TO_LIVE);

		/**
		 * Загружает результат sql запроса
		 * @param string $query текст запроса
		 * @return mixed
		 */
		public function loadSql($query);

		/**
		 * Сохраняет скалярные данные
		 * @param string $key ключ
		 * @param mixed $value данные
		 * @param int $expire время жизни кеша
		 * @return bool
		 */
		public function saveData($key, $value, $expire = self::DEFAULT_TIME_TO_LIVE);

		/**
		 * Загружает скалярные данные
		 * @param string $key ключ
		 * @return mixed
		 */
		public function loadData($key);

		/**
		 * Приостанавливает/возобновляет работу кеша
		 * @param bool $flag оставновить/возобновить
		 * @return $this
		 */
		public function setDisabled($flag = true);

		/**
		 * Удаляет данные из кеша по ключу.
		 * Работает объектами, массивами и скалярами.
		 * @param string $key
		 * @param string|null $storeType тип сохраняемых данных (iUmiEntinty::getStoreType())
		 * @return bool
		 */
		public function del($key, $storeType = null);

		/**
		 * Удаляет из кеша результат выполнения запроса
		 * @param string $query sql запроса
		 * @return bool|null
		 */
		public function deleteSql($query);

		/**
		 * Очищает кеш
		 * @return bool|null
		 */
		public function flush();

		/**
		 * Возвращает список названий доступных хранилищ кеша
		 * @return string[]
		 */
		public function getCacheEngineList();

		/**
		 * Возвращает название текущего хранилища
		 * @return string
		 */
		public function getCacheEngineName();

		/**
		 * Изменяет сохраненное название используемого хранилища
		 * @param string $name название хранилища
		 * @return $this
		 */
		public function switchCacheEngine($name);
	}
