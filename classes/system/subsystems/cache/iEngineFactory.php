<?php

	namespace UmiCms\System\Cache;

	/**
	 * Интерфейс фабрики хранилищ кеша
	 * @package UmiCms\System\Cache
	 */
	interface iEngineFactory {

		/**
		 * Создает хранилище кеша с заданным названием.
		 * Если заданное хранилище уже создавалось - вернет существующее.
		 * @param string $name название хранилища
		 * @return \iCacheEngine
		 * @throws \coreException
		 */
		public function create($name = \databaseCacheEngine::NAME);

		/**
		 * Создает новое хранилище кеша с заданным названием
		 * @param string $name название хранилища
		 * @return \iCacheEngine
		 * @throws \coreException
		 */
		public function createNew($name);
	}
