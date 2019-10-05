<?php

	/** Интерфейс для работы с поисковой базой по сайту */
	interface iSearchModel {

		/** @var string DEFAULT_ENTRY_PATTERN шаблон вхождения по умолчанию */
		const DEFAULT_ENTRY_PATTERN = '<b>%s</b>';

		/** @var string DEFAULT_LINE_PATTERN шаблон строки с вхождением по умолчанию */
		const DEFAULT_LINE_PATTERN = '<p>%s</p>';

		public static function splitString($str);

		/**
		 * @deprecated
		 * Возвращает id слова $word в поисковой базе
		 * @param string $word слово
		 * @return int|bool
		 */
		public static function getWordId($word);
		
		/**
		 * Возвращает id слова $word в поисковой базе
		 * @param string $word слово
		 * @return int|bool
		 */
		public function getSearchWordId($word);

		/**
		 * Осуществляет поиск по индексу
		 * @param string $searchString поисковая строка
		 * @param array $searchTypes если указан, то будут выбраны только страницы с необходимым hierarchy-type-id
		 * @param array $hierarchy_rels если указан, то искать только в определенном разделе сайта
		 * @param bool $orMode если true, то искать в режиме OR, иначе в режиме AND
		 * @return int[] Список идентификаторов найденных страниц
		 */
		public function runSearch($searchString, $searchTypes = null, $hierarchy_rels = null, $orMode = false);

		public function buildQueries($words, $search_types = null, $hierarchy_rels = null, $orMode = false);

		public function prepareContext($element_id, $uniqueOnly = false);

		/**
		 * Возвращает контекст, в котором употреблены поисковые слова на странице
		 * @param int $elementId id страницы
		 * @param string $searchString поисковая строка
		 * @param string $entryPattern шаблон вхождения
		 * @param string $linePattern шаблон строки с вхождением
		 * @return string
		 */
		public function getContext($elementId, $searchString, $entryPattern = self::DEFAULT_ENTRY_PATTERN, $linePattern = self::DEFAULT_LINE_PATTERN);

		/**
		 * Возвращает количество проиндексированных страниц
		 * @return int
		 */
		public function getIndexPages();

		/**
		 * Возвращает количество страниц, годных для индексации
		 * @return int
		 */
		public function getAllIndexablePages();

		/**
		 * Возвращает количество проиндексированных слов
		 * @return int
		 */
		public function getIndexWords();

		/**
		 * Возвращает количество проиндексированных уникальных слов
		 * @return int
		 */
		public function getIndexWordsUniq();

		/**
		 * Возвращает дату последней индексации
		 * @return int
		 */
		public function getIndexLast();

		/** Очищает поисковый индекс */
		public function truncate_index();

		/**
		 * @deprecated
		 * Индексирует все страницы, где дата последней модификации меньше даты последней индексации
		 * @param int|bool $limit ограничение на количество индексируемых страниц за одну итерацию
		 * @param int $lastId идентификатор последней проиндексированной страницы
		 * @return array
		 *
		 * [
		 *      "current" => номер итерации индексации,
		 *      "lastId" => идентификатор последней проиндексированной страницы
		 * ]
		 */
		public function index_all($limit = false, $lastId = 0);

		/**
		 * Индексирует страницу
		 * @param int $elementId id страницы
		 * @param bool $isManual индексация запускается вручную
		 * @return mixed
		 * @throws Exception
		 */
		public function index_item($elementId, $isManual = false);

		/**
		 * Индексирует страницу и всех ее детей
		 * @param int $elementId id страницы
		 */
		public function index_items($elementId);

		/**
		 * Считает IDF слова
		 * @param int $wordId id слова в поисковой базе
		 * @return float
		 */
		public function calculateIDF($wordId);

		/**
		 * Стирает индекс страницы
		 * @param int $elementId id страницы
		 * @return bool
		 */
		public function unindex_items($elementId);

		public function suggestions($string, $limit = 10);

		/**
		 * Обрабатывает страницу: добавляет или удаляет ее из поискового индекса
		 * @param iUmiHierarchyElement $element
		 * @return $this
		 * @throws Exception
		 */
		public function processPage(iUmiHierarchyElement $element);

		/**
		 * Обрабатывает список страниц: добавляет или удаляет страницы из поискового индекса
		 * @param iUmiHierarchyElement[] $elementList
		 * @return $this
		 * @throws Exception
		 */
		public function processPageList(array $elementList);

		public function parseItem($element_id);

		public function buildIndexImage($indexFields);

		public function updateSearchIndex($element_id, $index_image);

		/**
		 * Возвращает список всех проиндексированных слов
		 * @return array
		 */
		public function getIndexWordList();

		/**
		 * Возвращает поисковый индекс
		 * @return array
		 */
		public function getIndexList();
	}
