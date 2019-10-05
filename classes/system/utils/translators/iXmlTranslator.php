<?php

	/** Интерфейс xml транслятора (сериализатора) */
	interface iXmlTranslator {

		/**
		 * Конструктор
		 * @param DOMDocument $dom документ, куда требуется добавить сериализованные данные
		 */
		public function __construct(DOMDocument $dom);

		/**
		 * Сериализует данные в xml
		 * @param DOMElement $rootNode узел, куда требуется добавить сериализованные данные
		 * @param mixed $userData данные, которые требуется сериализовать
		 */
		public function translateToXml(DOMElement $rootNode, $userData);

		/**
		 * Сериализует данные в xml
		 * @param DOMElement $rootNode узел, куда требуется добавить сериализованные данные
		 * @param mixed $userData данные, которые требуется сериализовать
		 * @param array $options опции сериализации
		 * @throws coreException
		 */
		public function chooseTranslator(DOMElement $rootNode, $userData, $options = []);

		/**
		 * Определяет разрешена ли обработка макросов
		 * @return bool
		 */
		public static function isParseTPLMacrosesAllowed();

		/**
		 * Возвращает список разрешенных для обработки макросов
		 * @return null|string[]
		 */
		public static function getAllowedTplMacroses();

		/**
		 * Выполяет tpl макросы в строковых данных
		 * @param string $userData строковые данные
		 * @param bool $scopeElementId идентификатор страницы контекста
		 * @param bool $scopeObjectId идентификатор объекта контекста
		 * @return string
		 * @throws coreException
		 */
		public static function executeMacroses($userData, $scopeElementId = false, $scopeObjectId = false);

		/**
		 * Возвращает левую часть ключа
		 * @param string $key ключ данных
		 * @return string
		 */
		public static function getSubKey($key);

		/**
		 * Возвращает правую часть ключа
		 * @param string $key ключ данных
		 * @return string
		 */
		public static function getRealKey($key);

		/**
		 * Возвращает массив правой и левой частей ключа
		 * @param string $key ключ данных
		 * @return array
		 */
		public static function getKey($key);

		/** Очищает кэш у всех экземпляров класса */
		public static function clearCache();
	}
