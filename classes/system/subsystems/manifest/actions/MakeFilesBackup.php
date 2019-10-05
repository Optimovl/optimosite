<?php

	/** Команда бекапирования файлов */
	class MakeFilesBackupAction extends Action {

		/** @var array $movedItems список путей до бекапированных файлов */
		protected $movedItems = [];

		/** @var string $targetDirectory путь до директории для бекапа */
		protected $targetDirectory;

		/** @inheritdoc */
		public function execute() {
			$this->targetDirectory = $this->getParam('temporary-directory');

			$param = $this->getParam('targets');

			if (is_array($param)) {
				$this->copyItems($param);
			} else {
				throw new Exception('No items to copy');
			}
		}

		/** @inheritdoc */
		public function rollback() {
			$movedItems = $this->movedItems;
			$movedItems = array_reverse($movedItems);

			foreach ($movedItems as $item) {
				if (is_file($item)) {
					unlink($item);
				}

				if (is_dir($item)) {
					rmdir($item);
				}
			}

			if (is_dir($this->targetDirectory)) {
				rmdir($this->targetDirectory);
			}
		}

		/**
		 * Копирует файлы и директории
		 * @param array $items список бекапируемых файлов и директорий
		 */
		protected function copyItems(array $items) {
			clearstatcache();

			foreach ($items as $item) {
				if (is_file($item)) {
					$this->copyFile($item);
				}

				if (is_dir($item)) {
					$this->copyDirectory($item);
				}
			}
		}

		/**
		 * Бекапирует файл
		 * @param string $item путь до файла
		 * @throws Exception
		 */
		protected function copyFile($item) {
			if (!is_writable($item)) {
				throw new Exception("This file should be writable: \"{$item}\"");
			}

			$newItemPath = $this->targetDirectory . $item;
			copy($item, $newItemPath);
			$this->movedItems[] = $newItemPath;
		}

		/**
		 * Бекапирует директорию
		 * @param string $item путь до директории
		 * @throws Exception
		 */
		protected function copyDirectory($item) {
			$newItemPath = $this->targetDirectory . $item;
			$this->movedItems[] = $newItemPath;

			if (!is_dir($newItemPath) && !mkdir($newItemPath)) {
				throw new Exception("Can't create directory \"{$newItemPath}\"");
			}

			if (!is_writable($newItemPath)) {
				throw new Exception("Directory is not writable: \"{$newItemPath}\"");
			}

			$dir = new umiDirectory($item);

			foreach ($dir as $subItem) {
				if ($subItem instanceof umiDirectory) {

					if ($subItem->getName() == '.svn') {
						continue;
					}

					if ($subItem->getName() == 'cngeoip.dat') {
						continue;
					}

					$this->copyDirectory($subItem->getPath());
				}

				if ($subItem instanceof umiFile) {
					$this->copyFile($subItem->getFilePath());
				}
			}
		}
	}
