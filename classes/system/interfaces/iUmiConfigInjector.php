<?php

	/** Интерфейс класса, работающего с настройками (config.ini) */
	interface iUmiConfigInjector {

		/**
		 * Устанавливает настройки
		 * @param iConfiguration $configuration настройки
		 */
		public function setConfiguration(iConfiguration $configuration);

		/**
		 * Возвращает настройки
		 * @return iConfiguration
		 */
		public function getConfiguration();
	}

