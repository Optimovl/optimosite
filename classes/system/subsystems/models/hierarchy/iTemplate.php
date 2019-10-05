<?php

	/** Шаблона дизайна */
	interface iTemplate extends iUmiEntinty {

		/**
		 * Возвращает название шаблона дизайна
		 * @return string
		 */
		public function getName();

		/**
		 * Возвращает название файла шаблона дизайна
		 * @return string
		 */
		public function getFilename();

		/**
		 * Возвращает корневую директорию с ресурсами для шаблонов
		 * @param bool $httpMode
		 * @return string
		 */
		public function getResourcesDirectory($httpMode = false);

		/**
		 * Возвращает корневую директорию с шаблонами
		 * @return string
		 */
		public function getTemplatesDirectory();

		/**
		 * Возвращает полный путь к шаблону дизайна
		 * @return string
		 */
		public function getFilePath();

		/**
		 * Возвращает тип шаблона дизайна
		 * @return string
		 */
		public function getType();

		/**
		 * Возвращает название шаблона дизайна
		 * @return string
		 */
		public function getTitle();

		/**
		 * Возвращает id домена, к которому привязан шаблон
		 * @return int
		 */
		public function getDomainId();

		/**
		 * Возвращает id языка, к которому привязан шаблон
		 * @return int
		 */
		public function getLangId();

		/**
		 * Определяет, является ли данный шаблон шаблоном по умолчанию
		 * @return bool
		 */
		public function getIsDefault();

		/**
		 * Изменяет название шаблона
		 * @param string $name название
		 */
		public function setName($name);

		/**
		 * Изменяет название файла шаблона
		 * @param string $filename название файла шаблона
		 */
		public function setFilename($filename);

		/**
		 * Изменяет название шаблона дизайна
		 * @param string $title название шаблона
		 */
		public function setTitle($title);

		/**
		 * Изменяет тип шаблона
		 * @param string $type тип шаблона
		 */
		public function setType($type);

		/**
		 * Изменяет домен, к которому привязан шаблон дизайна
		 * @param int $domainId id домена
		 * @return bool true в случае успеха
		 */
		public function setDomainId($domainId);

		/**
		 * Изменяет язык, к которому привязан шаблон
		 * @param int $langId id языка
		 * @return bool true в случае успеха
		 */
		public function setLangId($langId);

		/**
		 * Изменяет флаг "по умолчанию"
		 * @param bool $isDefault значение флага "по умолчанию"
		 */
		public function setIsDefault($isDefault);

		/**
		 * Возвращает расширение файла шаблона
		 * @return string
		 */
		public function getFileExtension();

		/**
		 * Возвращает путь до конфигурации шаблона, если его возможно вычислить
		 * @return null|string
		 */
		public function getConfigPath();

		/**
		 * Возвращает список страниц, которые используют этот шаблон
		 * @param int $limit максимальное количество получаемых страниц
		 * @param int $offset смещение, относительно которого будет производиться выборка страниц
		 * @return array [
		 *     [
		 *         pageId,
		 *         pageName
		 *     ]
		 * ]
		 */
		public function getUsedPages($limit = 0, $offset = 0);

		/** Возвращает общее число страниц, которые используют данный шаблон */
		public function getTotalUsedPages();

		/**
		 * Возвращает список элементов, у которых установлен данный шаблон
		 * @param int $limit максимальное количество получаемых элементов
		 * @param int $offset смещение, относительно которого будет производиться выборка элементов
		 * @return iUmiHierarchyElement[]
		 */
		public function getRelatedPages($limit = 0, $offset = 0);

		/**
		 * Привязывает страницы сайта к шаблону
		 * @param array $pages [
		 *     [
		 *         pageId,
		 *         pageName
		 *     ]
		 * ]
		 * @return bool true в случае, если не возникло ошибок
		 */
		public function setUsedPages($pages);
	}
