<?php

	/** Интерфейс источника манифеста */
	interface iManifestSource {

		/**
		 * Возвращает путь до конфигурации манифест
		 * @param string $name название манифеста
		 * @return string
		 */
		public function getConfigFilePath($name);

		/**
		 * Возвращает путь до команды манифест
		 * @param string $name название команды
		 * @return string
		 */
		public function getActionFilePath($name);
	}
