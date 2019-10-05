<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange;

	use UmiCms\Classes\System\Utils\QuickExchange\Csv\iExporter;
	use UmiCms\Classes\System\Utils\QuickExchange\Csv\iImporter;
	use UmiCms\Classes\System\Utils\QuickExchange\File\iDownloader;
	use UmiCms\Classes\System\Utils\QuickExchange\File\iUploader;
	use UmiCms\System\Response\iFacade as Response;

	/**
	 * Класс фасада быстрого обмена данными в формате csv.
	 * @package UmiCms\Classes\System\Utils\QuickExchange
	 */
	class Facade implements iFacade {

		/** @var iExporter $exporter csv экспортер */
		private $exporter;

		/** @var iImporter $importer csv импортер */
		private $importer;

		/** @var iDownloader инициатор скачивания файла */
		private $downloader;

		/** @var iUploader инициатор загрузки файла */
		private $uploader;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var Response $response фасад вывода */
		private $response;

		/** @var string $encoding кодировка csv файла */
		private $encoding;

		/** @inheritdoc */
		public function __construct(
			iExporter $exporter,
			iImporter $importer,
			iDownloader $downloader,
			iUploader $uploader,
			\iConfiguration $configuration,
			Response $response
		) {
			$this->exporter = $exporter;
			$this->importer = $importer;
			$this->downloader = $downloader;
			$this->uploader = $uploader;
			$this->configuration = $configuration;
			$this->response = $response;
		}

		/** @inheritdoc */
		public function setEncoding($encoding = 'windows-1251') {
			$this->encoding = $encoding;
			return $this;
		}

		/** @inheritdoc */
		public function upload() {
			$this->handleAction(function() {
				$file = $this->getUploader()
					->upload();
				return [
					'file' => $file->getFilePath(true)
				];
			});
		}

		/** @inheritdoc */
		public function import(\selector $query) {
			$this->handleAction(function() use ($query) {
				$encoding = $this->getEncoding() ?: $this->getDefaultEncoding();
				$isComplete = $this->getImporter()
					->import($query, $encoding);
				return [
					'is_complete' => $isComplete
				];
			});
		}

		/** @inheritdoc */
		public function export(\selector $query) {
			$this->handleAction(function() use ($query) {
				$encoding = $this->getEncoding() ?: $this->getDefaultEncoding();
				$isComplete = $this->getExporter()
					->export($query, $encoding);
				return [
					'is_complete' => $isComplete
				];
			});
		}

		/** @inheritdoc */
		public function download() {
			$this->handleAction(function() {
				$this->getDownloader()
					->download();
			});
		}

		/**
		 * Выволяет операцию и выводит результат в буффер в виде json
		 * @param callable $action операция
		 */
		private function handleAction(callable $action) {
			try {
				$result = call_user_func($action);
			} catch (\Exception $exception) {
				$result = [
					'error' => $exception->getMessage()
				];
			}

			$this->getResponse()
				->printJson($result);
		}

		/**
		 * Возвращает кодировку по умолчанию
		 * @return string
		 */
		private function getDefaultEncoding() {
			return (string) $this->getConfiguration()
				->get('system', 'default-exchange-encoding');
		}

		/**
		 * Возвращает csv экспортер
		 * @return iExporter
		 */
		private function getExporter() {
			return $this->exporter;
		}

		/**
		 * Возвращает csv импортер
		 * @return iImporter
		 */
		private function getImporter() {
			return $this->importer;
		}

		/**
		 * Возвращает инициатора скачивания файла
		 * @return iDownloader
		 */
		private function getDownloader() {
			return $this->downloader;
		}

		/**
		 * Возвращает инициатора загрузки файла
		 * @return iUploader
		 */
		private function getUploader() {
			return $this->uploader;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает фасад вывода
		 * @return Response
		 */
		private function getResponse() {
			return $this->response;
		}

		/**
		 * Возвращает кодировку csv файла
		 * @return string
		 */
		private function getEncoding() {
			return $this->encoding;
		}
	}
