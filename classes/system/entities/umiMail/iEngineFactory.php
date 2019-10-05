<?php

	namespace UmiCms\Mail;

	/**
	 * Интерфейс фабрики средств отправки писем
	 * @package UmiCms\Mail
	 */
	interface iEngineFactory {

		/**
		 * Создает средство отправки писем по его коду.
		 * Если средство отправки было создано ранее - возвращает его.
		 * @param string $codeName код
		 * @return iEngine
		 * @throws \Exception
		 */
		public static function get($codeName);

		/**
		 * Создает новое средство отправки писем по его коду
		 * @param string $codeName код
		 * @return iEngine
		 * @throws \Exception
		 */
		public static function create($codeName);

		/**
		 * Создает средство отправки писем по умолчанию
		 * @return iEngine
		 */
		public static function createDefault();

		/**
		 * Создает заглушку средства отправки писем
		 * @return iEngine
		 */
		public static function createDummy();
	}
