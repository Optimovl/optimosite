<?php

	/** Источник манифеста - модуль */
	class ModuleManifestSource implements iManifestSource {

		/** @var string $module название модуля */
		private $module;

		/**
		 * Конструктор
		 * @param string $module название модуля
		 * @throws Exception
		 */
		public function __construct($module) {
			if (!is_string($module) || empty($module)) {
				throw new Exception('Wrong module name given');
			}

			$this->module = $module;
		}

		/** @inheritdoc */
		public function getConfigFilePath($name) {
			return SYS_MODULES_PATH . "{$this->module}/manifest/{$name}.xml";
		}

		/** @inheritdoc */
		public function getActionFilePath($name) {
			$name = trimNameSpace($name);
			return SYS_MODULES_PATH . "{$this->module}/manifest/actions/{$name}.php";
		}
	}
