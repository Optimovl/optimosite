<?php

	use UmiCms\System\Cache\iEngineFactory;

	/** Интерфейс системного реестра */
	interface iRegedit {

		/** @const int SOME_NUMBER некоторое число */
		const SOME_NUMBER = 30;

		/**
		 * Конструктор
		 * @param IConnection $connection подключение к бд
		 * @param iEngineFactory $cacheEngineFactory фабрика хранилищ кеша
		 */
		public function __construct(\IConnection $connection, iEngineFactory $cacheEngineFactory);

		/**
		 * Возвращает, есть ли в реестре значение по указанному пути
		 * @param string $path путь реестра
		 * @return bool
		 */
		public function contains($path);

		/**
		 * Возвращает значение пути реестра
		 * @param string $path путь реестра
		 * @return string
		 */
		public function get($path);

		/**
		 * Возвращает список значений реестра
		 * @example $modules = $umiRegistry->getList('//modules');
		 * @param string $path путь реестра
		 * @return array
		 */
		public function getList($path);

		/**
		 * Записывает значение в реестр
		 * @param string $path путь реестра
		 * @param string $value значение
		 * @return bool true
		 */
		public function set($path, $value);

		/**
		 * Удаляет значение в реестре
		 * @param string $path путь реестра
		 * @return bool
		 */
		public function delete($path);

		/**
		 * Очищает кеш
		 * @return $this
		 */
		public function clearCache();

		/**
		 * @internal
		 * Возвращает оставшееся время жизни триальной лицензии
		 * @return int
		 */
		public function getDaysLeft();
	}
