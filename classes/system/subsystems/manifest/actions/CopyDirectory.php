<?php

	/** Команда копирования директории */
	class CopyDirectoryAction extends Action {

		/** @var array $movedItems пути до скопированных файлов и директорий */
		protected $movedItems = [];

		/** @var string $targetDirectory путь куда нужно скопировать директорию */
		protected $targetDirectory;

		/** @var string $sourceDirectory путь до копируемой директории */
		protected $sourceDirectory;

		/** @inheritdoc */
		public function execute() {
			$sourceDirectory = $this->getParam('source-directory');
			$targetDirectory = $this->getParam('target-directory');

			$this->targetDirectory = $targetDirectory;
			$this->sourceDirectory = $sourceDirectory;

			if ($sourceDirectory) {
				$this->copyDirectory(new umiDirectory($sourceDirectory));
			} else {
				throw new Exception('No items to copy');
			}
		}

		/** @inheritdoc */
		public function rollback() {
			$movedItems = $this->movedItems;
			$movedItems = array_reverse($movedItems);

			foreach ($movedItems as $item) {
				if (is_file($item . '.bak')) {
					copy($item . '.bak', $item);
					unlink($item . '.bak');
				} elseif (is_file($item)) {
					unlink($item);
				} elseif (is_dir($item)) {
					rmdir($item);
				}
			}
		}

		/**
		 * Рекурсивно куопирует содержимое директории
		 * @param umiDirectory $dir директория
		 */
		protected function copyDirectory(umiDirectory $dir) {
			$targetPath = $this->targetDirectory . mb_substr($dir->getPath(), mb_strlen($this->sourceDirectory));

			if ($targetPath && !is_dir($targetPath)) {
				mkdir($targetPath, 0777, true);
				$this->movedItems[] = $targetPath;
			}

			foreach ($dir as $item) {
				if ($item instanceof umiDirectory) {
					$this->copyDirectory($item);
				}

				if ($item instanceof umiFile) {
					$this->copyFile($item);
				}
			}
		}

		/**
		 * Копирует файл
		 * @param umiFile $file файл
		 */
		protected function copyFile(umiFile $file) {
			$sourcePath = $file->getFilePath();
			$targetPath = $this->targetDirectory . mb_substr($sourcePath, mb_strlen($this->sourceDirectory));

			if (is_file($targetPath)) {
				copy($targetPath, $targetPath . '.bak');
			}

			copy($sourcePath, $targetPath);
			$this->movedItems[] = $targetPath;
		}
	}
