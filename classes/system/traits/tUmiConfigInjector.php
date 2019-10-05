<?php

	/** Трейт работника с настройками системы (из config.ini) */
	trait tUmiConfigInjector {

		/** @var iConfiguration $configuration настройки системы (config.ini) */
		private $configuration;

		/**
		 * Устанавливает настройки
		 * @param iConfiguration $configuration настройки
		 */
		public function setConfiguration(iConfiguration $configuration) {
			$this->configuration = $configuration;
		}

		/**
		 * Возвращает настройки
		 * @return iConfiguration
		 * @throws Exception
		 */
		public function getConfiguration() {
			if (!$this->configuration instanceof iConfiguration) {
				throw new RequiredPropertyHasNoValueException('You should set iConfiguration first');
			}

			return $this->configuration;
		}
	}
