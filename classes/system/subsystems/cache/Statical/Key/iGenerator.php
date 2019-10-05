<?php

	namespace UmiCms\System\Cache\Statical\Key;

	use UmiCms\System\Request\iFacade as iRequest;

	/**
	 * Интерфейс генератора ключей для статического кеша
	 * @package UmiCms\System\Cache\Statical\Key
	 */
	interface iGenerator {

		/**
		 * Конструктор
		 * @param iRequest $request запрос
		 * @param \iUmiHierarchy $pageCollection коллекция страниц
		 * @param \iConfiguration $config конфигурация
		 * @param \iDomainsCollection $domainsCollection коллекция доменов
		 */
		public function __construct(
			iRequest $request,
			\iUmiHierarchy $pageCollection,
			\iConfiguration $config,
			\iDomainsCollection $domainsCollection
		);

		/**
		 * Возвращает ключ, соответствующий текущему запросу
		 * @return string
		 */
		public function getKey();

		/**
		 * Возвращает список ключей, соответствующих запросу заданной страницы
		 * @param int $id идентификатор страницы
		 * @return string[]
		 */
		public function getKeyList($id);
	}
