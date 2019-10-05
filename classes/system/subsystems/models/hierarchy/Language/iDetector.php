<?php

	namespace UmiCms\System\Hierarchy\Language;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Request\iFacade;

	/**
	 * Интерфейс определителя запрошенного домена
	 * @package UmiCms\System\Hierarchy\Language
	 */
	interface iDetector {

		/**
		 * Конструктор
		 * @param \iLangsCollection $languageCollection коллекция языков
		 * @param DomainDetector $domainDetector определитель запрошенного языка
		 * @param iFacade $request запрос
		 * @param \iUmiHierarchy $pageCollection коллекция страниц
		 */
		public function __construct(
			\iLangsCollection $languageCollection,
			DomainDetector $domainDetector,
			iFacade $request,
			\iUmiHierarchy $pageCollection
		);

		/**
		 * Определяет запрошенный язык
		 * @return \iLang
		 * @throws \coreException
		 */
		public function detect();

		/**
		 * Определяет идентификатор запрошенного языка
		 * @return int
		 * @throws \coreException
		 */
		public function detectId();

		/**
		 * Определяет префикс запрошенного языка
		 * @return string
		 * @throws \coreException
		 */
		public function detectPrefix();
	}
