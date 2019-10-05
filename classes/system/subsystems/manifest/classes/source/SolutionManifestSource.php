<?php

	/** Источник манифеста - решение (сайт|шаблон) */
	class SolutionManifestSource implements iManifestSource {

		/** @var string $solution название решения */
		private $solution;

		/**
		 * Конструктор
		 * @param string $solution название решения
		 * @throws Exception
		 */
		public function __construct($solution) {
			if (!is_string($solution) || empty($solution)) {
				throw new Exception('Wrong solution name given');
			}

			$this->solution = $solution;
		}

		/** @inheritdoc */
		public function getConfigFilePath($name) {
			return CURRENT_WORKING_DIR . "/templates/{$this->solution}/manifest/{$name}.xml";
		}

		/** @inheritdoc */
		public function getActionFilePath($name) {
			$name = trimNameSpace($name);
			return CURRENT_WORKING_DIR . "/templates/{$this->solution}/manifest/actions/{$name}.php";
		}
	}
