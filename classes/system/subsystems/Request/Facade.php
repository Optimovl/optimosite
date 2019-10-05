<?php

	namespace UmiCms\System\Request;

	use UmiCms\System\Request\Http\iRequest;
	use UmiCms\System\Request\Mode\iDetector as ModeDetector;
	use UmiCms\System\Request\Path\iResolver as PathResolver;
	use UmiCms\Utils\Browser\iDetector as BrowserDetector;

	/**
	 * Класс фасада запроса
	 * @package UmiCms\System\Request
	 */
	class Facade implements iFacade {

		/** @var iRequest $request http запрос */
		private $request;

		/** @var BrowserDetector $browserDetector определитель параметров браузера */
		private $browserDetector;

		/** @var ModeDetector $modeDetector определитель режима работы системы */
		private $modeDetector;

		/** @var PathResolver $pathResolver распознаватель обрабатываемого пути */
		private $pathResolver;

		/** @var string|null $queryHash хеш от query */
		private $queryHash;

		/** @inheritdoc */
		public function __construct(
			iRequest $request,
			BrowserDetector $browserDetector,
			ModeDetector $modeDetector,
			PathResolver $pathResolver
		) {
			$this->request = $request;
			$this->browserDetector = $browserDetector;
			$this->modeDetector = $modeDetector;
			$this->pathResolver = $pathResolver;
		}

		/** @inheritdoc */
		public function Cookies() {
			return $this->getRequest()
				->Cookies();
		}

		/** @inheritdoc */
		public function Server() {
			return $this->getRequest()
				->Server();
		}

		/** @inheritdoc */
		public function Post() {
			return $this->getRequest()
				->Post();
		}

		/** @inheritdoc */
		public function Get() {
			return $this->getRequest()
				->Get();
		}

		/** @inheritdoc */
		public function Files() {
			return $this->getRequest()
				->Files();
		}

		/** @inheritdoc */
		public function getPath() {
			return $this->getPathResolver()
				->get();
		}

		/** @inheritdoc */
		public function getPathParts() {
			return $this->getPathResolver()
				->getParts();
		}

		/** @inheritdoc */
		public function isStream() {
			return $this->Get()->isExist('scheme');
		}

		/** @inheritdoc */
		public function getStreamScheme() {
			return $this->Get()->get('scheme');
		}

		/** @inheritdoc */
		public function isJson() {
			if ($this->isStream()) {
				return contains($this->getPath(), '.json');
			}

			return $this->Get()->get('jsonMode') === 'force';
		}

		/** @inheritdoc */
		public function isXml() {
			if ($this->isStream()) {
				return !$this->isJson();
			}

			return $this->Get()->get('xmlMode') === 'force';
		}

		/** @inheritdoc */
		public function isHtml() {
			return (!$this->isJson() && !$this->isXml());
		}

		/** @inheritdoc */
		public function isMobile() {
			$cookies = $this->Cookies();

			if ($cookies->isExist('is_mobile')) {
				return (bool) $cookies->get('is_mobile');
			}

			$detector = $this->getBrowserDetector();

			try {
				return ($detector->isMobile() || $detector->isTablet());
			} catch (\Exception $e) {
				return false;
			}
		}

		/** @inheritdoc */
		public function isLocal() {
			$saas = $this->Server()->get('SAAS');		
			$isSaas = isset($saas) ? contains($saas, 'umi') : false;

			return ($this->isLocalIp() && $this->isLocalHost()) || $isSaas;
		}

		/** @inheritdoc */
		public function getBrowser() {
			return $this->getBrowserDetector()
				->getBrowser();
		}

		/** @inheritdoc */
		public function getPlatform() {
			return $this->getBrowserDetector()
				->getPlatform();
		}

		/** @inheritdoc */
		public function isRobot() {
			return $this->getBrowserDetector()
				->isRobot();
		}

		/** @inheritdoc */
		public function isStreamCallStack() {
			return (bool) $this->Get()->get('showStreamsCalls');
		}

		/** @inheritdoc */
		public function method() {
			return $this->getRequest()
				->method();
		}

		/** @inheritdoc */
		public function isPost() {
			return $this->getRequest()
				->isPost();
		}

		/** @inheritdoc */
		public function isGet() {
			return $this->getRequest()
				->isGet();
		}

		/** @inheritdoc */
		public function isAdmin() {
			return $this->getModeDetector()
				->isAdmin();
		}

		/** @inheritdoc */
		public function isNotAdmin() {
			return !$this->isAdmin();
		}

		/** @inheritdoc */
		public function isSite() {
			return $this->getModeDetector()
				->isSite();
		}

		/** @inheritdoc */
		public function isCli() {
			return $this->getModeDetector()
				->isCli();
		}

		/** @inheritdoc */
		public function mode() {
			return $this->getModeDetector()
				->detect();
		}

		/** @inheritdoc */
		public function host() {
			return $this->getRequest()
				->host();
		}

		/** @inheritdoc */
		public function userAgent() {
			return $this->getRequest()
				->userAgent();
		}

		/** @inheritdoc */
		public function remoteAddress() {
			return $this->getRequest()
				->remoteAddress();
		}

		/** @inheritdoc */
		public function serverAddress() {
			return $this->getRequest()
				->serverAddress();
		}

		/** @inheritdoc */
		public function uri() {
			return $this->getRequest()
				->uri();
		}

		/** @inheritdoc */
		public function query() {
			return $this->getRequest()
				->query();
		}

		/** @inheritdoc */
		public function documentRoot() {
			return $this->Server()->get('DOCUMENT_ROOT');
		}

		/** @inheritdoc */
		public function queryHash() {
			if ($this->queryHash !== null) {
				return $this->queryHash;
			}

			$query = '';
			$matches = [];
			$success = preg_match('/([\?|\&][^\/#]*)/', $this->query(), $matches);

			if ($success && isset($matches[0])) {
				$query = $matches[0];
			}

			return $this->queryHash = md5($query);
		}

		/** @inheritdoc */
		public function getRawBody() {
			return $this->getRequest()
				->getRawBody();
		}

		/** @inheritdoc */
		public function isAjax() {
			$ajaxParam = $this->Server()->get('HTTP_X_REQUESTED_WITH');
			return isset($ajaxParam) && strtolower($ajaxParam) === 'xmlhttprequest';
		}

		/**
		 * Возвращает класс http запроса
		 * @return iRequest
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает определитель параметров браузера
		 * @return BrowserDetector
		 */
		private function getBrowserDetector() {
			if (!$this->browserDetector->getUserAgent()) {
				$this->browserDetector->setUserAgent($this->userAgent());
			}

			return $this->browserDetector;
		}

		/**
		 * Возвращает определитель режима работы системы
		 * @return ModeDetector
		 */
		private function getModeDetector() {
			return $this->modeDetector;
		}

		/**
		 * Возвращает распознаватель обрабатываемого пути
		 * @return PathResolver
		 */
		private function getPathResolver() {
			return $this->pathResolver;
		}

		/**
		 * Определяет является ли ip адрес сервера адресом локального сервера
		 * @return bool
		 */
		private function isLocalIp() {
			return contains($this->serverAddress(), '127.0.0.') || contains($this->serverAddress(), '::1');
		}

		/**
		 * Определяет является ли хост локальным
		 * @return bool
		 */
		private function isLocalHost() {
			$isFirstLevelDomain = preg_match('|^[^\.]*$|', $this->host());
			return $isFirstLevelDomain || $this->isLocalDomainZone();
		}

		/**
		 * Определяет является ли доменная зона хоста локальной
		 * @return bool
		 */
		private function isLocalDomainZone() {
			return (bool) preg_match('/\.(loc|local|localhost)$/', $this->host());
		}
	}
