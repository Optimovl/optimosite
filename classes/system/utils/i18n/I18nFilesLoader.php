<?php

	namespace UmiCms\Classes\System\Utils\I18n;

	/** Загрузчик языковых констант. */
	class I18nFilesLoader implements iI18nFilesLoader {

		/** @var array языковые константы */
		private $langConstants = [];

		/** @var string[] список модулей, константы для которых нужно загрузить */
		private $moduleList = [];

		/** @var string языковой префикс */
		private $langPrefix;

		/** @var string|null $templatePath путь до директории с шаблонами сайта */
		private $templatePath;

		/** @inheritdoc */
		public function __construct(array $moduleList, $langPrefix) {
			$this->moduleList = $moduleList;
			$this->langPrefix = $langPrefix;
		}

		/** @inheritdoc */
		public function loadLangConstants() {
			return $this->loadDefaultLangConstants()
				->loadModuleListLangConstants()
				->loadTemplateLangConstants()
				->getLoadedConstants();
		}

		/** @inheritdoc */
		public function setTemplatePath($path) {
			if (!is_string($path) || !file_exists($path)) {
				throw new \ErrorException('Incorrect template path given');
			}

			$this->templatePath = $path;
			return $this;
		}

		/** @inheritdoc */
		public function getLoadedConstants() {
			return $this->langConstants;
		}

		/**
		 * Загружает языковые константы модулей
		 * @return $this
		 */
		private function loadModuleListLangConstants() {
			foreach ($this->getModuleList() as $name) {
				$this->loadModuleLangConstants($name);
			}

			return $this;
		}

		/**
		 * Загружает языковые константы из файлов вида lang.*.php для отдельного модуля.
		 * @param string $name название модуля
		 * @return $this
		 */
		private function loadModuleLangConstants($name) {
			return $this->loadModuleDefaultLangConstants($name)
				->loadModuleLangConstantsWithPrefix($name)
				->loadModuleExtensionLangConstants($name);
		}

		/**
		 * Загружает языковые константы по умолчанию из файлов вида lang.*.php для отдельного модуля.
		 * @param string $name название модуля
		 * @return $this;
		 */
		private function loadModuleDefaultLangConstants($name) {
			$path = $this->buildCommonFilePath(SYS_MODULES_PATH . $name);
			$this->loadLangConstantsFromFile($path, $name);
			return $this;
		}

		/**
		 * Загружает языковые константы для текущего языка из файлов вида lang.*.php для отдельного модуля.
		 * @param string $name название модуля
		 * @return $this;
		 */
		private function loadModuleLangConstantsWithPrefix($name) {
			$path = $this->buildLocalisedFilePath(SYS_MODULES_PATH . $name);
			$this->loadLangConstantsFromFile($path, $name);
			return $this;
		}

		/**
		 * Загружает языковые константы для шаблонов сайта из файлов вида lang.*.php. из расширения.
		 * @param string $name название модуля
		 * @return $this;
		 */
		private function loadModuleExtensionLangConstants($name) {
			$pattern = SYS_MODULES_PATH . $name . '/ext/lang.*.' . $this->getLanguagePrefix() . '.php';
			$pathList = glob($pattern);

			if (!is_array($pathList)) {
				return $this;
			}

			foreach ($pathList as $path) {
				$this->loadLangConstantsFromFile($path, $name);
			}

			return $this;
		}

		/**
		 * Загружает языковые константы из файла вида lang.*.php.
		 * Переменные $LANG_EXPORT и $C_LANG наполняются языковыми константами из файла.
		 * @param string $path путь до файла с константами
		 * @param string $name название модуля, если файл принадлежит модулю
		 */
		private function loadLangConstantsFromFile($path, $name = '') {
			if (!file_exists($path)) {
				return;
			}

			$C_LANG = [];
			$LANG_EXPORT = [];

			/** @noinspection PhpIncludeInspection */
			require $path;

			if (isset($LANG_EXPORT) && is_array($LANG_EXPORT)) {
				foreach ($LANG_EXPORT as $key => $value) {
					$this->langConstants[$key] = $value;
				}
			}

			if ($name === '') {
				return;
			}

			if (isset($C_LANG) && is_array($C_LANG)) {
				foreach ($C_LANG as $key => $value) {
					$this->langConstants[$name][$key] = $value;
				}
			}
		}

		/**
		 * Загружает языковые константы по умолчанию из файлов вида lang.*.php.
		 * @return $this
		 */
		private function loadDefaultLangConstants() {
			$path = $this->buildLocalisedFilePath(SYS_MODULES_PATH);

			if (!file_exists($path)) {
				$path = $this->buildCommonFilePath(SYS_MODULES_PATH);
			}

			$this->loadLangConstantsFromFile($path);
			return $this;
		}

		/**
		 * Загружает языковые константы из директории с шаблоном сайта
		 * @return $this
		 */
		private function loadTemplateLangConstants() {
			$path = $this->getTemplatePath();

			if ($path === null) {
				return $this;
			}

			$defaultPath = rtrim($path, '/') . '/classes/modules';

			$constantDirectoryPathList = [
				$defaultPath
			];

			$moduleList = $this->getModuleList();

			foreach ($moduleList as $name) {
				$constantDirectoryPathList[$name] = $defaultPath . '/' . $name;
			}

			foreach ($constantDirectoryPathList as $name => $directoryPath) {
				if (!file_exists($directoryPath)) {
					continue;
				}

				$filePath = $this->buildLocalisedFilePath($directoryPath);

				if (!file_exists($filePath)) {
					$filePath = $this->buildCommonFilePath($directoryPath);
				}

				$name = in_array($name, $moduleList) ? $name : '';
				$this->loadLangConstantsFromFile($filePath, $name);
			}

			return $this;
		}

		/**
		 * Формирует путь до файла с константами по умолчанию
		 * @param string $directoryPath путь до директории с файлом
		 * @return string
		 */
		private function buildCommonFilePath($directoryPath) {
			return rtrim($directoryPath, '/') . '/lang.php';
		}

		/**
		 * Формирует путь до файла с константами текущей локализации
		 * @param string $directoryPath путь до директории с файлом
		 * @return string
		 */
		private function buildLocalisedFilePath($directoryPath) {
			return rtrim($directoryPath, '/') . sprintf('/lang.%s.php', $this->getLanguagePrefix());
		}

		/**
		 * Возвращает языковой префикс
		 * @return string
		 */
		private function getLanguagePrefix() {
			return $this->langPrefix;
		}

		/**
		 * Возвращает список модулей
		 * @return string[]
		 */
		private function getModuleList() {
			return $this->moduleList;
		}

		/**
		 * Возвращает путь до директории с шаблонами сайта
		 * @return string|null
		 */
		private function getTemplatePath() {
			return $this->templatePath;
		}
	}
