<?php

	/** Команда проверки прав на запись файла */
	class CheckPermissionsAction extends Action {

		/** @inheritdoc */
		public function execute() {
			$targetPath = $this->getParam('target');

			if (!file_exists($targetPath)) {
				throw new Exception("Doesn't exsist target \"{$targetPath}\"");
			}

			$mode = $this->getParam('mode');

			switch ($mode) {
				case 'write' : {
					if (!is_writable($targetPath)) {
						throw new Exception("Target must be writable \"{$targetPath}\"");
					}
					break;
				}
				case 'read' : {
					if (!is_readable($targetPath)) {
						throw new Exception("Target must be readable \"{$targetPath}\"");
					}
					break;
				}
				default : {
					throw new Exception("Unknown mode \"{$mode}\", use \"write\" or \"read\"");
				}
			}
		}

		/** @inheritdoc */
		public function rollback() {
		}
	}
