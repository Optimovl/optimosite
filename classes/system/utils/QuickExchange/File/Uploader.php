<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\File;

	use UmiCms\System\Request\iFacade as iRequest;

	/**
	 * Класс инициатора загрузки файла
	 * @package UmiCms\Classes\System\Utils\QuickExchange\File
	 */
	class Uploader implements iUploader {

		/** @var iRequest $request фасад запроса */
		private $request;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @const string FILE_INDEX индекс загружаемого файла */
		const FILE_INDEX = 'csv-file';

		/** @inheritdoc */
		public function __construct(iRequest $request, \iConfiguration $configuration) {
			$this->request = $request;
			$this->configuration = $configuration;
		}

		/** @inheritdoc */
		public function upload() {
			$files = $this->getRequest()
				->Files();

			if (!$files->isExist(self::FILE_INDEX)) {
				throw new \publicAdminException('File is not posted');
			}

			$fileInfo = (array) $files->get(self::FILE_INDEX);
			$error = getArrayKey($fileInfo, 'error');

			if ($error) {
				throw new \publicAdminException('Failed to upload file');
			}

			$name = getArrayKey($fileInfo, 'name');
			$tempPath = getArrayKey($fileInfo, 'tmp_name');
			$size = getArrayKey($fileInfo, 'size');
			$directory = $this->getConfiguration()
				->includeParam('system.runtime-cache');
			/** @todo: произвести рефакторинг umiFile,  передать новый класс в зависимость этому */
			$file = \umiFile::manualUpload($name, $tempPath, $size, $directory);

			if (!$file instanceof \iUmiFile || $file->getIsBroken()) {
				throw new \publicAdminException('Upload file is broken');
			}

			return $file;
		}

		/**
		 * Возвращает фасад запроса
		 * @return iRequest
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}
	}
