<?php

	/** Команда рекурсивного удаления директории */
	class RemoveDirectoryAction extends Action {

		/** @inheritdoc */
		public function execute() {
			$targetDirectory = $this->getParam('target-directory');
			$this->removeDirectory($targetDirectory);
		}

		/** @inheritdoc */
		public function rollback() {
		}
	}
