<?php

	use UmiCms\Service;

	if (!defined('CURRENT_WORKING_DIR')) {
		define('CURRENT_WORKING_DIR', str_replace("\\", '/', dirname(dirname(__FILE__))));
	}

	if (!defined('CONFIG_INI_PATH')) {
		define('CONFIG_INI_PATH', CURRENT_WORKING_DIR . '/config.ini');
	}

	if (!class_exists('mainConfiguration')) {
		require_once CURRENT_WORKING_DIR . '/classes/system/patterns/iSingleton.php';
		require_once CURRENT_WORKING_DIR . '/libs/iConfiguration.php';
		require_once CURRENT_WORKING_DIR . '/libs/configuration.php';
	}

	try {
		$config = mainConfiguration::getInstance();
	} catch (Exception $e) {
		echo 'Critical error: ', $e->getMessage();
		exit;
	}

	$ini = $config->getParsedIni();
	initConfigConstants($ini);

	if (!defined('SYS_KERNEL_PATH')) {
		define('SYS_KERNEL_PATH', $config->includeParam('system.kernel'));
	}

	define('SYS_LIBS_PATH', $config->includeParam('system.libs'));

	if (!defined('SYS_DEF_MODULE_PATH')) {
		define('SYS_DEF_MODULE_PATH', $config->includeParam('system.default-module') . getCompatibleModulesPath());
	}

	define('SYS_TPLS_PATH', $config->includeParam('templates.tpl'));
	define('SYS_XSLT_PATH', $config->includeParam('templates.xsl'));
	define('SYS_SKIN_PATH', $config->includeParam('templates.skins'));
	define('SYS_ERRORS_PATH', $config->includeParam('system.error'));
	define('SYS_MODULES_PATH', $config->includeParam('system.modules') . getCompatibleModulesPath());

	if (!defined('SYS_CACHE_RUNTIME')) {
		define('SYS_CACHE_RUNTIME', $config->includeParam('system.runtime-cache'));
	}

	if (!defined('SYS_MANIFEST_PATH')) {
		define('SYS_MANIFEST_PATH', $config->includeParam('system.manifest'));
	}

	define('SYS_KERNEL_STREAMS', $config->includeParam('system.kernel.streams'));
	define('KEYWORD_GRAB_ALL', $config->get('kernel', 'grab-all-keyword'));

	$cacheSalt = $config->get('system', 'salt');

	if (!$cacheSalt) {
		$cacheSalt = sha1(mt_rand());
		$config->set('system', 'salt', $cacheSalt);
		$config->save();
	}

	define('SYS_CACHE_SALT', $cacheSalt);

	if (!class_exists('umiAutoload')) {
		require_once CURRENT_WORKING_DIR . '/libs/umiAutoload.php';
	}

	spl_autoload_register('umiAutoload::autoload');

	require_once __DIR__ . '/AutoloadMapLoader.php';
	$mapLoader = new \UmiCms\Libs\AutoloadMapLoader();
	$map = $mapLoader
		->fromConfig(mainConfiguration::getInstance())
		->fromFile(__DIR__ . '/autoload.php')
		->fromFile(__DIR__ . '/autoload.custom.php')
		->getMap();

	umiAutoload::addClassesToAutoload($map);

	$composerAutoloadFiles = [
		CURRENT_WORKING_DIR . '/libs/vendor/autoload.php',
		CURRENT_WORKING_DIR . '/vendor/autoload.php',
	];

	foreach ($composerAutoloadFiles as $file) {
		if (file_exists($file)) {
			require_once $file;
		}
	}

	require SYS_LIBS_PATH . 'lib.php';
	require SYS_LIBS_PATH . 'system.php';
	require SYS_LIBS_PATH . 'def_macroses.php';
	require SYS_DEF_MODULE_PATH . 'def_module.php';
	require SYS_LIBS_PATH . 'streams.php';

	if (!class_exists('XSLTProcessor')) {
		$buffer = Service::Response()
			->getCurrentBuffer();
		$buffer->status(500);
		$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/xslt_failed.html'));
		$buffer->end();
	}

	$debug = false;

	if ($config->get('debug', 'enabled')) {
		$ips = $config->get('debug', 'filter.ip');
		if (is_array($ips)) {
			if (in_array(getServer('REMOTE_ADDR'), $ips)) {
				$debug = true;
			}
		} else {
			$debug = true;
		}
	}

	if (!defined('DEBUG')) {
		define('DEBUG', $debug);
	}

	if (!defined('DEBUG_SHOW_BACKTRACE')) {
		$showBacktrace = false;
		$allowedIps = $config->get('debug', 'allowed-ip');
		$allowedIps = is_array($allowedIps) ? $allowedIps : [];

		if ($config->get('debug', 'show-backtrace') &&
			(!umiCount($allowedIps) || in_array(getServer('REMOTE_ADDR'), $allowedIps))) {
			$showBacktrace = true;
		}

		define('DEBUG_SHOW_BACKTRACE', $showBacktrace);
	}

	error_reporting(DEBUG ? (defined('E_DEPRECATED') ? ~E_STRICT & ~E_DEPRECATED : ~E_STRICT) : E_ERROR);
	ini_set('display_errors', '1');

	/* Установка обработчика исключений */
	if (!isCronMode()) {
		// Выбор шаблона, в зависимости от режима работы
		if (getRequest('xmlMode') == 'force') {
			$template = SYS_ERRORS_PATH . 'exception.xml.php';
		} elseif (getRequest('jsonMode') == 'force') {
			$template = SYS_ERRORS_PATH . 'exception.json.php';
		} else {
			$template = SYS_ERRORS_PATH . 'exception.html.php';
		}

		umiExceptionHandler::set('base', $template);
		umiExceptionHandler::handleShutdown();
	}

	if (!defined('DEBUG') && function_exists('libxml_use_internal_errors')) {
		libxml_use_internal_errors(true);
	}

	$timezone = $config->get('system', 'time-zone');
	if ($timezone) {
		@date_default_timezone_set($timezone);
	}

	initConfigConnections($ini);

	if (isset($ini['system']['image-compression'])) {
		$imageCompression = getImageQualityLevel($ini['system']['image-compression']);
		define('IMAGE_COMPRESSION_LEVEL', $imageCompression);
	} else {
		define('IMAGE_COMPRESSION_LEVEL', 100);
	}

	if (defined('LIBXML_VERSION')) {
		define('DOM_LOAD_OPTIONS', (LIBXML_VERSION < 20621) ? 0 : LIBXML_COMPACT);
	} else {
		define('DOM_LOAD_OPTIONS', LIBXML_COMPACT);
	}

	if (!defined('PHP_INT_MAX')) {
		define('PHP_INT_MAX', 4294967296 / 2 - 1);
	}

	if (!is_string(getenv('OS')) || mb_strtolower(mb_substr(getenv('OS'), 0, 3)) != 'win') {
		setlocale(LC_NUMERIC, 'en_US.utf8');
	}

	mb_internal_encoding('UTF-8');

	if (!headers_sent()) {
		ini_set('session.cookie_lifetime', '0');
		ini_set('session.use_cookies', '1');
		ini_set('session.use_only_cookies', '1');
	}

	if (defined('CLUSTER_CACHE_CORRECTION') && CLUSTER_CACHE_CORRECTION) {
		Service::CacheFrontend();
		clusterCacheSync::getInstance();
	}

	if (!Service::Request()->isCli() && !isCronMode() && CURRENT_WORKING_DIR != 'phar://umi.phar.php') {
		try {
			checkIpAddress($config);
		} catch (Exception $exception) {
			//nothing
		}
	}

	importTemplateAutoload();

	/**
	 * Проверяет не был ли текущий ip пользователя добавлен в черный список ip адресов.
	 * Если был добавлен, то обратившемуся отдастся белый экран со статусом 403.
	 * Добавить ip в черный список можно либо через справочник "Список IP-адресов, которым недоступен сайт"
	 * (если включена опция "use-ip-blacklist-guide" в config.ini), либо через опцию "ip-blacklist" в config.ini.
	 * Обе управляющие опции расположены в секции "kernel", синтаксис добавления ip адреса в "ip-blacklist":
	 * ip-blacklist = "XXX.XXX.X.XXX,XXX.XXX.X.XXX"
	 * @param iConfiguration $config
	 * @throws coreException
	 */
	function checkIpAddress(iConfiguration $config) {
		$useIpBlacklistGuide = (bool) $config->get('kernel', 'use-ip-blacklist-guide');
		$isUseBlackList = ($useIpBlacklistGuide && !isUseDomainSettings()) || isUseBlackListForCurrentDomain();

		if (!$isUseBlackList) {
			return;
		}

		$remoteIP = Service::Request()->remoteAddress();

		if (isIpInBlackList($remoteIP)) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/html');
			$buffer->charset('utf-8');
			$buffer->status('403 Forbidden');
			$buffer->clear();
			$buffer->end();
		}
	}

	/**
	 * Проверяет находится ли IP в черном списке
	 * @param string $remoteIP
	 * @return bool
	 * @throws coreException
	 * @throws selectorException
	 */
	function isIpInBlackList($remoteIP) {
		$ipList = mainConfiguration::getInstance()->get('kernel', 'ip-blacklist');
		$isIpInConfigBlackList = in_array($remoteIP, explode(',', $ipList));

		$selector = new selector('objects');
		$selector->types('object-type')->guid('ip-blacklist');
		$selector->option('ignore-translate', true);

		if (isUseBlackListForCurrentDomain()) {
			$selector->where('domain_id')->equals(getCurrentDomain()->getId());
		} else {
			$selector->where('domain_id')->isnull();
		}

		$selector->where('name')->equals($remoteIP);
		$selector->limit(0,1);

		return ($selector->result() || $isIpInConfigBlackList);
	}

	/**
	 * Используется ли черный список для данного домена
	 * @return bool
	 * @throws coreException
	 */
	function isUseBlackListForCurrentDomain() {
		$blacklistDomainList = (array) mainConfiguration::getInstance()
			->get('kernel', 'use-ip-blacklist-guide-for-domain');

		return in_array(getCurrentDomain()->getHost(), $blacklistDomainList) && isUseDomainSettings();
	}

	/**
	 * Используются ли настройки домена
	 * @return string
	 * @throws coreException
	 */
	function isUseDomainSettings() {
		$langId = Service::LanguageDetector()->detectId();
		return Service::Registry()->get("//umiStub/" . getCurrentDomain()->getId() . "/$langId/use-custom-settings");
	}

	/**
	 * Возвращает текущий домен
	 * @return iDomain
	 * @throws coreException
	 */
	function getCurrentDomain() {
		return Service::DomainDetector()->detect();
	}

	function initConfigConstants($ini) {
		$defineConstants = [
			'system:db-driver' => ['DB_DRIVER', '%value%'],
			'system:version-line' => ['CURRENT_VERSION_LINE', '%value%'],
			'session:active-lifetime' => ['SESSION_LIFETIME', '%value%'],
			'system:default-date-format' => ['DEFAULT_DATE_FORMAT', '%value%'],
			'kernel:use-reflection-extension' => ['USE_REFLECTION_EXT', '%value%'],
			'kernel:cluster-cache-correction' => ['CLUSTER_CACHE_CORRECTION', '%value%'],
			'kernel:xslt-nested-menu' => ['XSLT_NESTED_MENU', '%value%'],
			'kernel:pages-auto-index' => ['PAGES_AUTO_INDEX', '%value%'],
			'kernel:enable-pre-auth' => ['PRE_AUTH_ENABLED', '%value%'],
			'kernel:ignore-module-names-overwrite' => ['IGNORE_MODULE_NAMES_OVERWRITE', '%value%'],
			'kernel:xml-format-output' => ['XML_FORMAT_OUTPUT', '%value%'],
			'kernel:selection-max-joins' => ['MAX_SELECTION_TABLE_JOINS', '%value%'],
			'kernel:property-value-mode' => ['XML_PROP_VALUE_MODE', '%value%'],
			'kernel:xml-macroses-disable' => ['XML_MACROSES_DISABLE', '%value%']
		];

		foreach ($defineConstants as $name => $const) {
			list($section, $variable) = explode(':', $name);
			$value = $const[1];

			if (is_string($value)) {
				$iniValue = isset($ini[$section][$variable]) ? $ini[$section][$variable] : '';
				$value = str_replace('%value%', $iniValue, $value);
			} else {
				if (!$value && isset($const[2])) {
					$value = $const[2];
				}
			}

			if (!defined($const[0])) {
				if ($const[0] == 'CURRENT_VERSION_LINE' && !$value) {
					continue;
				}
				define($const[0], $value);
			}
		}
	}

	function initConfigConnections($ini) {
		$connections = [];

		foreach ($ini['connections'] as $name => $value) {
			list($class, $pname) = explode('.', $name);
			if (!isset($connections[$class])) {
				$connections[$class] = [
					'type' => 'mysql',
					'host' => 'localhost',
					'login' => 'root',
					'password' => '',
					'dbname' => 'umi',
					'port' => false,
					'persistent' => false,
					'compression' => false
				];
			}
			$connections[$class][$pname] = $value;
		}

		$pool = ConnectionPool::getInstance();

		foreach ($connections as $class => $con) {
			$mysqlApi = 'mysqli';
			if (version_compare(phpversion(), '7.0.0', '<')) {
				$mysqlApi = (isset($con['api']) && $con['api'] == 'mysqli') ? 'mysqli' : 'mysql';
			}
			$mysqlApiClassName = $mysqlApi . 'Connection';
			$pool->setConnectionObjectClass($mysqlApiClassName);

			if ($con['dbname'] == '-=demo=-' || $con['dbname'] == '-=custom=-') {
				if ($con['dbname'] == '-=demo=-') {
					require './demo-center.php';
				}

				$con['host'] = MYSQL_HOST;
				$con['login'] = MYSQL_LOGIN;
				$con['password'] = MYSQL_PASSWORD;
				$con['dbname'] = ($con['dbname'] == '-=custom=-') ? MYSQL_DB_NAME : DEMO_DB_NAME;
			}

			$pool->addConnection(
				$class,
				$con['host'],
				$con['login'],
				$con['password'],
				$con['dbname'],
				($con['port'] !== false) ? $con['port'] : false,
				(bool) (int) $con['persistent']
			);
		}

		$connection = ConnectionPool::getInstance()->getConnection();
		ini_set('mysql.trace_mode', false);

		$config = mainConfiguration::getInstance();

		if ($config->get('kernel', 'mysql-queries-log-enable')) {
			$logType = $config->get('kernel', 'mysql-queries-log-type');
			$mysqlLoggerCreator = MysqlLoggerCreator::getInstance();
			$mysqlLogger = $mysqlLoggerCreator->createMysqlLogger($logType, $config);
			/* @var mysqliConnection $connection */
			$connection->setLogger($mysqlLogger);
		}
	}

	/**
	 * Читаем настройки переданные в fastcgi_param от сервера и заменяет ими прочитанные из config.ini
	 * собавляет новые которых нет в файле конфигурации
	 * @param $ini
	 * @return mixed
	 */
	function getParamFromFastcgiParams($ini) {
		foreach ($_SERVER as $key => $val) {
			if (contains($key, 'UMI_')) {
				$key = str_replace('UMI_', '', $key);
				$key = str_replace('_', '.', $key);
				$key = explode('.', $key, 2);
				$ini[$key[0]][$key[1]] = $val;
			}
		}
		return $ini;
	}

	/**
	 * Возвращает путь до директории с совместимыми модулями
	 * @return string путь до директории или пустая строка
	 */
	function getCompatibleModulesPath() {
		$config = mainConfiguration::getInstance();
		$isCompatible = (bool) $config->get('system', 'compatible-modules');

		if (version_compare(phpversion(), '7.0.0', '>=')) {
			$isCompatible = true;
		}

		$modulesPath = '../components';
		return ($isCompatible ? $modulesPath . DIRECTORY_SEPARATOR : '');
	}

	/** Импортирует файл правил автозагрузки классов из директории с шаблоном */
	function importTemplateAutoload() {
		try {
			$directory = cmsController::getInstance()
				->getResourcesDirectory();
		} catch (Exception $exception) {
			umiExceptionHandler::report($exception);
			return;
		}

		$mapPath = sprintf('%s/autoload.php', rtrim($directory, '/'));
		$mapLoader = new \UmiCms\Libs\AutoloadMapLoader();
		$map =$mapLoader->fromFile($mapPath)
			->getMap();
		umiAutoload::addClassesToAutoload($map);
	}

	/**
	 * Проверяет, что при запросе из мобильного приложения в системе подключен модуль "Интернет-магазин".
	 * Если он не подключен, приложению возвращается сообщение об ошибке.
	 * @throws publicException
	 */
	function checkMobileApplication() {
		if (getRequest('mobile_application') == 'true' && !Service::Registry()->get('//modules/emarket')) {
			$data = [
				'data' => [
					'type' => null,
					'action' => null,
					'error' => [
						'code' => 0,
						'message' => getLabel('label-module-emarket-is-absent')
					]
				]
			];

			/** @var HTTPOutputBuffer $buffer */
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();

			if (getRequest('xmlMode') == 'force') {
				$dom = new DOMDocument('1.0', 'utf-8');
				$rootNode = $dom->createElement('result');
				$dom->appendChild($rootNode);
				$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');
				$translator = new xmlTranslator($dom);
				$translator->translateToXml($rootNode, $data);

				$buffer->contentType('text/xml');
				$buffer->push($dom->saveXML());
			} elseif (getRequest('jsonMode') == 'force') {
				$translator = new jsonTranslator;

				$buffer->contentType('text/javascript');
				$buffer->push($translator->translateToJson($data));
			} else {
				throw new publicException(getLabel('label-module-emarket-is-absent'));
			}

			$buffer->end();
		}
	}