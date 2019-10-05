<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\File;

	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;
	use UmiCms\Classes\System\Utils\QuickExchange\Source\iDetector as SourceDetector;
	use UmiCms\System\Response\iFacade as Response;

	/**
	 * Класс инициатора скачивания файла
	 * @package UmiCms\Classes\System\Utils\QuickExchange\File
	 */
	class Downloader implements iDownloader {

		/** @var SourceDetector $sourceDetector определитель источника */
		private $sourceDetector;

		/** @var FileFactory $fileFactory фабрика файлов */
		private $fileFactory;

		/** @var Response $response фасад вывода */
		private $response;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @inheritdoc */
		public function __construct(
			SourceDetector $sourceDetector,
			FileFactory $fileFactory,
			Response $response,
			\iConfiguration $configuration
		) {
			$this->sourceDetector = $sourceDetector;
			$this->fileFactory = $fileFactory;
			$this->response = $response;
			$this->configuration = $configuration;
		}

		/** @inheritdoc */
		public function download() {
			$path = $this->getFilePath();
			$file = $this->getFileFactory()
				->create($path);
			$this->getResponse()
				->downloadAndDelete($file);
		}

		/**
		 * Возвращает путь до файла с результатами экспорта
		 * @return string
		 */
		private function getFilePath() {
			$temporaryPath = $this->getConfiguration()
				->includeParam('sys-temp-path');
			$sourceName = $this->getSourceDetector()
				->detectForExport();
			return sprintf('%s/export/%s.csv', $temporaryPath, $sourceName);
		}

		/**
		 * Возвращает определитель источника
		 * @return SourceDetector
		 */
		private function getSourceDetector() {
			return $this->sourceDetector;
		}

		/**
		 * Возвращает фабрику файлов
		 * @return FileFactory
		 */
		private function getFileFactory() {
			return $this->fileFactory;
		}

		/**
		 * Возвращает фасад вывода
		 * @return Response
		 */
		private function getResponse() {
			return $this->response;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}
	}
