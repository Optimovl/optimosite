<?php

	/** Команда архивирования директории */
	class CompressDirectoryAction extends Action {

		/** @var string $outputFileName путь до архива */
		protected $outputFileName;

		/** @inheritdoc */
		public function execute() {
			$targetDirectory = $this->getParam('target-directory');
			$outputFileName = $this->getParam('output-file-name');

			$this->outputFileName = $outputFileName;

			if (mb_substr($targetDirectory, 1, 1) == ':') {
				$removePath = mb_substr($targetDirectory, 2);
			} else {
				$removePath = $targetDirectory;
			}

			$zip = new UmiZipArchive($outputFileName);
			$result = $zip->create($targetDirectory, $removePath);

			if ($result == 0) {
				throw new Exception('Failed to create zip file: "' . $zip->errorInfo() . '"');
			}

			chmod($outputFileName, 0777);
		}

		/** @inheritdoc */
		public function rollback() {
			if (is_file($this->outputFileName)) {
				unlink($this->outputFileName);
			}
		}
	}
