<?php

	/** Команда создания директорий */
	class CreateDirectoryAction extends Action {

		/** @inheritdoc */
		public function execute() {
			$targetPathList = (array) $this->getParam('targets');

			foreach ($targetPathList as $filePath) {
				$this->createDirectory($filePath);
			}
		}

		/** @inheritdoc */
		public function rollback() {
			$targetPathList = (array) $this->getParam('targets');

			foreach ($targetPathList as $filePath) {
				$this->removeDirectory($filePath);
			}
		}
	}
