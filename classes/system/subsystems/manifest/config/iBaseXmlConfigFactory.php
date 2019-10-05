<?php

	/** Интерфейс фабрики менеджеров конфигураций в формате xml файлов */
	interface iBaseXmlConfigFactory {

		/**
		 * Создает менеджера конфигурации
		 * @param string $configPath путь до конфигурации в формате xml файлов
		 * @return iBaseXmlConfig
		 */
		public function create($configPath);
	}
