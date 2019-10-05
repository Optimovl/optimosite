<?php

	namespace UmiCms\System\Cache\Key;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;

	/**
	 * Интерфейс генератора ключей для кеширования
	 * @package UmiCms\System\Cache\Key
	 */
	interface iGenerator {

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration конфигурация
		 * @param DomainDetector $domainDetector определитель домена
		 * @param LanguageDetector $languageDetector определитель языка
		 */
		public function __construct(
			\iConfiguration $configuration,
			DomainDetector $domainDetector,
			LanguageDetector $languageDetector
		);

		/**
		 * Формирует ключ для кеширования произвольных данных
		 * @param string $key исходный ключ
		 * @param string|null $storeType тип кешируемых данных
		 * @return string
		 */
		public function createKey($key, $storeType = null);

		/**
		 * Формирует ключ для кеширований результата sql запроса
		 * @param string $query sql запрос
		 * @return string
		 */
		public function createKeyForQuery($query);

		/**
		 * Возвращает базовый префикс
		 * @return string
		 */
		public function getBasePrefix();
	}
