<?php

	namespace UmiCms\System\Hierarchy\Language;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Request\iFacade;

	/**
	 * Класс определителя запрошенного домена
	 * @package UmiCms\System\Hierarchy\Language
	 */
	class Detector implements iDetector {

		/** @var \iLangsCollection $languageCollection коллекция языков */
		private $languageCollection;

		/** @var DomainDetector $domainDetector определитель текущего домена */
		private $domainDetector;

		/** @var iFacade $request запрос */
		private $request;

		/** @var \iUmiHierarchy $pageCollection коллекция страниц */
		private $pageCollection;

		/** @inheritdoc */
		public function __construct(
			\iLangsCollection $languageCollection,
			DomainDetector $domainDetector,
			iFacade $request,
			\iUmiHierarchy $pageCollection
		) {
			$this->languageCollection = $languageCollection;
			$this->domainDetector = $domainDetector;
			$this->request = $request;
			$this->pageCollection = $pageCollection;
		}

		/** @inheritdoc */
		public function detect() {
			$requestLanguage = $this->getRequestLanguage();
			$domainLanguage = $this->getDomainLanguage();
			$defaultLanguage = $this->getDefaultLanguage();

			switch (true) {
				case ($requestLanguage instanceof \iLang) : {
					return $requestLanguage;
				}
				case ($domainLanguage instanceof \iLang) : {
					return $domainLanguage;
				}
				case ($defaultLanguage instanceof \iLang) : {
					return $defaultLanguage;
				}
				default : {
					throw new \coreException('Cannot detect current language');
				}
			}
		}

		/** @inheritdoc */
		public function detectId() {
			return $this->detect()
				->getId();
		}

		/** @inheritdoc */
		public function detectPrefix() {
			return $this->detect()
				->getPrefix();
		}

		/**
		 * Возвращает запрошенный язык
		 * @return \iLang|bool
		 */
		private function getRequestLanguage() {
			$request = $this->getRequest();
			$getContainer = $request->Get();
			$postContainer = $request->Post();
			$langId = false;

			if ($getContainer->isExist('lang_id') || $postContainer->isExist('lang_id')) {
				$langId = $getContainer->get('lang_id') ?: $postContainer->get('lang_id');
				$langId = is_array($langId) ? getFirstValue($langId) : $langId;
			}

			$languageCollection = $this->getLanguageCollection();

			if (!$langId && ($getContainer->isExist('lang') || $postContainer->isExist('lang'))) {
				$prefix = $getContainer->get('lang') ?: $postContainer->get('lang');
				$langId = $languageCollection->getLangId($prefix);
			}

			if (!$langId && ($getContainer->isExist('rel') || $postContainer->isExist('rel'))) {
				$pageId = $getContainer->get('rel') ?: $postContainer->get('rel');
				$pageId = is_array($pageId) ? getFirstValue($pageId) : $pageId;
				$page = $this->getPageCollection()->getElement($pageId);
				$langId = ($page instanceof \iUmiHierarchyElement) ? $page->getLangId() : false;
			}

			if (!$langId) {
				$prefix = getFirstValue($request->getPathParts());
				$langId = $languageCollection->getLangId($prefix);
			}

			return $languageCollection->getLang($langId);
		}

		/**
		 * Возвращает язык запрошенного домена
		 * @return \iLang|bool
		 */
		private function getDomainLanguage() {
			$id = $this->getDomainDetector()
				->detect()
				->getDefaultLangId();
			return $this->getLanguageCollection()
				->getLang($id);
		}

		/**
		 * Возвращает язык по умолчанию
		 * @return \iLang|bool
		 */
		private function getDefaultLanguage() {
			return $this->getLanguageCollection()
				->getDefaultLang();
		}

		/**
		 * Возвращает коллекцию языков
		 * @return \iLangsCollection
		 */
		private function getLanguageCollection() {
			return $this->languageCollection;
		}

		/**
		 * Возвращает определитель текущего домена
		 * @return DomainDetector
		 */
		private function getDomainDetector() {
			return $this->domainDetector;
		}

		/**
		 * Возвращает запрос
		 * @return iFacade
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает коллекцию страниц
		 * @return \iUmiHierarchy
		 */
		private function getPageCollection() {
			return $this->pageCollection;
		}
	}
