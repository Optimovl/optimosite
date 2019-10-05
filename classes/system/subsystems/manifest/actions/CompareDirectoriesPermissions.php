<?php

	/** Команда рекурсивной проверки прав на запись в заданной директории */
	class CompareDirectoriesPermissionsAction extends Action {

		/** @var string $sourceDirectory */
		protected $sourceDirectory;

		/** @var string $targetDirectory */
		protected $targetDirectory;

		/** @inheritdoc */
		public function execute() {
			$this->sourceDirectory = $this->getParam('source-directory');
			$this->targetDirectory = $this->getParam('target-directory');

			$this->checkFolderPermissions($this->sourceDirectory);
		}

		/** @inheritdoc */
		public function rollback() {
		}

		/**
		 * Рекурсивно проверяет права на запись для директории
		 * @param string $path путь до директории
		 * @throws Exception
		 */
		protected function checkFolderPermissions($path) {
			$checkPath = $this->targetDirectory . mb_substr($path, mb_strlen($this->sourceDirectory));

			if (is_dir($checkPath) && !is_writable($checkPath)) {
				throw new Exception("This directory must be writable \"{$path}\"");
			}

			$dir = new umiDirectory($path);

			foreach ($dir as $item) {
				if ($item instanceof umiDirectory) {
					$this->checkFolderPermissions($item->getPath());
				}

				if ($item instanceof umiFile) {
					$this->checkFilePermissions($item->getFilePath());
				}
			}
		}

		/**
		 * Проверяет права на запись для файла
		 * @param string $path путь до файла
		 * @throws Exception
		 */
		protected function checkFilePermissions($path) {
			$checkPath = $this->targetDirectory . mb_substr($path, mb_strlen($this->sourceDirectory));

			if (file_exists($checkPath) && !is_writable($checkPath)) {
				throw new Exception("This file should be writable \"{$checkPath}\"");
			}
		}
	}
