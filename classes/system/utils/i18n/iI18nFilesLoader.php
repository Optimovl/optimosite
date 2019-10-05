<?php

	namespace UmiCms\Classes\System\Utils\I18n;

	/** Интерфейс загрузчика языковых констант */
	interface iI18nFilesLoader {

		/**
		 * Конструктор
		 * @param array $moduleList список модулей, константы для которых нужно загрузить
		 * @param string $langPrefix языковой префикс констант
		 */
		public function __construct(array $moduleList, $langPrefix);

		/**
		 * Загружает языковые константы из файлов вида lang.*.php и возвращает их.
		 * Загружаемые константы:
		 *
		 * - Системные константы в общей директории модулей из файла lang.<prefix>.php или lang.php
		 * - Константы модулей из файла формата lang.<prefix>.php или lang.php
		 * - Константы расширений модулей из файла формата lang.*.<prefix>.php (любое количество файлов)
		 * - Константы шаблона сайта из файла формата lang.<prefix>.php или lang.php
		 *
		 * @return array
		 *
		 * [
		 *
		 *     'key' => %value%,
		 *     ...
		 *     'moduleName' => [
		 *         'key' => %value%,
		 *         ...
		 *     ],
		 *     ...
		 * ]
		 *
		 */
		public function loadLangConstants();

		/**
		 * Устанавливает путь до директории с шаблонами сайта
		 * @param string $path путь
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setTemplatePath($path);

		/**
		 * Возвращает загруженные константы
		 * @return array
		 */
		public function getLoadedConstants();
	}
