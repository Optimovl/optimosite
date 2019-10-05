<?php

	/**
	 * Интерфейс манифеста.
	 * Манифест представляет собой список транзакций (iTransaction).
	 * Содержимое манифеста определяет xml файл конфигурации (iBaseXmlConfig).
	 */
	interface iManifest extends iReadinessWorker, iAtomicOperation {

		/**
		 * Конструктор
		 * @param iBaseXmlConfig $config конфигурация манифеста
		 * @param iManifestSource $source источник манифеста
		 * @param array $params настройки выполнения:
		 *
		 * [
		 *      # => [
		 *          'name' => 'value'
		 *      ]
		 * ]
		 */
		public function __construct(iBaseXmlConfig $config, iManifestSource $source, array $params = []);

		/** Загружает список транзакций из конфигурации */
		public function loadTransactions();

		/**
		 * Возвращает журнал выполнения
		 * @return array
		 */
		public function getLog();
	}
