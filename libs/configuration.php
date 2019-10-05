<?php

	use UmiCms\Service;

	/** Класс менеджера системных настроек. Синглтон. */
	class mainConfiguration implements iConfiguration {

		/** @var iConfiguration|null $instance экземпляр класса */
		private static $instance;

		/** @var array $ini значения настроек */
		private $ini = [];

		/** @var bool $edited значения настроек были изменены */
		private $edited = false;

		/** @var bool $readOnlyConfig заблокировано сохранение изменений конфигурационного файла */
		private $readOnlyConfig = false;

		/**
		 * @inheritdoc
		 * @return iConfiguration
		 */
		public static function getInstance($className = null) {
			if (self::$instance === null) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Конструктор
		 * @throws Exception
		 */
		private function __construct() {
			$this->loadConfig(CONFIG_INI_PATH);
		}

		/** Реализация синглтон */
		private function __clone() {
		}

		/** @inheritdoc */
		public function save() {
			if ($this->isChanged()) {
				$this->writeIni();
			}
		}

		/**
		 * Редактировались ли настройки в текущей сессии
		 * @return bool
		 */
		private function isChanged() {
			return $this->edited;
		}

		/** Помечает флаг редактирования настроек */
		private function markAsChanged() {
			$this->edited = true;
		}

		/**
		 * Возвращает путь до временного файла конфигурации
		 * @return string
		 */
		private function getConfigTemporaryPath() {
			return CONFIG_INI_PATH . '.temp';
		}

		/**
		 * Сохраняет настройки в config.ini
		 * @return bool удалось ли сохранить изменения
		 */
		private function writeIni() {
			if ($this->isReadOnlyConfig()) {
				return false;
			}

			$tempPath = $this->getConfigTemporaryPath();
			$tempResource = fopen($tempPath, 'a+');

			if ($tempResource === false) {
				trigger_error('Cannot open temp config file!', E_USER_WARNING);
				return false;
			}

			$resource = fopen(CONFIG_INI_PATH, 'a+');

			if ($resource === false) {
				fclose($tempResource);
				trigger_error('Cannot open config file!', E_USER_WARNING);
				return false;
			}

			if (!flock($resource, LOCK_EX)) {
				fclose($resource);
				fclose($tempResource);
				trigger_error("Cannot lock config file!", E_USER_WARNING);
				return false;
			}

			ftruncate($tempResource, 0);
			$configContent = $this->packConfigContent();
			$tempWrittenBytesLength = fwrite($tempResource, $configContent);
			$configContentBytesLength = strlen($configContent);
			$writtenBytesLength = 0;

			if ($tempWrittenBytesLength === $configContentBytesLength) {
				ftruncate($tempResource, 0);
				fclose($tempResource);
				ftruncate($resource, 0);
				$writtenBytesLength = fwrite($resource, $configContent);
				fflush($resource);
			} else {
				fclose($tempResource);
				trigger_error('Cannot write temp config file!', E_USER_WARNING);
			}

			if (!flock($resource, LOCK_UN)) {
				fclose($resource);
				trigger_error('Cannot unlock config file!', E_USER_WARNING);
				return false;
			}

			fclose($resource);
			clearstatcache();
			chmod(CONFIG_INI_PATH, 0666);

			if (!unlink($tempPath)) {
				trigger_error('Cannot delete temp config file!', E_USER_WARNING);
				return false;
			}

			if ($writtenBytesLength !== $configContentBytesLength) {
				trigger_error('Cannot write config file!', E_USER_WARNING);
				return false;
			}

			return true;
		}

		/**
		 * Упаковывает содержимое конфигурации
		 * @return string
		 */
		private function packConfigContent() {
			$iniString = '';

			foreach ($this->ini as $section => $variables) {
				if (empty($variables)) {
					continue;
				}

				$iniString .= "[{$section}]" . PHP_EOL;

				foreach ($variables as $name => $value) {
					if (is_array($value)) {
						foreach ($value as $valueItem) {
							$valueItem = ($valueItem !== '') ? '"' . $valueItem . '"' : '';
							$iniString .= "{$name}[] = {$valueItem}" . PHP_EOL;
						}
					} else {
						$value = ($value !== '') ? '"' . $value . '"' : '';
						$iniString .= "{$name} = {$value}" . PHP_EOL;
					}
				}
				$iniString .= PHP_EOL;
			}

			return $iniString;
		}

		/** @inheritdoc */
		public function getParsedIni() {
			return $this->ini;
		}

		/** @inheritdoc */
		public function get($section, $variable) {
			if (isset($this->ini[$section]) && isset($this->ini[$section][$variable])) {
				$value = $this->ini[$section][$variable];
				$value = $this->removeSingeQuotes($value);

				if ($section == 'session' && $variable == 'active-lifetime' && $value < 1) {
					$value = 1440;
				}

				return $value;
			}

			return null;
		}

		/** @inheritdoc */
		public function set($section, $variable, $value) {
			if (!isset($this->ini[$section])) {
				$this->ini[$section] = [];
			}

			$isChanged = $value != $this->get($section, $variable);

			if ($value === null && isset($this->ini[$section][$variable])) {
				unset($this->ini[$section][$variable]);
			} else {
				if ($section == 'session' && $variable == 'active-lifetime' && $value < 1) {
					$value = 1440;
				}

				$this->ini[$section][$variable] = $value;
			}

			if ($isChanged) {
				$this->markAsChanged();
			}
		}

		/** @inheritdoc */
		public function getList($section) {
			if (isset($this->ini[$section]) && is_array($this->ini[$section])) {
				return array_keys($this->ini[$section]);
			}

			return null;
		}

		/** @inheritdoc */
		public function includeParam($key, array $params = null) {
			static $defaultParams = [];
			$path = $this->get('includes', $key);

			if (mb_strpos($path, '{') !== false) {
				if (class_exists('\UmiCms\Service') && !count($defaultParams)) {
					$defaultParams['lang'] = Service::LanguageDetector()->detectPrefix();
					$defaultParams['domain'] = Service::DomainDetector()->detectHost();
				}

				$params = ($params === null) ? $defaultParams : array_merge($params, $defaultParams);

				foreach ($params as $i => $v) {
					$path = str_replace('{' . $i . '}', $v, $path);
				}
			}

			if (mb_substr($path, 0, 2) == '~/') {
				$path = CURRENT_WORKING_DIR . mb_substr($path, 1);
			}

			return $path;
		}

		/** @inheritdoc */
		public function setReadOnlyConfig($flag = true) {
			$this->readOnlyConfig = (bool) $flag;
			return $this;
		}

		/** @inheritdoc */
		public function loadConfig($configPath) {
			if (!is_readable($configPath)) {
				throw new Exception("Can't find configuration file: $configPath");
			}

			$config = parse_ini_file($configPath, true);

			if (empty($this->ini)) {
				$this->ini = $config;
			} else {
				$this->ini = $this->mergeCustomConfig($this->ini, $config);
			}

			if (isset($this->ini['session']) && isset($this->ini['session']['active-lifetime']) &&
				$this->ini['session']['active-lifetime'] < 1) {
				$this->ini['session']['active-lifetime'] = 1440;
			}

			return $this->replaceConfigToFastCgiParams()
				->defineStateDirPathConstants();
		}

		/**
		 * Производит слияние базовой и кастомной конфигурации
		 * @param array $baseConfig
		 *
		 * [
		 *      'section' => [
		 *          'option' => 'value'
		 *      ]
		 * ]
		 *
		 * @param array $customConfig
		 *
		 * [
		 *      'section' => [
		 *          'option' => 'value'
		 *      ]
		 * ]
		 *
		 * @return array
		 */
		private function mergeCustomConfig(array $baseConfig, array $customConfig) {
			foreach ($customConfig as $section => $optionList) {

				if (!isset($baseConfig[$section])) {
					$baseConfig[$section] = $optionList;
					continue;
				}

				$baseConfig[$section] = array_merge($baseConfig[$section], $optionList);
			}

			return $baseConfig;
		}

		/**
		 * Удаляет одинарные кавычки из значений параметров config.ini
		 * @param mixed $value значение параметра
		 * @return mixed
		 */
		private function removeSingeQuotes($value) {
			if (is_string($value)) {
				return trim($value, "'");
			}

			if (is_array($value)) {
				return array_map([$this, 'removeSingeQuotes'], $value);
			}

			return $value;
		}

		/**
		 * Переопределяет значения параметров конфига переданными от вебсервера
		 * @return mainConfiguration
		 */
		private function replaceConfigToFastCgiParams() {
			foreach ($_SERVER as $key => $val) {
				if (mb_strpos($key, 'cp_') !== false) {
					$key = str_replace('cp_', '', $key);
					$key = str_replace('_', '.', $key);
					$key = explode('.', $key, 2);
					$this->ini[$key[0]][$key[1]] = $val;
				}
			}

			return $this;
		}

		/**
		 * Объявляет глобальные константы с путями до директорий, в которых хранится некоторое состояние системы:
		 *
		 * 1) USER_FILES_PATH - директория с пользовательскими файлами;
		 * 2) USER_IMAGES_PATH - директория с пользовательскими изображениями;
		 * 3) ERRORS_LOGS_PATH - директория с логом исключений;
		 * 4) SYS_TEMP_PATH - директория со временными файлами;
		 *
		 * @return mainConfiguration
		 */
		private function defineStateDirPathConstants() {
			$variables = [
				'user-files-path' => '/files',
				'user-images-path' => '/images',
				'errors-logs-path' => '/errors/logs',
				'sys-temp-path' => '/sys-temp'
			];

			foreach ($variables as $key => $value) {
				$path = CURRENT_WORKING_DIR . $value;

				if (isset($this->ini['includes'][$key]) && $this->ini['includes'][$key] !== '') {
					$path = $this->ini['includes'][$key];
				}

				if (mb_strpos($path, '~') !== false) {
					$path = CURRENT_WORKING_DIR . mb_substr($path, 1);
				}

				$constantName = mb_strtoupper(str_replace('-', '_', $key));

				if (!defined($constantName)) {
					define($constantName, $path);
				}
			}

			return $this;
		}

		/**
		 * Определяет заблокировано ли сохранение изменений конфигурационного файла
		 * @return bool
		 */
		private function isReadOnlyConfig() {
			return $this->readOnlyConfig;
		}

		/** @deprecated */
		public function __destruct() {
		}
	}
