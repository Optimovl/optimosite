<?php

	/** Интерфейс работника с реестром системы */
	interface iUmiRegistryInjector {

		/**
		 * Устанавливает экземпляр класса для работы с реестром
		 * @param \iRegedit $registry экземпляр класса для работы с реестром
		 * @return \iUmiRegistryInjector
		 */
		public function setRegistry(\iRegedit $registry);

		/**
		 * Возвращает экземпляр класса для работы с реестром
		 * @return \iRegedit
		 * @throws \Exception
		 */
		public function getRegistry();
	}
