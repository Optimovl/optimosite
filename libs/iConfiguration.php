<?php

	/** Интерфейс менеджера системных настроек */
	interface iConfiguration extends iSingleton {

		/** @const sting CRON_MODE режим работы системы "Крон" */
		const CRON_MODE = 'CRON';

		/** @const sting CLI_MODE режим работы системы при запуске через консоль */
		const CLI_MODE = 'CLI';

		/** @const sting HTTP_MODE режим работы системы при запуске по http */
		const HTTP_MODE = 'HTTP';

		/** @const string MYSQL_DB_DRIVER  название драйвера для СУБД MySQL */
		const MYSQL_DB_DRIVER = 'mysql';

		/**
		 * Возвращает все системные настройки
		 * @return array|null
		 */
		public function getParsedIni();

		/**
		 * Возвращает значение настройки
		 * @param string $section группа настройки
		 * @param string $variable название настройки
		 * @return mixed
		 */
		public function get($section, $variable);

		/**
		 * Устанавливает значение настройки
		 * @param string $section группа настройки
		 * @param string $variable название настройки
		 * @param mixed $value значение настройки
		 * @return mixed
		 */
		public function set($section, $variable, $value);

		/**
		 * Возвращает все системные настройки по секции
		 * @param string $section группа настройки
		 * @return array|null
		 */
		public function getList($section);

		/**
		 * Возвращает значение настройки из группы "includes"
		 * с преобразованиями
		 * @param string $key название настройки
		 * @param array $params параметры преобразования
		 * @return string
		 */
		public function includeParam($key, array $params = null);

		/** Вызывает сохранение настроек, если их значения менялись */
		public function save();

		/**
		 * Устанавливает флаг блокировки сохранения изменений конфигурационного файла
		 * @param bool $flag значение флага
		 * @return $this
		 */
		public function setReadOnlyConfig($flag = true);

		/**
		 * Загружает файл конфигурации.
		 * Если какая либо конфигурация уже была загружена - переопределяет значения конфигурации из нового файла.
		 * @param string $configPath путь до подключаемого файла конфигурации
		 * @return $this
		 */
		public function loadConfig($configPath);
	}
