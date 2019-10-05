<?php

	/** Источник манифеста - ядро */
	class CoreManifestSource implements iManifestSource {

		/** @inheritdoc */
		public function getConfigFilePath($name) {
			return SYS_KERNEL_PATH . "subsystems/manifest/manifests/{$name}.xml";
		}

		/** @inheritdoc */
		public function getActionFilePath($name) {
			$name = trimNameSpace($name);
			return SYS_KERNEL_PATH . "subsystems/manifest/actions/{$name}.php";
		}
	}
