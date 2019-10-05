<?php

	/** Интерфейс контроллера приложения UMI.CMS */
	interface iCmsController {

		/**
		 * Возвращает объект главного класса модуля по его имени
		 * @param string $moduleName имя модуля
		 * @param bool $resetCache не использовать кеш (по умолчанию кеш используется)
		 * @return def_module|bool
		 */
		public function getModule($moduleName, $resetCache = false);

		/**
		 * Возвращает список с названиями всех установленных модулей в системе
		 * @return array
		 */
		public function getModulesList();

		/**
		 * Определяет, установлен ли модуль в системе
		 * @param string $moduleName название модуля
		 * @return bool
		 */
		public function isModule($moduleName);

		/**
		 * Устанавливает модуль
		 * @param string $installPath установочный файл
		 * @throws publicAdminException если установочный файл не существует
		 */
		public function installModule($installPath);

		/**
		 * Возвращает имя модуля, который обрабатывает текущий запрос
		 * @return string
		 */
		public function getCurrentModule();

		/**
		 * Устанавливает имя модуля, который обрабатывает текущий запрос
		 * @param string $moduleName имя модуля
		 * @return iCmsController
		 */
		public function setCurrentModule($moduleName);

		/**
		 * Возвращает имя метода модуля, который обрабатывает текущий запрос
		 * @return string
		 */
		public function getCurrentMethod();

		/**
		 * Устанавливает имя метода модуля, который обрабатывает текущий запрос
		 * @param string $methodName имя метода
		 * @return iCmsController
		 */
		public function setCurrentMethod($methodName);

		/**
		 * Возвращает идентификатор текущей страницы
		 * @return bool|int
		 */
		public function getCurrentElementId();

		/**
		 * Устанавливает идентификатор текущей страницы
		 * @param bool|int $id идентификатор
		 * @return $this
		 */
		public function setCurrentElementId($id);

		/**
		 * Возвращает текущий языковой префикс.
		 * Если он не задан - пытается его определить.
		 * @return string
		 */
		public function getPreLang();

		/**
		 * Устанавливает текущий языковой префикс
		 * @param string $prefix префикс
		 * @return iCmsController
		 */
		public function setPreLang($prefix);

		/**
		 * Возвращает текущий шаблонизатор.
		 * @param bool $forceRefresh нужно ли заново обнаружить текущий шаблонизатор
		 * @return umiTemplater
		 * @throws coreException
		 */
		public function getCurrentTemplater($forceRefresh = false);

		/**
		 * Возвращает языковые константы для шаблонов сайта из файлов вида lang.*.php
		 * @return array
		 */
		public function getLangConstantList();

		/**
		 * Устанавливает языковую константу
		 * @param string $module модуль
		 * @param string $method метод
		 * @param string $label константа
		 * @return $this
		 */
		public function setLangConstant($module, $method, $label);

		/**
		 * Возвращает уникальный идентификатор текущего запроса в виде unix-timestamp
		 * @return int
		 */
		public function getRequestId();

		/** Определяет http referer текущего запроса и запоминает его */
		public function calculateRefererUri();

		/**
		 * Возвращает http referer текущего запроса
		 * @return string
		 */
		public function getCalculatedRefererUri();

		/**
		 * Возвращает директорию с ресурсами для текущего шаблона.
		 * @param bool $httpMode нужен относительный путь
		 * @return string
		 */
		public function getResourcesDirectory($httpMode = false);

		/**
		 * Возвращает текущую директорию с шаблонами
		 * @return string
		 */
		public function getTemplatesDirectory();

		/**
		 * Возвращает глобальные переменные в зависимости от
		 * текущего состояния системы
		 * @param bool $forcePrepare - если true, переменные будут еще раз инициализированы
		 * @return array
		 */
		public function getGlobalVariables($forcePrepare = false);

		/**
		 * Запускает umi-stream, возвращает результат работы
		 * @param string $uri Адрес потока в формате "udata://<id>?<params>"
		 * @throws coreException Если не удалось открыть поток
		 * @return string $data результат работы потока
		 */
		public function executeStream($uri);

		/**
		 * Возвращает текущий шаблон дизайна
		 * @return iTemplate|null
		 */
		public function getCurrentTemplate();

		/**
		 * Определяет текущий шаблон дизайна
		 * @return null|iTemplate - текущий шаблон дизайна, либо null
		 */
		public function detectCurrentDesignTemplate();

		public function analyzePath($reset = false);

		public function setAdminDataSet($dataSet);

		public function setUrlPrefix($prefix = '');

		public function getUrlPrefix();

		/**
		 * Возвращает первый доступный модуль
		 * @return string
		 * @throws publicAdminException
		 */
		public function getFirstAllowedModuleName();

		public static function doSomething();

		/** @deprecated */
		const ADMIN_MODE_ID = 'admin';
	}
