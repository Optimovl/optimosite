<?php

	use UmiCms\Service;

	/** @deprecated */
	class staticCache {

		protected $config, $enabled, $splitLevel = 5,
			$requestUri, $isAdmin = false, $cacheFolder, $cacheFilePath;

		public function __construct() {
			$this->config = mainConfiguration::getInstance();
			$this->enabled = (bool) $this->config->get('cache', 'static.enabled');

			if ($this->enabled) {
				$folder = $this->config->includeParam('system.static-cache');
				$temp_path = $this->config->includeParam('sys-temp-path');

				if (!$folder) {
					$folder = $temp_path . '/static-cache/';
				}

				if (mb_substr($folder, -1, 1) != '/') {
					$folder .= '/';
				}

				$folder .= preg_replace('/^www\./i', '', getServer("HTTP_HOST") . "/");
				$this->setRequestUri(getServer('REQUEST_URI'));
				$this->setCacheFolder($folder);
				$this->cacheFilePath = $this->prepareCacheFile();
			}
		}

		public function setRequestUri($requestUri) {
			$requestUri = trim($requestUri, '/');

			if (!$requestUri) {
				$requestUri = '__splash';
			}

			$this->requestUri = $requestUri;

			$isAdmin = false;

			if (mb_substr($requestUri, 0, mb_strlen('admin')) == 'admin') {
				$isAdmin = true;
			} elseif (mb_substr($requestUri, 3, mb_strlen('admin')) == 'admin') {
				$isAdmin = true;
			}

			$this->isAdmin = $isAdmin;
		}

		public function setCacheFolder($cacheFolder) {
			if (is_dir($cacheFolder)) {
				$this->cacheFolder = $cacheFolder;
				return true;
			}

			mkdir($cacheFolder, 0777, true);

			if (is_dir($cacheFolder)) {
				$this->cacheFolder = $cacheFolder;
				return true;
			}

			return false;
		}

		public function cleanup() {
			$this->deleteElementsRelatedPages();
		}

		protected function deleteElementsRelatedPages() {
			$hierarchy = umiHierarchy::getInstance();
			$updatedElements = $hierarchy->getUpdatedElements();

			foreach ($updatedElements as $elementId) {
				$this->deleteElementRelatedPages($elementId);
			}
		}

		protected function deleteElementRelatedPages($elementId) {
			$this->enabled = (bool) $this->config->get('cache', 'static.enabled');

			if (!$this->enabled) {
				return false;
			}

			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($elementId);

			if (!$element instanceOf umiHierarchyElement) {
				return false;
			}

			$folder = $this->config->includeParam('system.static-cache');
			$temp_path = $this->config->includeParam('sys-temp-path');

			if (!$folder) {
				$folder = $temp_path . '/static-cache/';
			}

			if (mb_substr($folder, -1, 1) != '/') {
				$folder .= '/';
			}

			$domainsCollection = domainsCollection::getInstance();
			$domain = $domainsCollection->getDomain($element->getDomainId());

			if (!$domain instanceof domain) {
				return false;
			}

			$domain = $domain->getHost();

			$folder .= preg_replace('/^www\./i', '', $domain . "/");

			if ($this->isMobileCache()) {
				$folder .= 'mobile/';
			}

			$pageAddress = ($element->getIsDefault()) ? '' : $hierarchy->getPathById($elementId);

			$folder .= $pageAddress;
			$file = $folder . 'index.html';

			$this->deleteFileIfExists($file);
			$this->deleteFolderIfEmpty(dirname($file));
		}

		protected function deleteFolderIfEmpty($path) {
			$path = realpath($path);

			if (!is_dir($path)) {
				return false;
			}

			$dir = opendir($path);

			while (($obj = readdir($dir)) !== false) {
				if ($obj == "." || $obj == "..") {
					continue;
				}

				return false;
			}

			if (is_writable($path)) {
				return false;
			}

			$parentPath = realpath($path . "/../");
			rmdir($path);
			$this->deleteFolderIfEmpty($parentPath);
			return true;
		}

		/**
		 * Возвращает время жизни статического кеша в секундах
		 * @return int
		 */
		public static function getExpireTime() {
			$umiConfig = mainConfiguration::getInstance();
			switch ($umiConfig->get('cache', 'static.mode')) {
				case 'test': {
					return 10;
				}
				case 'short': {
					return 10 * 60;
				}
				case 'long': {
					return 3600 * 2 * 365;
				}
				default: {
					return 3600 * 24;
				}
			}
		}

		public function isUserGuest() {
			return Service::Auth()
				->isLoginAsGuest();
		}

		public function isNeedToUseCache() {

			if (!$this->enabled) {
				return false;
			}

			if ($this->isAdmin) {
				return false;
			}

			if (!$this->isAllowedRequest()) {
				return false;
			}

			if (!$this->isUserGuest()) {
				return false;
			}

			if (count($_POST) > 0) {
				return false;
			}

			return true;
		}

		public function load() {
			$config = $this->config;
			$contentPath = $this->cacheFilePath;

			$cacheExists = file_exists($contentPath);
			$expire = self::getExpireTime();

			if ($cacheExists && (filemtime($contentPath) + $expire) < time()) {
				$this->deleteFileIfExists($contentPath);
				return false;
			}

			if (!$this->isNeedToUseCache() || !$cacheExists) {
				return false;
			}

			$content = trim(file_get_contents($contentPath));
			$contentType = 'text/html';

			if ($content) {
				if (!$config->get('cache', 'static.ignore-stat')) {
					$this->saveStatInfo();
				}

				/** @var HTTPOutputBuffer $buffer */
				$buffer = outputBuffer::current();
				$buffer->contentType($contentType);
				$buffer->charset('utf-8');
				$buffer->clear();
				$buffer->push($content);

				if ($config->get('cache', 'static.debug')) {
					$signature = <<<HTML
<!-- Load from static cache -->
HTML;
					$buffer->push($signature);
				}

				$buffer->end();
			}
		}

		/**
		 * Определяет нужно ли кешировать текущий запрос
		 * @return bool
		 */
		public function isAllowedRequest() {
			/** @var \UmiCms\System\Cache\Key\Validator\BlackList $validator */
			$validator = Service::CacheKeyValidatorFactory()
				->create('BlackList');
			return $validator->isValid($this->requestUri);
		}

		public function save($content) {
			if (!$this->isNeedToUseCache() || !$content) {
				return false;
			}

			$path = $this->cacheFilePath;
			file_put_contents($path, $content);
			@chmod($path, 0777);
		}

		protected function isMobileCache() {
			return (Service::Request()->isMobile() &&
				(bool) $this->config->get('cache', 'static.cache-for-mobile-devices'));
		}

		protected function prepareCacheFile() {
			$preparedDirPath = $this->cacheFolder;

			if ($this->requestUri != '__splash') {
				$preparedDirPath .= $this->requestUri;
			}

			if (mb_substr($preparedDirPath, -1) != '/') {
				$preparedDirPath .= '/';
			}

			if ($this->isMobileCache()) {
				$preparedDirPath .= 'mobile/';
			}

			$preparedFilePath = $preparedDirPath . 'index.html';

			if (!$this->isAdmin && $this->createDirectory($preparedDirPath) == false) {
				return false;
			}

			return $preparedFilePath;
		}

		protected function createDirectory($path) {
			$path = preg_replace('~/{2,}~', '/', $path);
			return is_dir($path) ? is_writable($path) : mkdir($path, 0777, true);
		}

		protected function deleteFileIfExists($filePath) {
			if (!is_file($filePath)) {
				return true;
			}

			return is_writable($filePath) ? unlink($filePath) : false;
		}

		protected function saveStatInfo() {
			$cmsController = cmsController::getInstance();
			$cmsController->analyzePath();
			$stat_inst = $cmsController->getModule("stat");

			if ($stat_inst instanceof stat) {
				$stat_inst->pushStat();
			}
		}
	}
