<?php

	/** Функционал класса части модуля */
	trait tModulePart {

		/** @var def_module $module модуль, который включает в себя часть */
		private $module;

		/**
		 * Устанавливает экземпляр модуля
		 * @param def_module $module модуль
		 * @return iModulePart|tModulePart
		 */
		public function setModule(def_module $module) {
			$this->module = $module;
			return $this;
		}

		/**
		 * Возвращает экземпляр модуля
		 * @return def_module
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getModule() {
			if (!$this->module instanceof def_module) {
				throw new RequiredPropertyHasNoValueException('You should set module first');
			}

			return $this->module;
		}

		/**
		 * Возвращает название модуля
		 * @return string
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getModuleName() {
			return get_class($this->getModule());
		}
	}
