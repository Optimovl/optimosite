<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\Csv;

	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;
	use UmiCms\Classes\System\Utils\QuickExchange\Source\iDetector as SourceDetector;
	use UmiCms\System\Request\iFacade as iRequest;
	use UmiCms\System\Session\iSession;

	/**
	 * Клас импортера
	 * @package UmiCms\Classes\System\Utils\QuickExchange\Csv
	 */
	class Importer implements iImporter {

		/** @var SourceDetector $sourceDetector определитель источника */
		private $sourceDetector;

		/** @var iRequest $request фасад запроса */
		private $request;

		/** @var FileFactory $fileFactory фабрика файлов */
		private $fileFactory;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var iSession $session фасад сессии */
		private $session;

		/** @const int DEFAULT_LIMIT ограничение на количество сущностей, импортируемых за одну итерацию по умолчанию */
		const DEFAULT_LIMIT = 25;

		/** @inheritdoc */
		public function __construct(
			SourceDetector $sourceDetector,
			iRequest $request,
			FileFactory $fileFactory,
			\iConfiguration $configuration,
			iSession $session
		) {
			$this->sourceDetector = $sourceDetector;
			$this->request = $request;
			$this->fileFactory = $fileFactory;
			$this->configuration = $configuration;
			$this->session = $session;
		}

		/** @inheritdoc */
		public function import(\selector $query, $encoding) {
			$file = $this->getFile();

			if ($file->getIsBroken()) {
				throw new \publicAdminException('Import file is broken');
			}

			$splitter = $this->createSplitter($query, $encoding);
			$session = $this->getSession();
			$offsetIndex = $this->getOffsetIndex();
			$splitter->load($file->getFilePath(), $this->getLimit(), $session->get($offsetIndex));
			$umiDump = $splitter->translate($splitter->getDocument());

			$importer = $this->createImporter($query, $splitter);
			$importer->loadXmlString($umiDump);
			$importer->execute();

			if ($splitter->getIsComplete()) {
				$session->del($offsetIndex);
				$file->delete();
			} else {
				$session->set($offsetIndex, $splitter->getOffset());
			}

			return $splitter->getIsComplete();
		}

		/**
		 * Создает разделитель данных (переводчик csv => umiDump)
		 * @param \selector $query выборка сущностей, с помощью которой определяется тип импортируемых
		 * сущностей (объекты/страницы).
		 * @param string $encoding кодировка csv файла (windows-1251 / utf-8)
		 * @return \QuickCsvObjectSplitter|\QuickCsvPageSplitter
		 */
		private function createSplitter(\selector $query, $encoding) {
			/** @todo: произвести рефакторинг umiImportSplitter, передать фабрику в зависимость этому классу */
			$splitter = \umiImportSplitter::get($this->getSplitterPrefix($query));
			$sourceName = $this->getSourceDetector()
				->detectForImport();
			/** @var \QuickCsvPageSplitter|\QuickCsvObjectSplitter $splitter */
			$splitter->setSourceName($sourceName);
			$splitter->setEncoding($encoding);
			return $splitter;
		}

		/**
		 * Создает импортер данных
		 * @param \selector $query выборка сущностей, с помощью которой определяется идентификатор родителя (для страниц).
		 * @param \iUmiImportSplitter $splitter разделитель данных (переводчик csv => umiDump)
		 * @return \iXmlImporter
		 */
		private function createImporter(\selector $query, \iUmiImportSplitter $splitter) {
			/** @todo: добавить фабрику передать ее в зависимость этому классу */
			$importer = new \xmlImporter();
			$parentId = $this->getParentId($query);

			if ($parentId) {
				$importer->setDestinationElement($parentId);
			}

			$importer->setIgnoreParentGroups($splitter->getIgnoreParentGroups());
			$importer->setAutoGuideCreation($splitter->getAutoGuideCreation());
			$importer->setRenameFiles($splitter->getRenameFiles());
			return $importer;
		}

		/**
		 * Возвращает префикс класса сплиттера
		 * @param \selector $query выборка сущностей, с помощью которой определяется тип импортируемых
		 * сущностей (объекты/страницы).
		 * @return string
		 */
		private function getSplitterPrefix(\selector $query) {
			return ($query->__get('mode') == 'pages') ? 'QuickCsvPage' : 'QuickCsvObject';
		}

		/**
		 * Возвращает файл, который необходимо импортировать
		 * @return \iUmiFile
		 */
		private function getFile() {
			$path = $this->getRequest()
				->Post()
				->get('file');
			return $this->getFileFactory()
				->createSecure(sprintf('.%s', $path));
		}

		/**
		 * Возвращает индекс смещения выборки для хранения в сессии
		 * @return string
		 */
		private function getOffsetIndex() {
			$sourceName = $this->getSourceDetector()
				->detectForImport();
			return sprintf('import_offset_%s', $sourceName);
		}

		/**
		 * Возвращает ограничение на количество сущностей, импортируемых за одну итерацию
		 * @return int
		 */
		private function getLimit() {
			return $this->getConfiguration()
				->get('modules', 'exchange.splitter.limit') ?: self::DEFAULT_LIMIT;
		}

		/**
		 * Возвращает идентификатор родительской страницы
		 * @param \selector $query выборка сущностей, с помощью которой определяется идентификатор родителя (для страниц).
		 * @return int|null
		 */
		private function getParentId(\selector $query) {
			if (is_array($query->__get('hierarchy')) && umiCount($query->__get('hierarchy')) > 0) {
				return $query->__get('hierarchy')[0]->elementId;
			}

			return null;
		}

		/**
		 * Возвращает определителя источника
		 * @return SourceDetector
		 */
		private function getSourceDetector() {
			return $this->sourceDetector;
		}

		/**
		 * Возвращает фасад запроса
		 * @return iRequest
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает фабрику файлов
		 * @return FileFactory
		 */
		private function getFileFactory() {
			return $this->fileFactory;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает фасад сессии
		 * @return iSession
		 */
		private function getSession() {
			return $this->session;
		}
	}
