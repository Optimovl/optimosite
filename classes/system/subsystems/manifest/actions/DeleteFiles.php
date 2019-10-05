<?php

	/** Команда удаления файлов */
	class DeleteFilesAction extends Action {

		/** @inheritdoc */
		public function execute() {
			$path = $this->getParam('target-directory');
			$pattern = $this->getParam('pattern');

			$directory = new umiDirectory($path);
			$directory->deleteFilesByPattern($pattern);
		}

		/** @inheritdoc */
		public function rollback() {
		}
	}
