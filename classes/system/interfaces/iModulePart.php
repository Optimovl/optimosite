<?php

	/** Интерфейс класса части модуля */
	interface iModulePart {

		/**
		 * Устанавливает экземпляр модуля
		 * @param def_module $module модуль
		 * @return iModulePart
		 */
		public function setModule(def_module $module);

		/**
		 * Возвращает экземпляр модуля
		 * @return def_module
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getModule();

		/**
		 * Возвращает название модуля
		 * @return string
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getModuleName();
	}
