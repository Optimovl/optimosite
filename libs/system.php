<?php

	use UmiCms\Service;

	/** Глобальные функции */

	/**
	 * Разрешено ли использование запрошенных метода/страницы?
	 * @param string $module модуль
	 * @param mixed $method метод
	 * @param mixed $elementId идентификатор страницы
	 * @return bool
	 */
	function system_is_allowed($module, $method = false, $elementId = false) {
		static $cache = [];
		static $userId = false;

		$hash = md5($module . $method . $elementId);
		if (array_key_exists($hash, $cache)) {
			return $cache[$hash];
		}

		if (in_array($module, ['system', 'core', 'custom'])) {
			return $cache[$hash] = true;
		}

		if (!$userId) {
			$userId = Service::Auth()->getUserId();
		}

		$umiPermissions = permissionsCollection::getInstance();

		if ($umiPermissions->isSv($userId)) {
			return $cache[$hash] = true;
		}

		if ($elementId) {
			list($readAllowed, $editAllowed) = $umiPermissions->isAllowedObject($userId, $elementId);

			if (contains($method, 'edit')) {
				return $cache[$hash] = (bool) $editAllowed;
			}

			return $cache[$hash] = (bool) $readAllowed;
		}

		if ($method) {
			return $cache[$hash] = (bool) $umiPermissions->isAllowedMethod($userId, $module, $method);
		}

		if ($module) {
			return $cache[$hash] = (bool) $umiPermissions->isAllowedModule($userId, $module);
		}

		return false;
	}

	/**
	 * Текущий скин админки
	 * @return mixed
	 */
	function system_get_skinName() {
		static $skinName;

		if ($skinName) {
			return $skinName;
		}

		$config = mainConfiguration::getInstance();
		$controller = cmsController::getInstance();

		$casualSkins = $config->getList('casual-skins');
		$methodName = $controller->getCurrentModule() . '::' . $controller->getCurrentMethod();

		foreach ($casualSkins as $casualSkinName) {
			if (in_array($methodName, $config->get('casual-skins', $casualSkinName))) {
				return $skinName = $casualSkinName;
			}
		}

		$skins = $config->get('system', 'skins');
		$cookieJar = Service::CookieJar();

		if (isset($_GET['skin_sel']) || isset($_POST['skin_sel'])) {
			if (($skin_sel = getArrayKey($_GET, 'skin_sel')) === null) {
				$skin_sel = getArrayKey($_POST, 'skin_sel');
			}

			$cookieJar->set('skin_sel', $skin_sel, time() + 3600 * 24 * 365);

			if (in_array($skinName, $skins)) {
				return $skinName = $skin_sel;
			}
		}

		if ($cookieJar->get('skin_sel')) {
			if (in_array($cookieJar->get('skin_sel'), $skins)) {
				return $skinName = $cookieJar->get('skin_sel');
			}
		}

		return $skinName = $config->get('system', 'default-skin');
	}

	/**
	 * Экземпляр виртуального модуля (core, system, custom)
	 * @param string $moduleName название модуля
	 * @return mixed
	 */
	function system_buildin_load($moduleName) {
		static $moduleClasses = [];

		if (isset($moduleClasses[$moduleName])) {
			return $moduleClasses[$moduleName];
		}

		$config = mainConfiguration::getInstance();
		$modulePath = $config->includeParam('system.virtual-modules') . getCompatibleModulesPath() . $moduleName . '.php';

		if (file_exists($modulePath)) {
			require $modulePath;
			if (class_exists($moduleName)) {
				return $moduleClasses[$moduleName] = new $moduleName;
			}
		}

		return false;
	}

	/**
	 * Обрезать строку до указанной длины, добавляя $endingString в конце
	 * @param string $string строка
	 * @param string $length максимальная длина результата
	 * @param string $endingString строка в конце
	 * @param bool $stripTags убирать ли html-теги?
	 * @return string
	 */
	function truncStr($string, $length = '50', $endingString = '...', $stripTags = false) {
		$result = $string;

		if ($stripTags) {
			$result = html_entity_decode(strip_tags($result), ENT_QUOTES, 'UTF-8');
		}

		if ($length <= 0) {
			return '';
		}

		if (mb_strlen($string) > $length) {
			$length -= mb_strlen($endingString);
			$result = mb_substr($result, 0, $length + 1);
			$result = preg_replace('/\s+([^\s]+)?$/i', '', $result) . $endingString;
		}

		return $result;
	}

	/**
	 * Дата в формате UNIX TIMESTAMP
	 *
	 * Примеры:
	 * toTimeStamp("1970-06-15") === 14245200
	 * toTimeStamp("15 июня 1970 года") === 14245200
	 * toTimeStamp("15:30 15 июня 1970 года") === 14301000
	 * toTimeStamp("завтра") === 1470254400
	 *
	 * @param string $date дата в произвольном формате
	 * @return int
	 */
	function toTimeStamp($date) {
		if (is_numeric($date)) {
			return $date;
		}

		$day = '';
		$month = '';
		$year = '';
		$hours = '';
		$minutes = '';

		$date = trim($date);

		if ($date == 'сейчас') {
			return time();
		}

		$date = str_replace('-', ' ', $date);
		$date = str_replace(',', ' ', $date);
		$date = str_replace("\\'", ' ', $date);

		$timeRegex = "/\d{2}\:\d{2}/";

		if (preg_match($timeRegex, $date, $matches)) {
			$time = $matches[0];
			preg_replace($timeRegex, '', $date);
			list($hours, $minutes) = explode(':', $time);
		}

		$spaceRegex = "[ \.\-\/\\\\]{1,10}";

		$date = preg_replace("/(\d{4}){$spaceRegex}(\d{2}){$spaceRegex}(\d{2})/im", "^\\3^ !\\2! ?\\1?", $date);
		$date = preg_replace("/(\d{1,2}){$spaceRegex}(\d{1,2}){$spaceRegex}(\d{2,4})/im", "^\\1^ !\\2! ?\\3?", $date);

		$months = [
			'январь',
			'февраль',
			'март',
			'апрель',
			'май',
			'июнь',
			'июль',
			'август',
			'сентябрь',
			'октябрь',
			'ноябрь',
			'декабрь'
		];

		$monthsAccusative = [
			'января',
			'февраля',
			'марта',
			'апреля',
			'мая',
			'июня',
			'июля',
			'августа',
			'сентября',
			'октября',
			'ноября',
			'декабря'
		];

		$monthsShort = [
			'янв',
			'фев',
			'мар',
			'апр',
			'май',
			'июн',
			'июл',
			'авг',
			'сен',
			'окт',
			'ноя',
			'дек'
		];

		$monthsShortEn = [
			'jan',
			'feb',
			'mar',
			'apr',
			'may',
			'jun',
			'jul',
			'aug',
			'sep',
			'oct',
			'nov',
			'dec'
		];

		$monthsTo = [
			'01',
			'02',
			'03',
			'04',
			'05',
			'06',
			'07',
			'08',
			'09',
			'10',
			'11',
			'12'
		];

		foreach ($months as $k => $v) {
			$months[$k] = '/' . $v . '/i';
		}

		foreach ($monthsAccusative as $k => $v) {
			$monthsAccusative[$k] = '/' . $v . '/i';
		}

		foreach ($monthsShort as $k => $v) {
			$monthsShort[$k] = '/' . $v . '/i';
		}

		foreach ($monthsShortEn as $k => $v) {
			$monthsShortEn[$k] = '/' . $v . '/i';
		}

		foreach ($monthsTo as $k => $v) {
			$monthsTo[$k] = ' !' . $v . '! ';
		}

		$date = preg_replace($months, $monthsTo, $date);
		$date = preg_replace($monthsAccusative, $monthsTo, $date);
		$date = preg_replace($monthsShort, $monthsTo, $date);
		$date = preg_replace($monthsShortEn, $monthsTo, $date);

		$years = [
			'/(\d{2,4})[ ]*года/i',
			'/(\d{2,4})[ ]*год/i',
			'/(\d{2,4})[ ]*г/i',
			'/(\d{4})/i',
		];

		$date = preg_replace($years, "?\\1?", $date);
		$date = preg_replace("/[^!^\?^\d](\d{1,2})[^!^\?^\d]/i", "^\\1^", ' ' . $date . ' ');

		if (preg_match("/\^(\d{1,2})\^/", $date, $matches)) {
			$day = $matches[1];
			if (mb_strlen($day) == 1) {
				$day = '0' . $day;
			}
		}

		if (preg_match("/!(\d{1,2})!/", $date, $matches)) {
			$month = $matches[1];
			if (mb_strlen($month) == 1) {
				$month = '0' . $month;
			}
		}

		if (preg_match("/\?(\d{2,4})\?/", $date, $matches)) {
			$year = $matches[1];
			if (mb_strlen($year) == 2) {
				$leadingDigit = (int) mb_substr($year, 0, 1);
				if ($leadingDigit >= 0 && $leadingDigit <= 4) {
					$year = '20' . $year;
				} else {
					$year = '19' . $year;
				}
			}
		}

		if ($day > 31) {
			$temp = $year;
			$year = $day;
			$day = $temp;
		}

		if ($month > 12) {
			$temp = $month;
			$month = $day;
			$day = $temp;
			unset($temp);
		}

		$tds = trim(mb_strtolower($date));

		switch ($tds) {
			case 'сегодня':
				$ts = time();
				$year = date('Y', $ts);
				$month = date('m', $ts);
				$day = date('d', $ts);
				break;

			case 'завтра':
				$ts = time() + (3600 * 24);
				$year = date('Y', $ts);
				$month = date('m', $ts);
				$day = date('d', $ts);
				break;

			case 'вчера':
				$ts = time() - (3600 * 24);
				$year = date('Y', $ts);
				$month = date('m', $ts);
				$day = date('d', $ts);
				break;

			case 'послезавтра':
				$ts = time() + (3600 * 48);
				$year = date('Y', $ts);
				$month = date('m', $ts);
				$day = date('d', $ts);
				break;

			case 'позавчера':
				$ts = time() - (3600 * 48);
				$year = date('Y', $ts);
				$month = date('m', $ts);
				$day = date('d', $ts);
				break;
		}

		if (!$day) {
			$tds = str_replace([$year, $month], '', $date);
			preg_match("/(\d{1,2})/", $tds, $matches);
			$day = isset($matches[1]) ? $matches[1] : null;
		}

		if (!$month && !$day && !$year) {
			return 0;
		}

		if ($day && !$month) {
			$month = $day;
			$day = 0;
		}

		return $timestamp = mktime((int) $hours, (int) $minutes, 0, (int) $month, (int) $day, (int) $year);
	}

	/**
	 * Распарсить вызовы коротких макросов поля "контент" у страницы
	 * @param string $content контент
	 * @param mixed $elementId идентификатор страницы
	 * @param mixed $objectId идентификатор объекта
	 * @param array $scopeVariables переменные области видимости
	 * @return mixed
	 */
	function system_parse_short_calls($content, $elementId = false, $objectId = false, $scopeVariables = []) {
		if (!contains($content, '%')) {
			return $content;
		}

		$cmsController = cmsController::getInstance();
		$umiObjects = umiObjectsCollection::getInstance();

		$shouldDumpScope = contains($content, '%scope%');
		$element = null;
		$object = null;

		if ($elementId === false && $objectId === false) {
			$elementId = $cmsController->getCurrentElementId();
		}

		if (contains($content, 'id%')) {
			$content = str_replace('%id%', $elementId, $content);
			$content = str_replace('%pid%', $cmsController->getCurrentElementId(), $content);
		}

		if ($elementId !== false) {
			if (!($element = umiHierarchy::getInstance()->getElement($elementId))) {
				return $content;
			}

			$object = $element->getObject();
		}

		if ($objectId !== false) {
			if (!($object = $umiObjects->getObject($objectId))) {
				return $content;
			}
		}

		if (!$object) {
			return $content;
		}

		$objectTypeId = $object->getTypeId();
		$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);

		if ($shouldDumpScope) {
			$fields = $objectType->getAllFields();
			foreach ($fields as $field) {
				$name = $field->getName();
				$scopeVariables[$name] = $object->getValue($name);
			}
			$content = str_replace('%scope%', system_print_template_scope($scopeVariables), $content);
		}

		if (preg_match_all("/%([A-z0-9\-_]*)%/", $content, $matches)) {
			foreach ($matches[1] as $objPropName) {
				if ($objectType->getFieldId($objPropName)) {
					$value = $object->getValue($objPropName);

					if (is_object($value)) {
						if ($value instanceof umiDate) {
							$value = $value->getFormattedDate('U');
						}

						if ($value instanceof umiFile) {
							$value = $value->getFilePath(true);
						}

						if ($value instanceof umiHierarchy) {
							$value = $value->getName();
						}
					}

					if (is_array($value)) {
						$count = umiCount($value);
						$stringValue = '';

						for ($i = 0; $i < $count; $i++) {
							$currentValue = $value[$i];

							if (is_numeric($currentValue)) {
								$obj = $umiObjects->getObject($currentValue);

								if ($obj) {
									$currentValue = $obj->getName();
									unset($obj);
								} else {
									continue;
								}
							}

							if ($currentValue instanceof iUmiHierarchyElement) {
								$currentValue = $currentValue->getName();
							}

							$stringValue .= $currentValue;
							if ($i < ($count - 1)) {
								$stringValue .= ', ';
							}
						}

						$value = $stringValue;
					}

					if (contains($value, '%')) {
						$value = def_module::parseTPLMacroses($value);
					}

					$content = str_replace('%' . $objPropName . '%', $value, $content);
				}
			}
		}

		if (contains($content, 'id%')) {
			$content = str_replace('%id%', $elementId, $content);
			$content = str_replace('%pid%', $cmsController->getCurrentElementId(), $content);
		}

		return $content;
	}

	/**
	 * Область видимости шаблона со значением всех переменных
	 * @param array $scopeVariables переменные области видимости
	 * @param bool $scopeName не используется
	 * @return mixed
	 */
	function system_print_template_scope($scopeVariables, $scopeName = false) {
		list($block, $varLine, $macroLine) = def_module::loadTemplates(
			'system/reflection',
			'scope_dump_block',
			'scope_dump_line_variable',
			'scope_dump_line_macro'
		);

		$assembledLines = '';

		foreach ($scopeVariables as $name => $value) {
			if ($name == '#meta') {
				continue;
			}

			if (is_array($value)) {
				$tmp = str_replace('%name%', $name, $macroLine);
			} else {
				$tmp = $varLine;
				$tmp = str_replace('%name%', $name, $tmp);
				$tmp = str_replace('%type%', gettype($value), $tmp);
				$tmp = str_replace('%value%', htmlspecialchars($value), $tmp);
			}

			$assembledLines .= $tmp;
		}

		if (isset($scopeVariables['#meta'])) {
			$scopeName = isset($scopeVariables['#meta']['name']) ? $scopeVariables['#meta']['name'] : '';
			$scopeFile = isset($scopeVariables['#meta']['file']) ? $scopeVariables['#meta']['file'] : '';
		} else {
			$scopeName = '';
			$scopeFile = '';
		}

		$block = str_replace('%lines%', $assembledLines, $block);
		$block = str_replace('%block_name%', $scopeName, $block);
		$block = str_replace('%block_file%', $scopeFile, $block);
		$block = preg_replace('/%[A-z0-9_]+%/i', '', $block);

		return $block;
	}

	/**
	 * Возвращает является ли текущий режим — демо режимом.
	 * @return bool
	 */
	function isDemoMode() {
		$versionLine = mainConfiguration::getInstance()->get('system', 'version-line');
		$issetVersionLine = $versionLine !== null;

		return ($issetVersionLine && $versionLine === 'demo');
	}

	/**
	 * Проверяет работает ли система в режиме "Крона"
	 * @return bool результат проверки
	 */
	function isCronMode() {
		return defined(iConfiguration::CRON_MODE) && constant(iConfiguration::CRON_MODE);
	}

	/**
	 * Возвращает режим работы "Крона"
	 * @return mixed
	 */
	function getCronMode() {
		if (!isCronMode()) {
			return false;
		}

		return constant(iConfiguration::CRON_MODE);
	}

	/**
	 * Проверяет работает ли система в режиме "Крона" и запущена через консоль
	 * @return bool результат проверки
	 */
	function isCronCliMode() {
		return getCronMode() == iConfiguration::CLI_MODE;
	}

	/**
	 * Проверяет работает ли система в режиме "Крона" и запущена по http
	 * @return bool результат проверки
	 */
	function isCronHttpMode() {
		return getCronMode() == iConfiguration::HTTP_MODE;
	}

	/**
	 * Алиас для функции isDemoMode(). Оставлен для обратной совместимости.
	 * @return bool
	 * @deprecated
	 */
	function is_demo() {
		return isDemoMode();
	}

	/**
	 * Кодировка строки
	 * @param string $string строка
	 * @return string
	 */
	function detectCharset($string) {
		if (preg_match("/[\x{0000}-\x{FFFF}]+/u", $string)) {
			return 'UTF-8';
		}

		$encoding = 'CP1251';

		if (!function_exists('iconv')) {
			return $encoding;
		}

		$cyrillicEncodings = [
			'CP1251',
			'ISO-8859-5',
			'KOI8-R',
			'UTF-8',
			'CP866'
		];

		if (function_exists('mb_detect_encoding')) {
			return mb_detect_encoding($string, implode(', ', $cyrillicEncodings));
		}

		return 'UTF-8';
	}

	/**
	 * Возможно ли записать количество байт $bytes в пользовательские директории?
	 * @param mixed $bytes количество байт
	 * @return bool
	 */
	function checkAllowedDiskSize($bytes = false) {
		$maxFilesSize = mainConfiguration::getInstance()->get('system', 'quota-files-and-images');
		return isAllowedToWriteBytes($bytes, getResourcesDirs(), $maxFilesSize);
	}

	/**
	 * Размер директорий на сервере в байтах
	 * @param mixed $dirs список директорий
	 * @return int
	 */
	function getBusyDiskSize($dirs = '__default__') {
		if ($dirs === '__default__') {
			$dirs = [USER_IMAGES_PATH, USER_FILES_PATH];
		}

		clearstatcache();
		$busySize = 0;

		if (!is_array($dirs)) {
			return 0;
		}

		foreach ($dirs as $dir) {
			if (!contains(CURRENT_WORKING_DIR, $dir)) {
				$busySize += getDirSize($dir);
			} else {
				$busySize += getDirSize(CURRENT_WORKING_DIR . $dir);
			}
		}

		return $busySize;
	}

	/**
	 * Процент занятого дискового пространства в пользовательских директориях для файлов и изображений
	 * @param mixed $dirs не используется
	 * @return int
	 */
	function getBusyDiskPercent($dirs = '__default__') {
		$maxFilesSize = mainConfiguration::getInstance()->get('system', 'quota-files-and-images');
		return getOccupiedDiskPercent(getResourcesDirs(), $maxFilesSize);
	}

	/**
	 * Возможно ли записать количество байт $bytes в директории в соответствии с ограничением?
	 * @param int $bytes количество байт для записи
	 * @param array $dirs список директорий
	 * @param string $quota ограничение дискового пространства
	 * @return bool
	 */
	function isAllowedToWriteBytes($bytes, $dirs, $quota = '0') {
		if (!$bytes) {
			return false;
		}

		if ($quota == 0) {
			return true;
		}

		$maxFilesSize = getBytesFromString($quota);
		$busySize = getBusyDiskSize($dirs);

		return $maxFilesSize >= $busySize + $bytes;
	}

	/**
	 * Процент занятого дискового пространства в директориях
	 * @param array $dirs список директорий
	 * @param string $quota ограничение дискового пространства
	 * @return float|int
	 */
	function getOccupiedDiskPercent($dirs, $quota = '0') {
		if ($quota == 0 || !is_array($dirs)) {
			return 0;
		}

		$maxFilesSize = getBytesFromString($quota);
		$busySize = getBusyDiskSize($dirs);

		return ceil($busySize / $maxFilesSize * 100);
	}

	/**
	 * Список путей до директорий, в которых хранятся пользовательские файлы
	 * @return array
	 */
	function getResourcesDirs() {
		return [USER_IMAGES_PATH, USER_FILES_PATH];
	}

	/**
	 * Путь до директории, в которую загружаются файлы из форм
	 * @return array
	 */
	function getUploadsDir() {
		return [SYS_TEMP_PATH . '/uploads'];
	}

	/**
	 * Конвертировать строку в байты с учетом единицы измерения (кило, мега, гига)
	 * @example getBytesFromString("10MB") === 10485760
	 * @param string $string строка
	 * @return int|mixed
	 */
	function getBytesFromString($string) {
		if (empty($string)) {
			return 0;
		}

		$string = str_replace(' ', '', mb_strtolower($string));
		$bytes = $string;

		if (mb_strpos($string, 'kb')) {
			$bytes = (int) str_replace('kb', '', $string) * 1024;
		}

		if (mb_strpos($string, 'k')) {
			$bytes = (int) str_replace('k', '', $string) * 1024;
		}

		if (mb_strpos($string, 'mb')) {
			$bytes = (int) str_replace('mb', '', $string) * 1024 * 1024;
		}

		if (mb_strpos($string, 'm')) {
			$bytes = (int) str_replace('m', '', $string) * 1024 * 1024;
		}

		if (mb_strpos($string, 'gb')) {
			$bytes = (int) str_replace('gb', '', $string) * 1024 * 1024 * 1024;
		}

		if (mb_strpos($string, 'g')) {
			$bytes = (int) str_replace('g', '', $string) * 1024 * 1024 * 1024;
		}

		return $bytes;
	}

	/**
	 * Размер папки, полученный средствами php
	 * @param string $path путь до директории
	 * @param int $startTime время запуска
	 * @return bool|int
	 */
	function getDirSizePhp($path, $startTime = 0) {
		$size = 0;
		$maxTime = (int) ini_get('max_execution_time');
		$maxTime = $maxTime > 0 ? $maxTime * 0.5 : 2;

		$startTime = $startTime === 0 ? microtime(true) : $startTime;

		if (mb_substr($path, -1, 1) !== DIRECTORY_SEPARATOR) {
			$path .= DIRECTORY_SEPARATOR;
		}

		if (is_file($path)) {
			return filesize($path);
		}

		if (!is_dir($path)) {
			return false;
		}

		$files = glob(rtrim($path, '/') . '/*', GLOB_NOSORT);
		$files = $files ?: [];

		foreach ($files as $file) {
			if (microtime(true) - $startTime >= $maxTime) {
				return $size;
			}
			$size += is_file($file) ? filesize($file) : getDirSizePhp($file, $startTime);
		}

		return $size;
	}

	/**
	 * Размер папки, полученный с помощью консольных програм
	 * @param string $path путь до директории
	 * @return string
	 */
	function getDirSizeConsole($path) {
		$path = realpath($path);

		if (contains(mb_strtolower(PHP_OS), 'linux')) {
			$io = popen('/usr/bin/du -sk -b ' . $path, 'r');
			$size = fgets($io, 4096);
			$size = mb_substr($size, 0, mb_strpos($size, "\t"));
			pclose($io);
		} else {
			if (contains(mb_strtolower(PHP_OS), 'win') && class_exists('com')) {
				$obj = new COM ('scripting.filesystemobject');
				if (is_object($obj)) {
					$ref = $obj->getfolder($path);
					$obj = null;
					$size = $ref->size;
				} else {
					$size = getDirSizePhp($path);
				}
			} else {
				$size = getDirSizePhp($path);
			}
		}

		return $size;
	}

	/**
	 * Дисковое пространство в байтах, занятое директорией $path
	 * @param string $path путь до директории
	 * @return int
	 */
	function getDirSize($path) {
		$disabled_func = ini_get('disable_functions');

		if (!contains($disabled_func, 'popen')) {
			$size = getDirSizeConsole($path);
		} else {
			$size = getDirSizePhp($path);
		}
		return $size;
	}

	/**
	 * Доступен ли файл на чтение?
	 * @param string $path путь до файла
	 * @param array $extension допустимые расширения файла
	 * @return bool
	 */
	function checkFileForReading($path, $extension = []) {
		$path = realpath($path);

		if (!file_exists($path)) {
			return false;
		}

		$path = str_replace("\\", '/', $path);
		$pathInfo = pathinfo($path);

		if (isset($pathInfo)) {
			if ($pathInfo['filename'] == '.htaccess' || $pathInfo['filename'] == '.htpasswd') {
				return false;
			}
		}

		return !(umiCount($extension) && !in_array($pathInfo['extension'], $extension));
	}

	/** @deprecated */
	function system_is_mobile() {
		return Service::Request()->isMobile();
	}

	/** @deprecated используйте translit::convert() */
	function translit($input, $mode = 'R_TO_E') {
		$rusBig = [
			'Э',
			'Ч',
			'Ш',
			'Ё',
			'Ё',
			'Ж',
			'Ю',
			'Ю',
			"\Я",
			"\Я",
			'А',
			'Б',
			'В',
			'Г',
			'Д',
			'Е',
			'З',
			'И',
			'Й',
			'К',
			'Л',
			'М',
			'Н',
			'О',
			'П',
			'Р',
			'С',
			'Т',
			'У',
			'Ф',
			'Х',
			'Ц',
			'Щ',
			'Ъ',
			'Ы',
			'Ь'
		];
		$rusSmall = [
			'э',
			'ч',
			'ш',
			'ё',
			'ё',
			'ж',
			'ю',
			'ю',
			'я',
			'я',
			'а',
			'б',
			'в',
			'г',
			'д',
			'е',
			'з',
			'и',
			'й',
			'к',
			'л',
			'м',
			'н',
			'о',
			'п',
			'р',
			'с',
			'т',
			'у',
			'ф',
			'х',
			'ц',
			'щ',
			'ъ',
			'ы',
			'ь'
		];
		$engBig = [
			"E\'",
			'CH',
			'SH',
			'YO',
			'JO',
			'ZH',
			'YU',
			'JU',
			'YA',
			'JA',
			'A',
			'B',
			'V',
			'G',
			'D',
			'E',
			'Z',
			'I',
			'J',
			'K',
			'L',
			'M',
			'N',
			'O',
			'P',
			'R',
			'S',
			'T',
			'U',
			'F',
			'H',
			'C',
			'W',
			'~',
			'Y',
			"\'"
		];
		$engSmall = [
			"e\'",
			'ch',
			'sh',
			'yo',
			'jo',
			'zh',
			'yu',
			'ju',
			'ya',
			'ja',
			'a',
			'b',
			'v',
			'g',
			'd',
			'e',
			'z',
			'i',
			'j',
			'k',
			'l',
			'm',
			'n',
			'o',
			'p',
			'r',
			's',
			't',
			'u',
			'f',
			'h',
			'c',
			'w',
			'~',
			'y',
			"\'"
		];
		$rusRegBig = [
			'Э',
			'Ч',
			'Ш',
			'Ё',
			'Ё',
			'Ж',
			'Ю',
			'Ю',
			'Я',
			'Я',
			'А',
			'Б',
			'В',
			'Г',
			'Д',
			'Е',
			'З',
			'И',
			'Й',
			'К',
			'Л',
			'М',
			'Н',
			'О',
			'П',
			'Р',
			'С',
			'Т',
			'У',
			'Ф',
			'Х',
			'Ц',
			'Щ',
			'Ъ',
			'Ы',
			'Ь'
		];
		$rusRegSmall = [
			'э',
			'ч',
			'ш',
			'ё',
			'ё',
			'ж',
			'ю',
			'ю',
			'я',
			'я',
			'а',
			'б',
			'в',
			'г',
			'д',
			'е',
			'з',
			'и',
			'й',
			'к',
			'л',
			'м',
			'н',
			'о',
			'п',
			'р',
			'с',
			'т',
			'у',
			'ф',
			'х',
			'ц',
			'щ',
			'ъ',
			'ы',
			'ь'
		];
		$engRegBig = [
			"E'",
			'CH',
			'SH',
			'YO',
			'JO',
			'ZH',
			'YU',
			'JU',
			'YA',
			'JA',
			'A',
			'B',
			'V',
			'',
			'D',
			'E',
			'Z',
			'I',
			'J',
			'K',
			'L',
			'M',
			'N',
			'O',
			'P',
			'R',
			'S',
			'T',
			'U',
			'F',
			'H',
			'C',
			'W',
			'~',
			'Y',
			"'"
		];
		$engRegSmall = [
			"e'",
			'ch',
			'sh',
			'yo',
			'jo',
			'zh',
			'yu',
			'ju',
			'ya',
			'ja',
			'a',
			'b',
			'v',
			'',
			'd',
			'e',
			'z',
			'i',
			'j',
			'k',
			'l',
			'm',
			'n',
			'o',
			'p',
			'r',
			's',
			't',
			'u',
			'f',
			'h',
			'c',
			'w',
			'~',
			'y',
			"'"
		];

		$textar = $input;
		$res = $input;

		if ($mode == 'E_TO_R') {
			if ($textar) {
				for ($i = 0; $i < umiCount($engRegSmall); $i++) {
					$textar = str_replace($engRegSmall[$i], $rusSmall[$i], $textar);
				}
				for ($i = 0; $i < umiCount($engRegBig); $i++) {
					$textar = str_replace($engRegBig[$i], $rusBig[$i], $textar);
					$textar = str_replace($engRegBig[$i], $rusBig[$i], $textar);
				}
				$res = $textar;
			}
		}

		if ($mode == 'R_TO_E') {
			if ($textar) {
				$textar = str_replace($rusRegSmall, $engSmall, $textar);
				$textar = str_replace($rusRegBig, $engSmall, $textar);
				$res = mb_strtolower($textar);
			}
		}

		$from = ['/', "\\", "'", "\t", "\r\n", "\n", '"', ' ', '?', '.'];
		$to = ['', '', '', '', '', '', '', '_', '', ''];

		$res = str_replace($from, $to, $res);

		$res = preg_replace('/[ ]+/', '_', $res);
		return $res;
	}

	/** @deprecated */
	function system_get_tpl($mode = 'default') {
		$config = mainConfiguration::getInstance();
		$controller = cmsController::getInstance();
		$dirPath = '';
		$fileName = '';
		$filePath = '';
		if (Service::Request()->isAdmin() && $mode == 'current') {
			$type = 'xslt';
			$className = 'xslAdminTemplater';
			$fileName = 'main.xsl';
			$dirPath = $config->includeParam('templates.skins', ['skin' => system_get_skinName()]);

			$permissions = permissionsCollection::getInstance();
			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$isAllowed = $permissions->isAllowedMethod(
				$userId,
				$controller->getCurrentModule(),
				$controller->getCurrentMethod()
			);

			if ((!$permissions->isAdmin() || !$isAllowed) && file_exists($dirPath . 'main_login.xsl')) {
				if ($auth->isAuthorized()) {
					$sqlWhere = "owner_id = {$userId}";
					$userGroups = umiObjectsCollection::getInstance()->getObject($userId)->getValue('groups');
					foreach ($userGroups as $userGroup) {
						$sqlWhere .= " or owner_id = {$userGroup}";
					}

					$connection = ConnectionPool::getInstance()->getConnection();
					$sql = "SELECT `module` FROM cms_permissions WHERE ($sqlWhere) AND (method = '' OR method IS NULL)";
					$result = $connection->queryResult($sql);
					$result->setFetchType(IQueryResult::FETCH_ARRAY);

					if ($result->length() !== 0) {
						$regedit = Service::Registry();

						foreach ($result as $row) {
							$module = array_shift($row);
							$method = $regedit->get("//modules/{$module}/default_method_admin");
							if ($permissions->isAllowedMethod($userId, $module, $method)) {
								def_module::redirect(
									'http://' . Service::DomainDetector()->detectHost() . '/admin/' . $module . '/' . $method
								);
								break;
							}
						}
					}
				}
				$fileName = 'main_login.xsl';
			}
			$filePath = $dirPath . $fileName;
		} else {
			$templatesColl = templatesCollection::getInstance();
			$tpl = false;
			$template_id = getRequest('template_id');

			if ($template_id) {
				$tpl = $templatesColl->getTemplate($template_id);
			}

			if (!$tpl instanceof iTemplate) {
				$tpl = ($mode == 'current') ? $templatesColl->getCurrentTemplate() : $templatesColl->getDefaultTemplate();
			}

			if ($tpl instanceof iTemplate) {
				$fileName = $tpl->getFilename();
				$templateName = $tpl->getName();
				$type = $tpl->getType();
				if (!$type) {
					$nameParts = explode('.', $fileName);
					switch (array_pop($nameParts)) {
						case 'xsl':
							$type = 'xslt';
							break;
						case 'tpl':
							$type = 'tpls';
							break;
					}
				}
				$templateDirPath = CURRENT_WORKING_DIR . '/templates/' . $templateName . '/' . $type . '/';
				switch ($type) {
					case 'xslt':
						$dirPath = file_exists($templateDirPath . $fileName)
							? $templateDirPath
							: $config->includeParam('templates.xsl');
						$className = 'xslTemplater';
						break;
					case 'tpls':
						$dirPath = file_exists($templateDirPath . 'content/' . $fileName)
							? $templateDirPath
							: $config->includeParam('templates.tpl');
						$className = 'tplTemplater';
						break;
					default :
						$dirPath = file_exists($templateDirPath . $fileName) ? $templateDirPath : '';
						$className = file_exists(dirname(__FILE__) . '/' . $type . '/' . $type . 'Templater.php')
							? $type . 'Templater'
							: '';
				}
				if ($mode == 'streams') {
					$className = 'xslTemplater';
					$type = 'xslt';
					$dirPath = $config->includeParam('templates.xsl');
					$fileName = 'sample.xsl';
				}
				if (Service::Request()->isMobile() && file_exists($dirPath . 'mobile/' . $fileName)) {
					$dirPath = $dirPath . 'mobile/';
				}
				$filePath = $dirPath . ($type == 'tpls' ? 'content/' : '') . $fileName;
			} else {
				if ($mode == 'default' || $mode == 'streams') {
					$className = 'xslTemplater';
					$type = 'xslt';
					$dirPath = $config->includeParam('templates.xsl');
					$filePath = $config->includeParam('templates.xsl') . 'sample.xsl';
				} else {
					$buffer = Service::Response()
						->getCurrentBuffer();
					$buffer->clear();
					$buffer->status(500);
					$buffer->push(file_get_contents(SYS_ERRORS_PATH . 'no_design_template.html'));
					$buffer->end();
				}
			}
		}
		$params = [
			'class_name' => $className,
			'type' => $type,
			'dir_path' => $dirPath,
			'file_path' => $filePath
		];
		return $params;
	}

	/** @deprecated */
	function system_remove_cache($alt) {
		$cacheFolder = ini_get('include_path') . 'cache';
		$cacheFileName = md5($alt);
		$cacheFilePath = $this->cacheFolder . '/' . $this->cacheFileName;

		if (file_exists($cacheFilePath)) {
			return unlink(md5($cacheFilePath));
		}

		return false;
	}

	/** @deprecated */
	function system_gen_password(
		$length = 12,
		$avLetters = '$#@^&!1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM'
	) {
		$npass = '';
		for ($i = 0; $i < $length; $i++) {
			$npass .= $avLetters[mt_rand(0, mb_strlen($avLetters) - 1)];
		}
		return $npass;
	}

	/** @deprecated */
	function getPrintableTpl($_sTplName) {
		if (!isset($_GET['print'])) {
			return $_sTplName;
		}
		$sNewTplPath = mb_substr($_sTplName, 0, mb_strrpos($_sTplName, '.')) . '.print.tpl';
		return file_exists('tpls/content/' . $sNewTplPath) ? $sNewTplPath : $_sTplName;
	}

	/**
	 * @deprecated
	 * @return null
	 */
	function system_checkSession() {
		return null;
	}

	/**
	 * @deprecated
	 * @return null
	 */
	function system_setSession() {
		return null;
	}

	/**
	 * @deprecated
	 * @return null
	 */
	function system_removeSession() {
		return null;
	}

	/**
	 * @deprecated
	 * @return null
	 */
	function system_getSession() {
		return null;
	}

	/**
	 * @deprecated
	 * @return null
	 */
	function system_runSession() {
		return null;
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	function isLocalMode() {
		return Service::Request()->isLocal();
	}
