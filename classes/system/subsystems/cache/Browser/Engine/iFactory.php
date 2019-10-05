<?php

	namespace UmiCms\System\Cache\Browser\Engine;

	use UmiCms\System\Cache\Browser\iEngine;

	/**
	 * Интерфейс фабрики реализаций браузерного кеширования
	 * @package UmiCms\System\Cache\Browser\Engine
	 */
	interface iFactory {

		/** @const string LAST_MODIFIED имя реализации браузерного кеширования с помощью заголовка "Last-Modified" */
		const LAST_MODIFIED = 'LastModified';

		/** @const string ENTITY_TAG имя реализации браузерного кеширования с помощью заголовка "ETag" */
		const ENTITY_TAG = 'EntityTag';

		/** @const string EXPIRES имя реализации браузерного кеширования с помощью заголовка "Expires" */
		const EXPIRES = 'Expires';

		/** @const string NONE имя реализации отключенного браузерного кеширования */
		const NONE = 'None';

		/**
		 * Конструктор
		 * @param \iServiceContainer $serviceContainer контейнер сервисов
		 */
		public function __construct(\iServiceContainer $serviceContainer);

		/**
		 * Создает реализацию браузерного кеша
		 * @param string $name имя реализации
		 * @return iEngine
		 * @throws \wrongParamException
		 */
		public function create($name);
	}
