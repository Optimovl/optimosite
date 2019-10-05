<?php

use UmiCms\System\Hierarchy\Domain\iDetector;
use UmiCms\System\Response;

/**
	 * Коллекция и менеджер редиректов.
	 * Примеры использования можно посмотреть в методах:
	 *
	 *    1) UmiRedirectsAdmin::lists();
	 *  2) UmiRedirectsAdmin::add();
	 *  3) UmiRedirectsAdmin::edit();
	 *  4) UmiRedirectsAdmin::del();
	 *  5) UmiRedirectsAdmin::saveValue();
	 */
	class umiRedirectsCollection implements
		iUmiCollection,
		iRedirects,
		iUmiDataBaseInjector,
		iUmiConfigInjector,
		Response\iInjector,
		iUmiService,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tUmiService;
		use Response\tInjector;
		use tUmiConfigInjector;
		use tUmiConstantMapInjector;
		use tCommonCollection;
		use tClassConfigManager;

		/** @var string $urlSuffix суффикс для адреса */
		private $urlSuffix;

		/** @var array $autoCreateRedirectHandledEvents список имен событий, для которых модуль создает слушателей */
		private $autoCreateRedirectHandledEvents = [
			'systemModifyElement',
			'systemMoveElement'
		];

		/** @var string $collectionItemClass класс элементов данной коллекции */
		private $collectionItemClass = 'umiRedirect';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'Redirects',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false,
				],
				[
					'name' => 'SOURCE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TARGET_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'STATUS_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'MADE_BY_USER_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'title' => 'Made By user',
					'required' => true,
				],
			]
		];

		/** @var iDetector $domainDetector определитель домена */
		private $domainDetector;

		/** @var \iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/** @var \iLangsCollection $languageCollection коллекция языков */
		private $languageCollection;

		/** @inheritdoc */
		public static function getInstance($c = null) {
			$serviceContainer = ServiceContainerFactory::create();
			return $serviceContainer->get(self::getConfig()->get('service'));
		}

		/** @inheritdoc */
		public function add($source, $target, $status = 301, $madeByUser = false) {
			if ($source == $target) {
				return false;
			}

			$connection = $this->getConnection();
			$source = $connection->escape($this->parseUri($source));
			$target = $connection->escape($this->parseUri($target));
			$status = (int) $status;

			if (!umiRedirect::getRedirectMessage($status)) {
				return false;
			}

			$madeByUser = (int) $madeByUser;
			$connection->startTransaction('Adding new redirect records');
			$map = $this->getMap();

			try {
				$redirectsToSource = $this->get(
					[
						$map->get('TARGET_FIELD_NAME') => $source
					]
				);

				/** @var umiRedirect $redirectToSource */
				foreach ($redirectsToSource as $redirectToSource) {
					/** @var umiRedirect $redirect */
					$redirect = $this->create(
						[
							$map->get('SOURCE_FIELD_NAME') => $redirectToSource->getSource(),
							$map->get('TARGET_FIELD_NAME') => $target,
							$map->get('STATUS_FIELD_NAME') => $status,
							$map->get('MADE_BY_USER_FIELD_NAME') => $madeByUser
						]
					);

					if ($redirect->getSource() === $redirect->getTarget()) {
						$this->del($redirect->getId());
						$this->del($redirectToSource->getId());
					}
				}

				$this->delete(
					[
						$map->get('TARGET_FIELD_NAME') => $source
					]
				);

				$doublesExists = $this->isExists(
					[
						$map->get('TARGET_FIELD_NAME') => $target,
						$map->get('SOURCE_FIELD_NAME') => $source
					]
				);

				if ($doublesExists) {
					throw new Exception('Prevent doubles');
				}

				$this->create(
					[
						$map->get('TARGET_FIELD_NAME') => $target,
						$map->get('SOURCE_FIELD_NAME') => $source,
						$map->get('STATUS_FIELD_NAME') => $status,
						$map->get('MADE_BY_USER_FIELD_NAME') => $madeByUser
					]
				);
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				return false;
			}

			$connection->commitTransaction();
			return true;
		}

		/** @inheritdoc */
		public function getRedirectsIdBySource($source, $madeByUser = false) {
			$connection = $this->getConnection();
			$source = $connection->escape($this->parseUri($source));
			$madeByUser = (int) $madeByUser;
			$map = $this->getMap();
			$redirects = [];

			try {
				$result = $this->get(
					[
						$map->get('SOURCE_FIELD_NAME') => $source,
						$map->get('MADE_BY_USER_FIELD_NAME') => $madeByUser
					]
				);
			} catch (Exception $e) {
				return $redirects;
			}

			/** @var iUmiRedirect|iUmiCollectionItem $redirect */
			foreach ($result as $redirect) {
				$redirects[$redirect->getId()] = [
					$redirect->getSource(),
					$redirect->getTarget(),
					$redirect->getStatus()
				];
			}

			return $redirects;
		}

		/** @inheritdoc */
		public function getRedirectIdByTarget($target, $madeByUser = false) {
			$connection = $this->getConnection();
			$target = $connection->escape($this->parseUri($target));
			$madeByUser = (int) $madeByUser;
			$map = $this->getMap();
			$redirects = [];

			try {
				$result = $this->get(
					[
						$map->get('TARGET_FIELD_NAME') => $target,
						$map->get('MADE_BY_USER_FIELD_NAME') => $madeByUser
					]
				);
			} catch (Exception $e) {
				return $redirects;
			}

			/** @var iUmiRedirect|iUmiCollectionItem $redirect */
			foreach ($result as $redirect) {
				$redirects[$redirect->getId()] = [
					$redirect->getSource(),
					$redirect->getTarget(),
					$redirect->getStatus()
				];
			}

			return $redirects;
		}

		/** @inheritdoc */
		public function del($id) {
			$this->delete(
				[
					$this->getMap()->get('ID_FIELD_NAME') => $id
				]
			);

			return true;
		}

		/** @inheritdoc */
		public function redirectIfRequired($source, $madeByUser = false) {
			$connection = $this->getConnection();
			$source = $connection->escape($this->parseUri($source));
			$sourceCandidates = $this->getSourceCandidates($source);
			$map = $this->getMap();
			$madeByUser = (int) $madeByUser;

			try {
				$redirects = $this->get(
					[
						$map->get('SOURCE_FIELD_NAME') => $sourceCandidates,
						$map->get('MADE_BY_USER_FIELD_NAME') => $madeByUser
					]
				);
			} catch (Exception $e) {
				return false;
			}

			if (umiCount($redirects) > 0) {
				/** @var umiRedirect $redirect */
				$redirect = array_shift($redirects);
				$target = $this->parseUri($redirect->getTarget());
				$status = $redirect->getStatus();

				if (!preg_match('/^(https?:\/\/)/', $target)) {
					$target = '/' . $target;
				}

				if ($madeByUser || $this->isValidLink($target)) {
					$this->redirect($target, $status);
				}
			}

			$sourceParts = explode('/', trim($source, '/'));

			do {
				array_pop($sourceParts);
				$subSource = implode('/', $sourceParts) . '/';
				$subSource = $connection->escape($this->parseUri($subSource));

				if ($subSource === '') {
					if (umiCount($sourceParts) > 0) {
						continue;
					}
					break;
				}

				$subSourceCandidates = $this->getSourceCandidates($subSource);

				if ($subSourceCandidates == $sourceCandidates) {
					continue;
				}

				try {
					$subRedirects = $this->get(
						[
							$map->get('SOURCE_FIELD_NAME') => $subSourceCandidates,
							$map->get('MADE_BY_USER_FIELD_NAME') => $madeByUser
						]
					);
				} catch (Exception $e) {
					return false;
				}

				if (umiCount($subRedirects) > 0) {
					/** @var umiRedirect $subRedirect */
					$subRedirect = array_shift($subRedirects);
					$subSource = $subRedirect->getSource();
					$subTarget = $this->parseUri($subRedirect->getTarget());

					if ($source == $subTarget) {
						continue;
					}

					$sourceUriSuffix = mb_substr($source, mb_strlen($subSource));
					$subTarget .= $sourceUriSuffix;

					if (!preg_match('/^(https?:\/\/)/', $subTarget)) {
						$subTarget = '/' . $subTarget;
					}

					if ($this->isValidLink($subTarget)) {
						$this->redirect($subTarget, $subRedirect->getStatus());
					}
				}
			} while (umiCount($sourceParts) > 1);

			return false;
		}

		/** @inheritdoc */
		public function init() {
			$config = $this->getConfiguration();
			$map = $this->getMap();

			if ($config->get($map->get('CONFIG_SECTION'), $map->get('CONFIG_AUTO_CREATE_REDIRECT_ENABLE'))) {

				foreach ($this->getHandledEventsForAutoCreating() as $eventName) {
					new umiEventListener(
						$eventName,
						$map->get('AUTO_CREATE_REDIRECT_HANDLER_MODULE'),
						$map->get('AUTO_CREATE_REDIRECT_HANDLER_METHOD')
					);
				}
			}
		}

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/** @inheritdoc */
		public function setDomainDetector(iDetector $detector) {
			$this->domainDetector = $detector;
			return $this;
		}

		/** @inheritdoc */
		public function setDomainCollection(\iDomainsCollection $domainCollection) {
			$this->domainCollection = $domainCollection;
			return $this;
		}

		/** @inheritdoc */
		public function setLanguageCollection(\iLangsCollection $languageCollection) {
			$this->languageCollection = $languageCollection;
			return $this;
		}

		/**
		 * Возвращает разобранный адрес
		 * @param string $uri адрес
		 * @return mixed|string
		 */
		protected function parseUri($uri) {
			if ($uri === '/') {
				return $uri;
			}

			$uri = ltrim($uri, '/');
			$urlSuffix = $this->getUrlSuffix();

			if ($urlSuffix == '/') {
				return rtrim($uri, '/');
			}

			$suffix = addcslashes($urlSuffix, '\^.$|()[]*+?{},');
			$pattern = '/(' . $suffix . ')/';
			$cleanUri = preg_replace($pattern, '', $uri);
			return ($cleanUri === null) ? $uri : $cleanUri;
		}

		/**
		 * Возвращает суффикс адреса
		 * @return string
		 * @throws Exception
		 */
		protected function getUrlSuffix() {
			if ($this->urlSuffix !== null) {
				return $this->urlSuffix;
			}

			$configuration = $this->getConfiguration();
			$map = $this->getMap();

			if ($configuration->get($map->get('CONFIG_SECTION'), $map->get('CONFIG_URL_SUFFIX_ENABLE'))) {
				return $this->urlSuffix = (string) $configuration->get(
					$map->get('CONFIG_SECTION'),
					$map->get('CONFIG_URL_SUFFIX')
				);
			}

			return $this->urlSuffix = '/';
		}

		/**
		 * Добавляет суффикс к адресу
		 * @param string $url адрес
		 * @return string
		 */
		protected function appendUrlSuffix($url) {
			$urlSuffix = $this->getUrlSuffix();
			$urlInfo = parse_url($url);
			$urlPath = $urlInfo['path'];

			$suffixPosition = mb_strrpos($urlPath, $urlSuffix);
			$suffixFound = $suffixPosition !== false;
			$suffixInEndOfUrl = $suffixPosition + mb_strlen($urlSuffix) == mb_strlen($urlPath);
			$defaultPageUrl = $urlPath === '/';

			if (($suffixFound && $suffixInEndOfUrl) || $defaultPageUrl) {
				return $url;
			}

			$urlWithSuffix = rtrim($urlPath, '/') . $urlSuffix;
			$newUrl = '';

			if (isset($urlInfo['scheme']) && $urlInfo['scheme']) {
				$newUrl .= $urlInfo['scheme'] . '://';
			}

			if (isset($urlInfo['host']) && $urlInfo['host']) {
				$newUrl .= $urlInfo['host'];
			}

			$newUrl .= $urlWithSuffix;

			if (isset($urlInfo['query']) && $urlInfo['query']) {
				$newUrl .= '?' . $urlInfo['query'];
			}

			return $newUrl;
		}

		/**
		 * Производит редирект
		 * @param string $target куда перенаправлять
		 * @param int $status код статуса перенаправления
		 * @return bool
		 * @throws Exception
		 */
		protected function redirect($target, $status) {
			$statusMessage = umiRedirect::getRedirectMessage($status);

			if (!$statusMessage) {
				return false;
			}

			$configuration = $this->getConfiguration();

			if (!$this->isExternalLink($target) && $configuration->get('seo', 'url-suffix.add')) {
				$target = $this->appendUrlSuffix($target);
			}

			$buffer = $this->getResponse()
				->getCurrentBuffer();
			$referrer = getServer('HTTP_REFERER');

			if ($referrer) {
				$buffer->setHeader('Referrer', (string) $referrer);
			}

			$buffer->redirect($target, $status . ' ' . $statusMessage, $status);
			$buffer->end();
		}

		/**
		 * Возвращает список имен событий изменения страниц
		 * @return array
		 */
		protected function getHandledEventsForAutoCreating() {
			return $this->autoCreateRedirectHandledEvents;
		}

		/**
		 * Можно ли делать редирект на ссылку?
		 * @param string $target ссылка
		 * @return bool
		 */
		protected function isValidLink($target) {
			if ($this->isExternalLink($target)) {
				return true;
			}

			if (preg_match('/^https?:\/\/[^\/]*\/(\S*)/', $target, $matches)) {
				$path = $matches[1];
			} else {
				$path = $target;
			}

			$expectNotActivePages = false;
			$errorsCount = 0;
			$domainId = $this->getDomainCollection()
				->getDomainIdByUrl($target);
			$languageId = $this->getLanguageCollection()
				->getLanguageIdByUrl($target);
			$targetPageId = umiHierarchy::getInstance()
				->getIdByPath($path, $expectNotActivePages, $errorsCount, $domainId, $languageId);

			return ($targetPageId !== false);
		}

		/**
		 * Является ли ссылка внешней?
		 * @param string $target ссылка
		 * @return bool
		 */
		protected function isExternalLink($target) {
			return $this->getDomainCollection()->getDomainIdByUrl($target) === false;
		}

		/**
		 * Возвращает все возможные варианты источника редиректа (абсолютные и относительные)
		 * @param string $source путь до источника
		 * @return array
		 */
		private function getSourceCandidates($source) {
			return array_merge(
				$this->getAbsoluteSourceCandidates($source),
				$this->getRelativeSourceCandidates($source)
			);
		}

		/**
		 * Возвращает возможные комбинации написания абсолютного источника редиректа
		 * @param string $source источник редиректа
		 * @return array
		 */
		private function getAbsoluteSourceCandidates($source) {
			$domain = $this->getDomainDetector()->detect();
			$url = rtrim($domain->getCurrentUrl() . "/{$source}", '/');
			return [
				$url,
				"{$url}/"
			];
		}

		/**
		 * Возвращает возможные комбинации написания относительного источника редиректа
		 * @param string $source источник редиректа
		 * @return array
		 */
		private function getRelativeSourceCandidates($source) {
			if ($source === '/') {
				return [$source];
			}

			return [
				$source,
				"{$source}/",
				"/{$source}",
				"/{$source}/",
			];
		}

		/**
		 * @deprecated
		 * @throws Exception
		 */
		public function deleteAllRedirects() {
			$this->deleteAll();
		}

		/**
		 * Возвращает определитель домена
		 * @return iDetector
		 */
		private function getDomainDetector() {
			return $this->domainDetector;
		}

		/**
		 * Возвращает коллекцию доменов
		 * @return iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}

		/**
		 * Возвращает коллекцию языков
		 * @return iLangsCollection
		 */
		private function getLanguageCollection() {
			return $this->languageCollection;
		}
	}
