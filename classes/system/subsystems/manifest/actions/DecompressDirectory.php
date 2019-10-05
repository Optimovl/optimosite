<?php

	/** Команда распаковывания архива */
	class DecompressDirectoryAction extends Action {

		/** @inheritdoc */
		public function execute() {
			$archiveFileName = $this->getParam('archive-filepath');
			$targetDirectory = $this->getParam('target-directory');

			$zip = new UmiZipArchive($archiveFileName);
			$result = $zip->extract($targetDirectory);

			if ($result == 0) {
				throw new Exception('Failed to create zip file: "' . $zip->errorInfo() . '"');
			}
		}

		/** @inheritdoc */
		public function rollback() {
		}
	}
