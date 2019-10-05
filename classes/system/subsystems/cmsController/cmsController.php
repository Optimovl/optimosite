<?php

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\I18n\I18nFilesLoader;

	/** Контроллер приложения UMI.CMS. */
	class cmsController extends singleton implements iSingleton, iCmsController {

		public static $IGNORE_MICROCACHE = false;

		public $isContentMode = false;

		public $parsedContent = false;

		public $currentTitle = false;

		public $currentHeader = false;

		public $currentMetaKeywords = false;

		public $currentMetaDescription = false;

		public $currentEditElementId = false;

		public $pre_lang;

		public $langs_export = [];

		public $errorUrl;

		public $headerLabel = false;

		/** @var umiTemplater|bool */
		protected $current_templater = false;

		/** @var iTemplate текущий шаблон дизайна */
		protected $currentTemplate;

		protected $modules = [];

		protected $current_module = false;

		protected $current_method = false;

		protected $current_element_id = false;

		protected $calculated_referer_uri = false;

		protected $modulesPath;

		protected $url_prefix = '';

		protected $adminDataSet = [];

		/** @var array Методы, вызов которых надо игнорировать в режиме XSLT */
		private $skipExecuteMethods = [
			'eshop/compare',
			'faq/question',
			'faq/project',
			'faq/category',
			'blogs20/blog',
			'blogs20/post',
			'blogs20/postEdit',
			'catalog/category',
			'catalog/getObjectsList',
			'catalog/object',
			'catalog/viewObject',
			'catalog/search',
			'content/content',
			'content/sitemap',
			'dispatches/unsubscribe',
			'dispatches/subscribe',
			'dispatches/subscribe_do',
			'emarket/compare',
			'emarket/order',
			'emarket/purchase',
			'filemanager/shared_file',
			'forum/confs_list',
			'forum/conf',
			'forum/topic',
			'forum/topic_last_message',
			'forum/conf_last_message',
			'news/lastlist',
			'news/rubric',
			'news/view',
			'news/related_links',
			'news/item',
			'news/listlents',
			'news/lastlents',
			'photoalbum/album',
			'photoalbum/photo',
			'search/search_do',
			'search/suggestions',
			'users/settings',
			'users/registrate',
			'users/registrate_done',
			'users/activate',
			'users/auth',
			'vote/poll',
			'vote/insertvote',
			'vote/results',
			'webforms/page',
			'webforms/posted'
		];

		/** @var array языковые константы для шаблонов сайта из файлов вида lang.*.php */
		private $langConstantList = [];

		/** @var @deprecated */
		public $langs = [];

		/** @inheritdoc */
		protected function __construct() {
			$config = mainConfiguration::getInstance();
			$this->modulesPath = $config->includeParam('system.modules') . getCompatibleModulesPath();
			$this->errorUrl = getServer('HTTP_REFERER');
		}

		/**
		 * @inheritdoc
		 * @return iCmsController
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getModule($moduleName, $resetCache = false) {
			if (!$moduleName) {
				return false;
			}

			if (array_key_exists($moduleName, $this->modules) && !$resetCache) {
				return $this->modules[$moduleName];
			}

			return $this->loadModule($moduleName);
		}

		/** @inheritdoc */
		public function getModulesList() {
			$list = Service::Registry()->getList('//modules');
			$result = [];

			foreach ($list as $arr) {
				$result[] = getArrayKey($arr, 0);
			}

			return $result;
		}

		/** @inheritdoc */
		public function isModule($moduleName) {
			return Service::Registry()->contains("//modules/{$moduleName}");
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function installModule($installPath) {
			$INFO = [];
			$COMPONENTS = [];

			if (!file_exists($installPath)) {
				throw new publicAdminException(getLabel('label-errors-13052'), 13052);
			}

			/** @noinspection PhpIncludeInspection */
			require_once $installPath;

			$nameByPath = null;

			if (preg_match('|classes\/modules\/(\S+)\/|i', $installPath, $matches)) {
				$nameByPath = $matches[1];
			}

			if ($nameByPath === null && preg_match('|classes\/components\/(\S+)\/|i', $installPath, $matches)) {
				$nameByPath = $matches[1];
			}

			if ($nameByPath != $INFO['name']) {
				throw new publicAdminException(getLabel('label-errors-13053'), 13053);
			}

			$this->checkModuleByName($nameByPath);
			$this->checkModuleComponents($COMPONENTS);

			def_module::install($INFO);
		}

		/** @inheritdoc */
		public function getCurrentModule() {
			return $this->current_module;
		}

		/** @inheritdoc */
		public function setCurrentModule($moduleName) {
			$this->current_module = (string) $moduleName;
			return $this;
		}

		/** @inheritdoc */
		public function getCurrentMethod() {
			return $this->current_method;
		}

		/** @inheritdoc */
		public function setCurrentMethod($methodName) {
			$magic = [
				'__construct',
				'__destruct',
				'__call',
				'__callStatic',
				'__get',
				'__set',
				'__isset',
				'__unset',
				'__sleep',
				'__wakeup',
				'__toString',
				'__invoke',
				'__set_state',
				'__clone'
			];

			if (in_array($methodName, $magic)) {
				$this->setCurrentModule('content');
				$methodName = 'notfound';
			}

			$this->current_method = (string) $methodName;
			return $this;
		}

		/** @inheritdoc */
		public function getCurrentElementId() {
			return $this->current_element_id;
		}

		/** @inheritdoc */
		public function setCurrentElementId($id) {
			$this->current_element_id = $id;
			return $this;
		}

		/** @inheritdoc */
		public function getCurrentTemplater($forceRefresh = false) {
			if (!$this->current_templater instanceof umiTemplater || $forceRefresh) {
				$this->detectCurrentTemplater();
			}

			if (!$this->current_templater instanceof umiTemplater) {
				throw new coreException("Can't detect current templater.");
			}

			return $this->current_templater;
		}

		/** @inheritdoc */
		public function getLangConstantList() {
			if (empty($this->langConstantList)) {
				$this->langConstantList = $this->loadLangConstantList();
			}

			return $this->langConstantList;
		}

		/** @inheritdoc */
		public function setLangConstant($module, $method, $label) {
			$this->langConstantList[$module][$method] = $label;
			return $this;
		}

		/** @inheritdoc */
		public function getResourcesDirectory($httpMode = false) {
			if (Service::Request()->isAdmin()) {
				$defaultTemplate = templatesCollection::getInstance()->getDefaultTemplate();
				if ($defaultTemplate instanceof iTemplate) {
					return $defaultTemplate->getResourcesDirectory($httpMode);
				}

				return false;
			}

			$currentTemplate = $this->detectCurrentDesignTemplate();
			if ($currentTemplate instanceof iTemplate) {
				return $currentTemplate->getResourcesDirectory($httpMode);
			}

			return false;
		}

		/** @inheritdoc */
		public function getTemplatesDirectory() {
			$template = $this->detectCurrentDesignTemplate();
			if ($template instanceof iTemplate) {
				return $template->getTemplatesDirectory();
			}

			return CURRENT_WORKING_DIR . 'xsltTpls/';
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws ErrorException
		 * @throws Exception
		 */
		public function getGlobalVariables($forcePrepare = false) {
			static $globalVariables;

			if (!$forcePrepare && $globalVariables !== null) {
				return $globalVariables;
			}

			$globalVariables = [];

			switch (true) {
				case (Service::Request()->isAdmin()) : {
					$globalVariables = $this->prepareAdminSideGlobalVariables();
					break;
				}
				case (def_module::isXSLTResultMode()) : {
					$globalVariables = $this->prepareClientSideGlobalVariablesForXSLT();
					break;
				}
				default: {
					$globalVariables = $this->prepareClientSideGlobalVariablesForTPL();
				}
			}

			$eventPoint = new umiEventPoint('globalVariablesCollected');
			$eventPoint->setMode('after');
			$eventPoint->addRef('variables', $globalVariables);
			$eventPoint->call();

			return $globalVariables;
		}

		/** @inheritdoc */
		public function executeStream($uri) {
			if (($data = @file_get_contents($uri)) === false) {
				// bugfix: failed to open stream: infinite recursion prevented
				$uri .= (!contains($uri, '?')) ? '?umiHash=' : '&umiHash=';
				$uri .= md5($uri);

				if (($data = @file_get_contents($uri)) === false) {
					preg_match("/(\w+:\/\/)/i", $uri, $matches);
					$stream = $matches[1];

					throw new coreException("Failed to open $stream stream");
				}
			}

			return $data;
		}

		/**
		 * @internal
		 * Предназначен для избавления от заплатки $this->breakMe
		 * Возвращает false, если метод вызывать не нужно
		 *
		 * @param string $module
		 * @param string $method
		 *
		 * @return boolean
		 */
		public function isAllowedExecuteMethod($module, $method) {
			return !in_array($module . '/' . $method, $this->skipExecuteMethods);
		}

		/** @inheritdoc */
		public function getCurrentTemplate() {
			if (!$this->currentTemplate) {
				$this->detectCurrentDesignTemplate();
			}

			return $this->currentTemplate;
		}

		/** @inheritdoc */
		public function detectCurrentDesignTemplate() {
			$umiTemplates = templatesCollection::getInstance();
			$template = null;

			$templateId = (int) getRequest('template_id');
			if ($templateId) {
				$template = $umiTemplates->getTemplate($templateId);
			}

			if (!$template instanceof iTemplate) {
				$template = $umiTemplates->getCurrentTemplate();
			}

			$this->currentTemplate = $template;
			return $template;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws Exception
		 */
		public function analyzePath($reset = false) {
			$request = Service::Request();
			$requestUri = $request->uri();

			if ($requestUri && !$this->isCorrectRequestUri($requestUri)) {
				$this->setCurrentElementId(false);
				$this->setCurrentModule('content');
				$this->setCurrentMethod('content');
				return;
			}

			$path = $request->getPath();

			if (getRequest('scheme') !== null) {
				if (preg_replace('/[^\w]/im', '', getRequest('scheme')) == 'upage') {
					preg_match_all('/[\d]+/', $path, $elementId);
					$this->setCurrentElementId($elementId[0][0]);
				}

				return;
			}

			$regedit = Service::Registry();
			$hierarchy = umiHierarchy::getInstance();
			$config = mainConfiguration::getInstance();
			$buffer = Service::Response()
				->getCurrentBuffer();
			$redirects = Service::Redirects();

			if ($reset === true) {
				$this->reset();
			}

			$urlSuffix = $config->get('seo', 'url-suffix');
			$pos = mb_strrpos($path, $urlSuffix);
			if ($pos && ($pos + mb_strlen($urlSuffix) == mb_strlen($path))) {
				$path = mb_substr($path, 0, $pos);
			}

			if ($request->isNotAdmin()) {
				$redirects->redirectIfRequired($requestUri, true);
			}

			if ($config->get('seo', 'watch-redirects-history')) {
				$redirects->init();
			}

			$pathArray = $request->getPathParts();
			$sz = umiCount($pathArray);
			$urlArray = [];
			$p = 0;
			$currentLang = Service::LanguageDetector()->detect();

			for ($i = 0; $i < $sz; $i++) {
				$subPath = $pathArray[$i];

				if ($i <= 1) {
					if (($subPath == $request->mode()) || ($subPath == $currentLang->getPrefix())) {
						continue;
					}
				}

				$urlArray[] = $subPath;

				$subPathType = $this->getSubPathType($subPath);

				if ($subPathType == 'PARAM') {
					$_REQUEST['param' . $p++] = $subPath;
				}
			}

			if (!$this->getCurrentModule()) {
				if ($request->isAdmin()) {
					if ($regedit->get('//settings/default_module_admin_changed') ||
						!$moduleName = $regedit->get('//modules/events')) {
						$moduleName = $regedit->get('//settings/default_module_admin');
					} else {
						$moduleName = $regedit->get('//modules/events');
					}
					$this->autoRedirectToMethod($moduleName);
				} else {
					$moduleName = $regedit->get('//settings/default_module');
				}
				$this->setCurrentModule($moduleName);
			}

			if (!$this->getCurrentMethod()) {
				if ($request->isAdmin()) {
					return $this->autoRedirectToMethod($this->getCurrentModule());
				}

				$method_name = $regedit->get('//modules/' . $this->getCurrentModule() . '/default_method');
				$this->setCurrentMethod($method_name);
			}

			if ($request->isAdmin()) {
				return;
			}

			$elementId = false;
			$sz = umiCount($urlArray);
			$subPath = '';
			$errorsCount = 0;

			for ($i = 0; $i < $sz; $i++) {
				$subPath .= '/' . $urlArray[$i];

				if (!($tmp = $hierarchy->getIdByPath($subPath, false, $errorsCount))) {
					$elementId = false;
					break;
				}

				$elementId = $tmp;
			}

			if ($elementId) {

				if ($this->isCaseSensitiveAltName() && $request->isHtml()) {
					$urlFactory = Service::UrlFactory();
					$elementUrl = $urlFactory->create($hierarchy->getPathById($elementId));
					$requestUrl = $urlFactory->create($requestUri);

					if ($elementUrl->getPath() !== $this->addSuffixToPath($requestUrl->getPath())) {
						$path = $requestUrl->merge($elementUrl);
						$this->processCaseSensitiveUrl($buffer, $path);
						$elementId = false;
					}
				}

				if ($errorsCount > 0 && !defined('DISABLE_AUTOCORRECTION_REDIRECT')) {
					$path = $hierarchy->getPathById($elementId);
					
					if ($i == 0) {
						if ($this->isModule($urlArray[0])) {
							$elementId = false;
						}
					}

					$buffer->status('301 Moved Permanently');
					$buffer->redirect($path);
				}

				$element = $hierarchy->getElement($elementId);

				if ($element instanceof iUmiHierarchyElement) {
					if ($element->getIsDefault()) {
						$path = $hierarchy->getPathById($elementId);
						$buffer->status('301 Moved Permanently');
						$buffer->redirect($path);
					}
				}
			} elseif (isset($urlArray[0])) {
				if ($this->isModule($urlArray[0])) {
					$module = $this->getModule($urlArray[0]);
					if (isset($urlArray[1]) && !$module->isMethodExists($urlArray[1])) {
						$this->setCurrentModule('content');
						$this->setCurrentMethod('content');
					}
				} else {
					$this->setCurrentModule('content');
					$this->setCurrentMethod('content');
				}
			}

			if (($path == '' || $path == $currentLang->getPrefix()) && $request->isNotAdmin()) {
				$elementId = $hierarchy->getDefaultElementId(
					$currentLang->getId(),
					Service::DomainDetector()->detectId()
				);

				if ($elementId) {
					$this->setCurrentElementId($elementId);
				} else {
					$this->setCurrentModule('content');
					$this->setCurrentMethod('content');
				}
			}

			$element = $hierarchy->getElement($elementId, true);

			if ($element instanceof iUmiHierarchyElement) {
				$type = umiHierarchyTypesCollection::getInstance()->getType($element->getTypeId());

				if (!$type) {
					return false;
				}

				$this->setCurrentModule($type->getName());
				$ext = $type->getExt();

				if ($ext) {
					$this->setCurrentMethod($ext);
				} else {
					$this->setCurrentMethod('content');  //Fixme: content "constructor". Maybe, fix in future?
				}

				$this->setCurrentElementId($elementId);
			}

			if ($this->getCurrentModule() == 'content' && $this->getCurrentMethod() == 'content' && !$elementId) {
				$redirects->redirectIfRequired($path);
			}
		}

		/**
		 * Корректно ли значение серверной переменной REQUEST_URI
		 * @param string $requestUri значение серверной переменной REQUEST_URI
		 * @return bool
		 */
		private function isCorrectRequestUri($requestUri) {
			return !preg_match('/(^[^\?]*\/&)/', $requestUri);
		}

		/** @inheritdoc */
		public function setAdminDataSet($dataSet) {
			$this->adminDataSet = $dataSet;
		}

		/** @inheritdoc */
		public function getRequestId() {
			static $requestId = false;

			if ($requestId === false) {
				$requestId = time();
			}

			return $requestId;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getPreLang() {
			if ($this->pre_lang === null) {
				$this->detectPreLang();
			}

			return $this->pre_lang;
		}

		/** @inheritdoc */
		public function setPreLang($prefix) {
			$this->pre_lang = (string) $prefix;
			return $this;
		}

		/** @inheritdoc */
		public function calculateRefererUri() {
			$session = Service::Session();
			$referrer = getRequest('referer');

			if ($referrer) {
				$session->set('referer', $referrer);
			} else {
				$referrer = $session->get('referer');

				if ($referrer) {
					$session->del('referer');
				} else {
					$referrer = getServer('HTTP_REFERER');
				}
			}

			$this->calculated_referer_uri = $referrer;
		}

		/** @inheritdoc */
		public function getCalculatedRefererUri() {
			if ($this->calculated_referer_uri === false) {
				$this->calculateRefererUri();
			}

			return $this->calculated_referer_uri;
		}

		/** @inheritdoc */
		public function setUrlPrefix($prefix = '') {
			$this->url_prefix = $prefix;
		}

		/** @inheritdoc */
		public function getUrlPrefix() {
			return $this->url_prefix ?: '';
		}

		/**
		 * Перенаправляет на подходящий административный метод модуля
		 * @param string $moduleName имя модуля
		 * @throws publicAdminException
		 * @throws coreException
		 */
		protected function autoRedirectToMethod($moduleName) {
			$registry = Service::Registry();

			if (!system_is_allowed($moduleName) || !$moduleName) {
				try {
					$moduleName = $this->getFirstAllowedModuleName();
				} catch (publicAdminException $exception) {
					$defaultSystemModule = $this->isModule('events') ? 'events' : 'content';
					$moduleName = $registry->get('//settings/default_module_admin') ?: $defaultSystemModule;
				}
			}

			$method = $registry->get('//modules/' . $moduleName . '/default_method_admin');

			if (!system_is_allowed($moduleName, $method) || !$method) {
				$module = $this->getModule($moduleName);

				if (!$module instanceof def_module) {
					throw new publicAdminException(getLabel('error-cannot-load-module', false, $moduleName));
				}

				try {
					$method = $module->getFirstAllowedTabName();
				} catch (publicAdminException $exception) {
					//nothing
				}
			}

			$url = $this->getPreLang() . '/admin/' . $moduleName . '/' . $method . '/';
			Service::Response()
				->getCurrentBuffer()
				->redirect($url);
		}

		/** 
    	 * @inheritdoc
    	 * @throws publicAdminException
    	 */
		public function getFirstAllowedModuleName() {
			$permissionCollection = permissionsCollection::getInstance();
			$userId = Service::Auth()
				->getUserId();

			foreach ($this->getModulesList() as $module) {
				if ($permissionCollection->isAllowedModule($userId, $module)) {
					return $module;
				}
			}

			throw new publicAdminException(getLabel('error-cannot-detect-allowed-module'));
		}

		/** @inheritdoc */
		final public static function doSomething() {
			if (isDemoMode() || isCronCliMode()) {
				return true;
			}

			$buffer = Service::Response()
				->getCurrentBuffer();

			if (defined('CURRENT_VERSION_LINE') && !isDemoMode()) {
				$buffer->status(500);
				$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/invalid_license.html'));
				$buffer->end();
			}

			if (!is_writable(SYS_CACHE_RUNTIME)) {
				$buffer->status(500);
				$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/invalid_permissions.html'));
				$buffer->end();
			}

			$keycode = Service::RegistrySettings()->getLicense();

			if (self::doStrangeThings($keycode)) {
				return true;
			}

			$compKeycode = [
				'pro' => umiTemplater::getSomething('pro'),
				'ultimate' => umiTemplater::getSomething('ultimate'),
				'1cfranchisee' => umiTemplater::getSomething('1cfranchisee'),
				'umifree' => umiTemplater::getSomething('umifree'),
				'shop' => umiTemplater::getSomething('shop'),
				'lite' => umiTemplater::getSomething('lite'),
				'start' => umiTemplater::getSomething('start'),
				'trial' => umiTemplater::getSomething('trial')
			];

			if (regedit::checkSomething($keycode, $compKeycode)) {
				return true;
			}

			$buffer->status(500);
			$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/invalid_license.html'));
			$buffer->end();
		}

		private static function doStrangeThings($keycode) {
			if (isDemoMode()) {
				return true;
			}

			$licenseFile = SYS_CACHE_RUNTIME . 'trash';
			$cmpKeycode = false;
			$expire = 604800;

			if (file_exists($licenseFile)) {
				if ((time() - filemtime($licenseFile)) > $expire) {
					$cmpKeycode = base64_decode(file_get_contents($licenseFile));
				}
			} else {
				file_put_contents($licenseFile, base64_encode($keycode));
			}

			if ($cmpKeycode !== false && $keycode) {
				if ($keycode === $cmpKeycode) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Загружает модуль и возвращает его экземпляр (или false, если модуль не удалось загрузить)
		 * @param string $moduleName название модуля
		 * @return def_module|bool
		 * @throws coreException
		 */
		private function loadModule($moduleName) {
			if (!defined('CURRENT_VERSION_LINE')) {
				define('CURRENT_VERSION_LINE', '');
			}

			if (Service::Registry()->get("//modules/{$moduleName}") != $moduleName) {
				return false;
			}

			$modulePath = $this->modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'class.php';

			if (!file_exists($modulePath)) {
				return false;
			}

			if (!class_exists($moduleName)) {
				/** @noinspection PhpIncludeInspection */
				require $modulePath;
			}

			$module = new $moduleName();
			$module->pre_lang = $this->getPreLang();
			$module->pid = $this->getCurrentElementId();
			$this->modules[$moduleName] = $module;

			return $module;
		}

		/**
		 * Проверка наличия всех компонентов модуля
		 *
		 * @param $components
		 * @throws coreException
		 * @return bool
		 */
		private function checkModuleComponents($components) {
			if (!is_array($components)) {
				return false;
			}

			$files = [];
			foreach ($components as $component) {
				$file = preg_replace('/.\/(.+)/', CURRENT_WORKING_DIR . '/' . '$1', $component);
				if (!file_exists($file) || !is_readable($file)) {
					$files[] = $file;
				}
			}

			if (umiCount($files)) {
				$error = getLabel('label-errors-13058') . "\n";
				foreach ($files as $file) {
					$error .= getLabel('error-file-does-not-exist', null, $file) . "\n";
				}

				throw new coreException($error);
			}

			return true;
		}

		/**
		 * Проверяет, что модуль доступен для данной лицензии.
		 * @param string $moduleName - имя модуля
		 * @throws publicAdminException
		 */
		private function checkModuleByName($moduleName) {
			if (!defined('UPDATE_SERVER')) {
				define('UPDATE_SERVER', base64_decode('aHR0cDovL3VwZGF0ZXMudW1pLWNtcy5ydS91cGRhdGVzZXJ2ZXIv'));
			}

			$regedit = Service::Registry();
			$domainsCollection = Service::DomainCollection();

			$info = [
				'type' => 'get-modules-list',
				'revision' => $this->getSystemBuild(),
				'host' => $domainsCollection->getDefaultDomain()->getHost(),
				'ip' => getServer('SERVER_ADDR'),
				'key' => Service::RegistrySettings()->getLicense()
			];
			$url = UPDATE_SERVER . '?' . http_build_query($info, '', '&');

			$result = $this->getFile($url);

			if (!$result) {
				throw new publicAdminException(getLabel('label-errors-13054'), 13054);
			}

			$xml = new DOMDocument();
			if (!$xml->loadXML($result)) {
				throw new publicAdminException(getLabel('label-errors-13055'), 13055);
			}

			$xpath = new DOMXPath($xml);

			// Проверяем, возможно сервер возвратил ошибку.
			$errors = $xpath->query('error');

			if ($errors->length != 0) {
				/** @var DomElement $error */
				$error = $errors->item(0);
				$code = $error->getAttribute('code');
				throw new publicAdminException(getLabel('label-errors-' . $code), $code);
			}

			$modules = $xpath->query('module');
			if ($modules->length == 0) {
				throw new publicAdminException(getLabel('label-errors-13056'), 13056);
			}

			$moduleName = mb_strtolower($moduleName);

			$modules = $xpath->query("module[@name='" . $moduleName . "']");
			if ($modules->length != 0) {
				/** @var DomElement $module */
				$module = $modules->item(0);

				if ($module->getAttribute('active') != '1') {
					throw new publicAdminException(getLabel('label-errors-13057'), 13057);
				}
			}
		}

		/**
		 * Выполняет запрос к серверу обновлений
		 *
		 * @param mixed $url - сформированная строка запроса
		 * @throws publicAdminException
		 * @return string;
		 */
		private function getFile($url) {
			try {
				return umiRemoteFileGetter::get($url);
			} catch (Exception $e) {
				throw new publicAdminException(getLabel('label-errors-13041'), 13041);
			}
		}

		/**
		 * Является ли запрошенная связка модуль/метод шлюзом?
		 * @return bool
		 */
		private function isGateway() {
			$gateways = [
				['exchange', 'auto'],
				['exchange', 'export1C'],
				['users', 'login_do'],
				['emarket', 'getOrderStatuses'],
				['emarket', 'getOrderStatusesList'],
				['emarket', 'getDeliveryStatuses'],
				['emarket', 'getPaymentStatuses'],
				['emarket', 'getOrdersByStatus'],
				['emarket', 'getOrder'],
				['emarket', 'setOrder'],
				['emarket', 'addToken'],
				['emarket', 'removeToken']
			];

			$customGateways = mainConfiguration::getInstance()->get('system', 'gateways');

			if (is_array($customGateways)) {
				foreach ($customGateways as $pair) {
					$gateways[] = explode('-', $pair);
				}
			}

			$module = $this->getCurrentModule();
			$method = $this->getCurrentMethod();

			return in_array([$module, $method], $gateways);
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны админки
		 * @throws Exception
		 * @return array
		 */
		private function prepareAdminSideGlobalVariables() {
			if (!$this->isGateway() && mb_strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
				$security = Service::Protection();
				$security->checkReferrer();
				$security->checkCsrf();
			}

			$permissions = permissionsCollection::getInstance();
			$domains = Service::DomainCollection();
			$regedit = Service::Registry();
			$session = Service::Session();
			$sessionLifeTime = $session->getMaxActiveTime();
			$domain = Service::DomainDetector()->detect();
			$language = Service::LanguageDetector()->detect();

			$result = [
				'@module' => $this->getCurrentModule(),
				'@method' => $this->getCurrentMethod(),
				'@lang' => $language->getPrefix(),
				'@lang-id' => $language->getId(),
				'@pre-lang' => $this->getPreLang(),
				'@domain' => $domain->getHost(),
				'@domain-id' => $domain->getId(),
				'@session-lifetime' => $sessionLifeTime,
				'@system-build' => $this->getSystemBuild(),
				'@referer-uri' => $this->getCalculatedRefererUri(),
				'@user-id' => Service::Auth()->getUserId(),
				'@interface-lang' => ulangStream::getLangPrefix(),
				'@csrf' => $session->get('csrf_token')
			];

			if (isDemoMode()) {
				$result['@demo'] = 1;
			}

			$requestUri = getServer('REQUEST_URI');

			if ($requestUri) {
				$requestUriInfo = parse_url($requestUri);
				$requestUri = getArrayKey($requestUriInfo, 'path');
				$queryParams = getArrayKey($requestUriInfo, 'query');
				if ($queryParams) {
					parse_str($queryParams, $queryParamsArr);
					if (isset($queryParamsArr['p'])) {
						unset($queryParamsArr['p']);
					}
					if (isset($queryParamsArr['xmlMode'])) {
						unset($queryParamsArr['xmlMode']);
					}

					$queryParams = http_build_query($queryParamsArr, '', '&');
					if ($queryParams) {
						$requestUri .= '?' . $queryParams;
					}
				}
				$result['@request-uri'] = $requestUri;
			}

			$result['@edition'] = defined('CURRENT_VERSION_LINE') ? CURRENT_VERSION_LINE : '';
			$result['@disableTooManyChildsNotification'] = (int) mainConfiguration::getInstance()->get(
				'system',
				'disable-too-many-childs-notification'
			);

			$isUserAdmin = $permissions->isAdmin();
			$buffer = Service::Response()
				->getCurrentBuffer();

			switch (true) {
				case ($isUserAdmin && $permissions->isAllowedDomain($result['@user-id'], $result['@domain-id']) == 0): {
					$result['data'] = new requreMoreAdminPermissionsException(getLabel('error-require-more-permissions'));
					$buffer->status('403 Forbidden');
					break;
				}

				case system_is_allowed($this->getCurrentModule(), $this->getCurrentMethod()): {
					try {
						$module = $this->getModule($this->getCurrentModule());

						if ($module) {
							$module->cms_callMethod($this->getCurrentMethod(), null);
						}

						$result['data'] = $this->adminDataSet;
					} catch (publicException $e) {
						$result['data'] = $e;
					}

					break;
				}

				case $isUserAdmin: {
					$result['data'] = new requreMoreAdminPermissionsException(getLabel('error-require-more-permissions'));
					$buffer->status('403 Forbidden');
					break;
				}

				case ($this->getCurrentModule() != 'events' && $this->getCurrentMethod() != 'last'): {
					$buffer->status('403 Forbidden');
					break;
				}
			}

			if (($domainFloated = getRequest('domain')) !== null) {
				$result['@domain-floated'] = $domainFloated;
				$result['@domain-floated-id'] = $domains->getDomainId($domainFloated);
			} else {
				if ($this->currentEditElementId) {
					$element = umiHierarchy::getInstance()->getElement($this->currentEditElementId);
					if ($element instanceof iUmiHierarchyElement) {
						$domain = $domains->getDomain($element->getDomainId());

						if ($domain instanceof iDomain) {
							$result['@domain-floated'] = $domainFloated = $domain->getHost();
						}
					}
				} else {
					$result['@domain-floated'] = $result['@domain'];
				}
			}

			return $result;
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны сайта для режима TPL.
		 * @throws coreException если нет модуля пользователи
		 * @return array
		 */
		private function prepareClientSideGlobalVariablesForTPL() {
			$permissions = permissionsCollection::getInstance();

			$currentModule = $this->getCurrentModule();
			$currentMethod = $this->getCurrentMethod();
			$elementId = $this->getCurrentElementId();
			$userId = Service::Auth()->getUserId();

			// check permissions
			$notPermitted = true;
			if ($permissions->isAllowedMethod($userId, $currentModule, $currentMethod)) {
				$notPermitted = false;
				if ($elementId) {
					list($r) = $permissions->isAllowedObject($userId, $elementId);
					$notPermitted = !$r;
				}
			}

			// если нет прав на текущую страницу либо на доступ к текущему методу
			if ($notPermitted) {
				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->status('401 Unauthorized');
				$this->setCurrentModule('users');
				$this->setCurrentMethod('login');

				$moduleUsers = $this->getModule('users');

				/** @var users|UsersMacros $moduleUsers */
				if (!$moduleUsers) {
					throw new coreException('Module "users" not found.');
				}

				return ['content' => $moduleUsers->login()];
			}

			$module = $this->getModule($currentModule);
			try {
				$content = $module->cms_callMethod($currentMethod, []);
			} catch (publicException $e) {
				$content = $e->getMessage();
			}

			return ['content' => $content];
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны сайта для режима XSLT.
		 * @return array
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws ErrorException
		 */
		private function prepareClientSideGlobalVariablesForXSLT() {
			if ($this->useOnlyBaseClientVariables()) {
				return $this->getBaseClientVariables();
			}

			$globalVariables = [];

			$permissions = permissionsCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$elementId = $this->getCurrentElementId();
			$currentModule = $this->getCurrentModule();
			$currentMethod = $this->getCurrentMethod();

			$isAllowed = false;

			if ($permissions->isAllowedMethod($userId, $currentModule, $currentMethod)) {
				$isAllowed = true;

				if ($elementId) {
					list($readPermissions) = $permissions->isAllowedObject($userId, $elementId);

					if (!$readPermissions) {
						$isAllowed = false;
						$globalVariables['attribute:not-permitted'] = 1;
					}
				}
			}

			if (!$isAllowed) {
				$currentModule = 'users';
				$currentMethod = 'login';
				$this->setCurrentModule($currentModule);
				$this->setCurrentMethod($currentMethod);
			}

			$this->currentHeader = def_module::parseTPLMacroses(macros_header());
			$globalVariables = array_merge($globalVariables, $this->getBaseClientVariables());

			if (getRequest('p') !== null) {
				$globalVariables['@paging'] = 'yes';
			}

			/** @var social_networks $socialModule */
			$socialModule = $cmsController->getModule('social_networks');

			if ($socialModule && ($currentSocial = $socialModule->getCurrentSocial())) {
				$globalVariables['@socialId'] = $currentSocial->getId();
				$_REQUEST['template_id'] = (int) $currentSocial->getValue('template_id');
				$this->getCurrentTemplater(true);
			}

			$requestUri = getServer('REQUEST_URI');

			if ($requestUri) {
				$requestUriInfo = @parse_url($requestUri);
				$requestUri = getArrayKey($requestUriInfo, 'path');
				$queryParams = getArrayKey($requestUriInfo, 'query');

				if ($socialModule && ($currentSocial = $socialModule->getCurrentSocial())) {
					$queryParams = '';
				}

				if ($queryParams) {
					parse_str($queryParams, $queryParamsArr);

					if (isset($queryParamsArr['p'])) {
						unset($queryParamsArr['p']);
					}

					if (isset($queryParamsArr['xmlMode'])) {
						unset($queryParamsArr['xmlMode']);
					}

					$queryParams = http_build_query($queryParamsArr, '', '&');

					if ($queryParams) {
						$requestUri .= '?' . $queryParams;
					}
				}

				$globalVariables['@request-uri'] = $requestUri;
			}

			$userInfo = [];
			$userId = $auth->getUserId();
			$userInfo['@id'] = $userId;
			$userType = 'guest';

			if (Service::Auth()->isAuthorized() && ($user = $objectsCollection->getObject($userId))) {
				if (getRequest('mobile_application') == 'true') {
					if (!Service::Registry()->get('//modules/emarket/')) {
						$globalVariables['data']['error'] = getLabel('error-module-emarket-absent');
						return $globalVariables;
					}

					if (!$permissions->isAllowedMethod($userId, 'emarket', 'mobile_application_get_data')) {
						$globalVariables['data']['error'] = getLabel('error-mobile-application-not-allowed');
						return $globalVariables;
					}
				}

				/** @var iUmiObject $user */
				$userType = 'user';
				$userInfo['@status'] = 'auth';
				$userInfo['@login'] = $user->getValue('login');
				$userInfo['xlink:href'] = $user->getXlink();

				if ($permissions->isAdmin()) {
					$userType = 'admin';

					if ($permissions->isSv()) {
						$userType = 'sv';
					}
				}
			}

			$userInfo['@type'] = $userType;

			/** @var geoip|false $geoip */
			$geoip = $this->getModule('geoip');

			if ($geoip) {
				$geoInfo = $geoip->lookupIp();

				if (isset($geoInfo['special'])) {
					$userInfo['geo'] = ['special' => $geoInfo['special']];
				} else {
					$userInfo['geo'] = [
						'country' => $geoInfo['country'],
						'region' => $geoInfo['region'],
						'city' => $geoInfo['city'],
						'latitude' => $geoInfo['lat'],
						'longitude' => $geoInfo['lon']
					];
				}
			}

			$globalVariables['user'] = $userInfo;

			if ($elementId && ($element = $hierarchy->getElement($elementId))) {
				$parentElements = $hierarchy->getAllParents($elementId);
				$parentsInfo = [];

				foreach ($parentElements as $parentElementId) {
					if ($parentElementId == 0) {
						continue;
					}

					$parentElement = $hierarchy->getElement($parentElementId);

					if ($parentElement) {
						$parentsInfo[] = $parentElement;
					}
				}

				$globalVariables += [
					'@pageId' => $elementId,
					'parents' => [
						'+page' => $parentsInfo
					],
					'full:page' => $element
				];

				def_module::pushEditable($currentModule, $currentMethod, $elementId);
			} elseif ($currentModule == 'content' && $currentMethod == 'content') {
				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->status('404 Not Found');
				$globalVariables['@method'] = 'notfound';
			} elseif ($isAllowed && $this->isAllowedExecuteMethod($currentModule, $currentMethod)) {

				try {
					$pathParts = Service::Request()->getPathParts();

					if (isset($pathParts[0]) && $pathParts[0] == Service::LanguageDetector()->detectPrefix()) {
						$pathParts = array_slice($pathParts, 1);
					}

					if (umiCount($pathParts) < 2) {
						throw new coreException('Invalid udata path');
					}

					$pathParts[0] = $currentModule;
					$pathParts[1] = $currentMethod;

					if ($this->canReturnArrayFromMacrosExecution()) {
						$module = $cmsController->getModule($currentModule);
						$pathParts = array_slice($pathParts, 2);
						$globalVariables['data'] = call_user_func_array([$module, $currentMethod], $pathParts);
					} else {
						$path = 'udata://' . implode('/', $pathParts);
						$globalVariables['xml:data'] = $this->executeStream($path);
					}
				} catch (publicException $e) {
					$globalVariables['data'] = $e;
				}
			}

			return $globalVariables;
		}

		/**
		 * Может ли результат выполнения макроса добавляться в глобальные переменные шаблона
		 * в виде массива (вместо xml-строки)? Имеет смысл только на PHP-шаблонизаторе.
		 * @return bool
		 * @throws coreException
		 */
		private function canReturnArrayFromMacrosExecution() {
			$umiConfig = mainConfiguration::getInstance();
			$flag = (bool) $umiConfig->get('system', 'return-array-from-macros-execution');
			return $flag && ($this->getCurrentTemplater() instanceof umiTemplaterPHP);
		}

		/**
		 * Возвращает базовые глобальные переменные для клиентской части, то есть,
		 * те которые теоретически могут использоваться на каждой странице
		 * @return array
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function getBaseClientVariables() {
			$domain = Service::DomainDetector()->detect();
			$language = Service::LanguageDetector()->detect();
			$variables = [
				'@module' => $this->getCurrentModule(),
				'@method' => $this->getCurrentMethod(),
				'@domain' => $domain->getHost(),
				'@domain-id' => $domain->getId(),
				'@system-build' => $this->getSystemBuild(),
				'@lang' => $language->getPrefix(),
				'@lang-id' => $language->getId(),
				'@pre-lang' => $this->getPreLang(),
				'@header' => def_module::parseTPLMacroses(macros_header()),
				'@title' => def_module::parseTPLMacroses(macros_title()),
				'@site-name' => def_module::parseTPLMacroses(macros_sitename()),
				'@csrf' => Service::Session()->get('csrf_token'),
				'meta' => [
					'keywords' => macros_keywords(),
					'description' => macros_describtion()
				],

			];

			$template = $this->getCurrentTemplate();
			if ($template instanceof iTemplate) {
				$variables['@template-id'] = $template->getId();
			}

			if (isDemoMode()) {
				$variables['@demo'] = 1;
			}

			if ($this->getCurrentElementId()) {
				$variables['@pageId'] = $this->getCurrentElementId();
				$isDefault = ($variables['@pageId'] === umiHierarchy::getInstance()->getDefaultElementId());
				$variables['@is-default'] = $isDefault;
			}

			return $variables;
		}

		/**
		 * Возвращает билд системы
		 * @return string
		 */
		private function getSystemBuild() {
			return Service::RegistrySettings()->getRevision();
		}

		/**
		 * Определяет нужно ли использовать только базовые глобальные переменные для клиентской части
		 * в рамках общих данных для шаблонизации
		 * @return bool
		 */
		private function useOnlyBaseClientVariables() {
			return (bool) mainConfiguration::getInstance()
				->get('system', 'use-only-base-client-variables');
		}

		/**
		 * Определяет текущий шаблонизатор.
		 * @return umiTemplater
		 * @throws coreException
		 */
		private function detectCurrentTemplater() {
			if (defined('VIA_HTTP_SCHEME') && VIA_HTTP_SCHEME) {
				return $this->current_templater = $this->initHTTPSchemeModeTemplater();
			}

			if (Service::Request()->isAdmin()) {
				return $this->current_templater = $this->initAdminModeTemplater();
			}

			return $this->current_templater = $this->initSiteModeTemplater();
		}

		/**
		 * Обрабатывает url если включена настройка регистрозависимости
		 * @param iOutputBuffer $buffer текущий буфер
		 * @param string $url адрес
		 * @throws coreException
		 */
		private function processCaseSensitiveUrl(iOutputBuffer $buffer, $url) {
			$domainId = Service::DomainDetector()->detectId();
			$languageId = Service::LanguageDetector()->detectId();
			$registry = Service::Registry();

			switch ($registry->get("//settings/seo/$domainId/$languageId/case-sensitive-status")) {
				case 'redirect': {
					$buffer->status('301 Moved Permanently');
					$buffer->redirect($url);
					break;
				}

				case 'not-found': {
					$buffer->status('404 Not Found');
					break;
				}
			}
		}

		/**
		 * Добавляет суффикс к пути
		 * @param string $path путь url
		 * @return string
		 */
		private function addSuffixToPath($path) {
			$configuration = mainConfiguration::getInstance();

			if (!$configuration->get('seo', 'url-suffix.add')) {
				return $path;
			}

			$suffix = $configuration->get('seo', 'url-suffix');
			return endsWith($path, $suffix) ? $path : ($path . $suffix);
		}

		/**
		 * Проверяет чувствительны ли alt-name элементов к регистру
		 * @return string
		 * @throws coreException
		 */
		private function isCaseSensitiveAltName() {
			$domainId = Service::DomainDetector()->detectId();
			$languageId = Service::LanguageDetector()->detectId();
			return Service::Registry()->get("//settings/seo/$domainId/$languageId/case-sensitive");
		}

		/**
		 * Инициализируем шаблонизатор для режима работы VIA_HTTP_SCHEME
		 * @return umiTemplater
		 * @throws coreException
		 */
		private function initHTTPSchemeModeTemplater() {
			outputBuffer::contentGenerator('XSLT, HTTP SCHEME MODE');

			return umiTemplater::create('XSLT');
		}

		/**
		 * Инициализируем шаблонизатор для Site Mode,
		 * определяем шаблон и возвращаем инстанс соответсвующего шаблонизатора
		 * @return umiTemplater
		 * @throws coreException
		 */
		private function initSiteModeTemplater() {
			/** @var social_networks $socialNetwork */
			$socialNetwork = $this->getModule('social_networks');

			if ($socialNetwork instanceof social_networks && $socialNetwork->getCurrentSocial() && !getRequest('template_id')) {
				return $this->createDefaultSocialNetWorkTemplate();
			}

			$template = $this->detectCurrentDesignTemplate();

			// шаблон не определен, выдаем ошибку, завершаем работу
			if (!$template instanceof iTemplate) {
				debug_print_backtrace();
				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->clear();
				$buffer->status(500);
				$buffer->push(file_get_contents(SYS_ERRORS_PATH . 'no_design_template.html'));
				$buffer->end();
			}

			$templaterType = $template->getType();

			if ($templaterType == 'xsl') {
				$templaterType = 'XSLT';
			}
			if ($templaterType == 'tpls') {
				$templaterType = 'TPL';
			}

			$templaterType = mb_strtoupper($templaterType);
			outputBuffer::contentGenerator($templaterType . ', SITE MODE');

			try {
				$config = mainConfiguration::getInstance();
				$config->loadConfig($template->getConfigPath());
				$config->setReadOnlyConfig();
			} catch (Exception $exception) {
				//nothing
			}

			return umiTemplater::create($templaterType, $template->getFilePath());
		}

		/**
		 * Создает шаблон приложения для социальной сети по умолчанию
		 * @return umiTemplater
		 * @throws coreException
		 */
		private function createDefaultSocialNetWorkTemplate() {
			return umiTemplater::create('XSLT', CURRENT_WORKING_DIR  . '/xsl/social/vkontakte.xsl');
		}

		/**
		 * Инициализируем шаблонизатор для Admin Mode
		 * @throws coreException
		 */
		private function initAdminModeTemplater() {
			$config = mainConfiguration::getInstance();
			$skinPath = $config->includeParam('templates.skins', ['skin' => system_get_skinName()]);
			$permissions = permissionsCollection::getInstance();
			$userId = Service::Auth()->getUserId();
			$isAllowed = $permissions->isAllowedMethod($userId, $this->getCurrentModule(), $this->getCurrentMethod());

			// TODO: вынести в конфиг все названия шаблонов
			$fileName = 'main.xsl';

			if (!$permissions->isAdmin(false, true) || !$isAllowed) {
				$fileName = 'main_login.xsl';
			}

			$templateSource = $skinPath . $fileName;

			if (!is_file($templateSource)) {
				throw new coreException('Template "' . $templateSource . '" not found.');
			}

			outputBuffer::contentGenerator('XSLT, ADMIN MODE');

			return umiTemplater::create('XSLT', $templateSource);
		}

		private function getSubPathType($sub_path) {
			if (!$this->getCurrentModule()) {
				if (Service::Registry()->get('//modules/' . $sub_path)) {
					$this->setCurrentModule($sub_path);

					return 'MODULE';
				}
			}

			if ($this->getCurrentModule() && !$this->getCurrentMethod()) {
				$this->setCurrentMethod($sub_path);

				return 'METHOD';
			}

			if ($this->getCurrentModule() && $this->getCurrentMethod()) {
				return 'PARAM';
			}

			return 'UNKNOWN';
		}

		private function reset() {
			$this->setCurrentModule('');
			$this->setCurrentMethod('');

			for ($i = 0; $i < 10; $i++) {
				if (isset($_REQUEST['param' . $i])) {
					unset($_REQUEST['param' . $i]);
				} else {
					break;
				}
			}
		}

		/**
		 * Определяет текущий языковой префикс
		 * @throws coreException
		 */
		private function detectPreLang() {
			$currentLanguage = Service::LanguageDetector()->detect();
			$currentDomain = Service::DomainDetector()->detect();

			if ($currentLanguage->getId() != $currentDomain->getDefaultLangId()) {
				$languagePrefix = '/' . $currentLanguage->getPrefix();
			} else {
				$languagePrefix = '';
			}

			$_REQUEST['pre_lang'] = $languagePrefix;
			$this->setPreLang($languagePrefix);
		}

		/**
		 * Загружает языковые константы для шаблонов сайта из файлов вида lang.*.php
		 * @return array
		 */
		private function loadLangConstantList() {
			$moduleList = $this->getModulesList();

			try {
				$langPrefix = Service::LanguageDetector()->detectPrefix();
			} catch (coreException $exception) {
				umiExceptionHandler::report($exception);
				return [];
			}

			$loader = new I18nFilesLoader($moduleList, $langPrefix);

			try {
				$directoryPath = $this->getResourcesDirectory();
				$loader->setTemplatePath($directoryPath);
			} catch (Exception $exception) {
				umiExceptionHandler::report($exception);
			}

			return $loader->loadLangConstants();
		}

		/** @deprecated */
		public static function isCSRFTokenValid() {
			try {
				return Service::Protection()->checkCsrf();
			} catch (\UmiCms\System\Protection\CsrfException $e) {
				return false;
			}
		}

		/** @deprecated */
		public function getLang() {
			return Service::LanguageDetector()->detect();
		}

		/** @deprecated */
		public function setLang(iLang $lang) {
			trigger_error('Method is not working anymore', E_USER_WARNING);
			return $this;
		}

		/** @deprecated */
		public function getSkinPath() {
			return null;
		}

		/** @deprecated */
		public function loadBuildInModule($moduleName) {
			return null;
		}

		/** @deprecated */
		public function loadExtLang($moduleName) {
			return null;
		}

		/** @deprecated */
		public function loadLangs() {
			return null;
		}

		/** @deprecated */
		public function getCurrentMode() {
			return Service::Request()
				->mode();
		}

		/** @deprecated */
		public function setCurrentMode($mode) {
			trigger_error('Method is not working anymore', E_USER_WARNING);
			return $this;
		}

		/** @deprecated */
		public function getAdminModeId() {
			return \UmiCms\System\Request\Mode\iDetector::ADMIN_MODE;
		}

		/** @deprecated */
		public function isCurrentModeAdmin() {
			return Service::Request()
				->isAdmin();
		}

		/** @deprecated */
		public function getCurrentDomain() {
			return Service::DomainDetector()->detect();
		}

		/** @deprecated */
		public function setCurrentDomain(iDomain $domain) {
			trigger_error('Method is not working anymore', E_USER_WARNING);
			return $this;
		}

		/** @deprecated */
		public function getCurrentLang() {
			return Service::LanguageDetector()->detect();
		}

		/** @deprecated */
		public function setCurrentLang(iLang $lang) {
			trigger_error('Method is not working anymore', E_USER_WARNING);
			return $this;
		}

		/** @deprecated */
		public function setCurrentTemplater() {
			return $this->getCurrentTemplater();
		}
	}
