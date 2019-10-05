<?php

	namespace UmiCms\Classes\System\Entities\File;

	/**
	 * Фабрика файлов
	 * @package UmiCms\Classes\System\Entities\File
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create($path) {
			$file = $this->createByRawPath($path);
			return $this->fixFilePath($file, $path);
		}

		/** @inheritdoc */
		public function createByRawPath($path) {
			$file = $this->createSecure($path);
			$file->setIgnoreSecurity();
			$file->setFilePath($path);
			return $file->refresh();
		}

		/** @inheritdoc */
		public function createSecure($path) {
			$file = $this->instantiate($path);
			return $this->fixFilePath($file, $path);
		}

		/**
		 * Исправляет путь до файла, если это необходимо
		 * @param \iUmiFile $file файл
		 * @param string $path исходный путь
		 * @return \iUmiFile
		 */
		private function fixFilePath(\iUmiFile $file, $path) {
			if ($file->isExists() || startsWith($path, '.') || startsWith($path, CURRENT_WORKING_DIR)) {
				return $file;
			}

			@$file->setFilePath(sprintf('.%s', $path));
			return $file->refresh();
		}

		/**
		 * Инстанцирует экземпляр файла
		 * @param string $path путь до файла
		 * @return \umiFile
		 */
		protected function instantiate($path) {
			return new \umiFile($path);
		}
	}
