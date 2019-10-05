<?php

	/** Фабрика менеджеров конфигураций в формате xml файлов */
	class BaseXmlConfigFactory implements iBaseXmlConfigFactory {

		/** @inheritdoc */
		public function create($configPath) {
			return new baseXmlConfig($configPath);
		}
	}
