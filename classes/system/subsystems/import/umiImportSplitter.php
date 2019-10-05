<?php

	/**
	 * Абстрактный тип импорта.
	 * Умеет загружать конкретные реализации типов импорта, @see umiImportSplitter::get()
	 * @todo: update interface
	 * @todo: выделить из класса фабрику и, возможно, коллекцию
	 *
	 * Задачи типа импорта:
	 *   - Преобразовывать по частям файл импорта в формат UMIDUMP, @see umiImportSplitter::getDocument()
	 *   - Преобразовывать документ в формате UMIDUMP по шаблону, @see umiImportSplitter::translate()
	 *
	 * Реальный импорт данных в систему производится только из формата UMIDUMP
	 * в классе @see xmlImporter . Каждая конкретный тип импорта только
	 * преобразовывает данные из произвольного формата в формат UMIDUMP.
	 */
	abstract class umiImportSplitter implements iUmiImportSplitter {

		/** @var iUmiImportSplitter[] Кэш загруженных конкретных типов импорта */
		private static $importers;

		/** @var int Отступ в файле импорта */
		protected $offset = 0;

		/** @var int Количество импортируемых за одну итерацию сущностей */
		protected $block_size = 10;

		/** @var string Тип импорта (префикс конкретного класса) */
		protected $type = '';

		/** @var bool|string Путь до файла импорта */
		protected $file_path = false;

		/** @var bool Статус завершенности импорта */
		protected $complete = false;

		/**
		 * @todo: свойство не должно быть публичным, нужно добавить сеттер
		 * @var bool Режим, при котором новые создаваемые типы данных
		 * не будут наследовать группы и поля родительского типа данных.
		 */
		public $ignoreParentGroups = true;

		/**
		 * @todo: свойство не должно быть публичным, нужно добавить сеттер
		 * @var bool Режим, при котором будут автоматически создаваться
		 * новые типы данных (справочники).
		 */
		public $autoGuideCreation = false;

		/**
		 * @todo: свойство не должно быть публичным, нужно добавить сеттер
		 * @var bool Режим, при котором файлы, указанные в импортируемых полях типа "файл"
		 * будут переименовываться в более удобное название.
		 */
		public $renameFiles = false;

		/**
		 * Преобразовывает очередную часть импортируемых данных
		 * в документ формата UMIDUMP и возвращает этот документ.
		 * @return DOMDocument
		 */
		abstract protected function readDataBlock();

		/**
		 * Возвращает объект конкретного типа импорта по названию его класса
		 * @param string $prefix префикс названия класса типа импорта
		 * @return self
		 * @throws publicException
		 */
		final public static function get($prefix) {
			$importer = self::loadWrapper($prefix);
			if ($importer instanceof self) {
				return $importer;
			}
			throw new publicException("Can't load splitter for type \"{$prefix}\"");
		}

		/**
		 * Преобразует входной документ из формата UMIDUMP
		 * в формат конкретного типа импорта и возвращает результат преобразования.
		 * @param DomDocument $document документ
		 * @return string
		 * @throws publicException
		 */
		public function translate(DOMDocument $document) {
			$template = CURRENT_WORKING_DIR . '/xsl/import/' . $this->type . '.xsl';
			if (!is_file($template)) {
				throw new publicException("Can't load translator {$template}");
			}

			$templater = umiTemplater::create('XSLT', $template);
			return $templater->parse($document);
		}

		/** @inheritdoc */
		public function __construct($type) {
			$this->type = $type;
		}

		/**
		 * Загружает конкретный тип импорта и возвращает его
		 * @param string $prefix префикс названия класса типа импорта
		 * @return iUmiImportSplitter
		 * @throws publicException
		 */
		private static function loadWrapper($prefix) {
			if (isset(self::$importers[$prefix])) {
				return self::$importers[$prefix];
			}

			self::$importers[$prefix] = false;
			$className = "{$prefix}Splitter";

			if (!class_exists($className)) {
				$filePath = mainConfiguration::getInstance()
						->includeParam('system.kernel') . "subsystems/import/splitters/{$className}.php";

				if (!is_file($filePath)) {
					throw new publicException("Can't load splitter \"{$filePath}\" for \"{$prefix}\" file type");
				}

				require $filePath;
			}

			if (!class_exists($className)) {
				throw new publicException("Splitter class \"{$className}\" not found");
			}

			$importer = new $className($prefix);
			if (!$importer instanceof self) {
				throw new publicException("Splitter class \"{$className}\" should be instance of umiImportSplitter");
			}

			self::$importers[$prefix] = $importer;
			return $importer;
		}

		/**
		 * Устанавливает параметры импорта
		 * @param string $filePath путь до файла импорта
		 * @param int $blockSize количество импортируемых за одну итерацию сущностей
		 * @param int $offset отступ импорта
		 * @throws publicException
		 */
		public function load($filePath, $blockSize = 100, $offset = 0) {
			if (!is_file($filePath)) {
				throw new publicException("File {$filePath} does not exist.");
			}
			$this->block_size = (int) $blockSize;
			$this->offset = (int) $offset;
			$this->file_path = $filePath;
		}

		/**
		 * Возвращает статус завершенности импорта
		 * @return bool
		 */
		public function getIsComplete() {
			return $this->complete;
		}

		/**
		 * Возвращает очередную часть импортируемых данных
		 * в формате UMIDUMP как XML-текст.
		 * @return bool
		 */
		public function getXML() {
			$doc = $this->readDataBlock();
			return $doc ? $doc->saveXML() : false;
		}

		/**
		 * Возвращает очередную часть импортируемых данных в формате UMIDUMP.
		 * @return DOMDocument
		 */
		public function getDocument() {
			return $this->readDataBlock();
		}

		/**
		 * Возвращает отступ импорта
		 * @return int
		 */
		public function getOffset() {
			return $this->offset;
		}

		/** @inheritdoc */
		public function getRenameFiles() {
			return (bool) $this->renameFiles;
		}

		/** @inheritdoc */
		public function getIgnoreParentGroups() {
			return $this->ignoreParentGroups;
		}

		/** @inheritdoc */
		public function getAutoGuideCreation() {
			return $this->autoGuideCreation;
		}
	}
