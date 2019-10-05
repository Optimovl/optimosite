<?php

use UmiCms\Service;

/** Тип экспорта в формате CSV */
	class csvExporter extends umiExporter {

		/** @const string символ, обрамляющий значения полей */
		const VALUE_LIMITER = '"';

		/** @const string наименование исходной кодировки данных */
		const SOURCE_ENCODING = 'utf-8';

		/** @var string[] список поддерживаемых кодировок */
		protected static $supportedEncodings = ['utf-8', 'windows-1251', 'cp1251'];

		/**
		 * @var int[] Список идентификаторов сущностей, которые нужно экспортировать
		 * [
		 *     <id> => <id>
		 * ]
		 */
		protected $entityIdList = [];

		/** @var string Путь до кэш-файла со списком идентификаторов сущностей, которые нужно экспортировать */
		protected $entityIdListFilePath;

		/** @var string $sourceName строковой идентификатор источника экспорта */
		protected $sourceName;

		/** @var string[] Список строковых идентификаторов свойств, которые будут экспортированы в CSV-файл. */
		protected $nameList = [];

		/** @var string[] Список названий свойств, которые будут экспортированы в CSV-файл. */
		protected $titleList = [];

		/** @var string[] Список типов свойств, которые будут экспортированы в CSV-файл. */
		protected $typeList = [];

		/** @var xmlExporter экземпляр экспорта сущностей системы в формате UMIDUMP */
		protected $exporter;

		/** @var bool Определяет, нужно ли выводить экспортированные данные в буфер вывода */
		protected $shouldFlushToBuffer;

		/** @var string Путь до CSV-файла, который является результатом экспорта */
		private $csvFilePath;

		/** @var string наименование кодировки, в которой будут экспортированы данные */
		private $encoding = 'windows-1251';

		/** @var string разделитель полей */
		private $propertyDelimiter = ';';

		/** @var string разделитель значений */
		private $valueDelimiter = ',';

		/** @var string разделитель частей значений */
		private $partDelimiter = '|';

		/** @var string разделитель под-частей */
		private $subPartDelimiter = ':';

		/** @var array части значения поля с типом "Множественное изображение" */
		private $multipleImageParts = ['path', 'alt', 'ord'];

		/** @var array части значения поля с типом "Составное" */
		private $optionedParts = ['int', 'varchar', 'text', 'float'];

		/** @var string $fileName имя экспортируемого файла */
		private $fileName;

		/** @inheritdoc */
		public function __construct($type) {
			parent::__construct($type);
			ini_set('mbstring.substitute_character', 'none');
		}

		/** @inheritdoc */
		public function setOutputBuffer() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->charset($this->encoding);
			$buffer->contentType('text/plain');
			return $buffer;
		}

		/**
		 * Устанавливает кодировку, в которой будут экспортированы данные
		 * @param string $encoding наименование устанавливаемой кодировки
		 * @throws InvalidArgumentException если кодировка не поддерживается
		 */
		public function setEncoding($encoding) {
			if (in_array(mb_strtolower($encoding), self::$supportedEncodings)) {
				$this->encoding = $encoding;
			} else {
				throw new InvalidArgumentException("Encoding '${encoding}' is not supported");
			}
		}

		/**
		 * Устанавливает символ, представляющий разделитель полей
		 * @param string $delimiter символ-разделитель
		 */
		public function setDelimiter($delimiter) {
			if ($delimiter) {
				$this->propertyDelimiter = (string) $delimiter;
			}
		}

		/**
		 * Устанавливает имя файла
		 * @param string $name имя файла
		 * @return $this
		 * @throws InvalidArgumentException
		 */
		public function setFileName($name) {
			if (!is_string($name) || $name === '') {
				throw new InvalidArgumentException('Invalid file name given');
			}

			$this->fileName = $name;
			return $this;
		}

		/** @inheritdoc */
		public function getFileExt() {
			return 'csv';
		}

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			$this->initialize();
			$this->loadEntityIdList($exportList, $ignoreList);
			$this->doOneIteration();
			$this->updateEntityIdList();
			$this->determineCompleteness();

			if ($this->getIsCompleted()) {
				$this->writeFinalCsvFile();
			}

			return false;
		}

		/**
		 * Определяет был ли процесс уже запущен
		 * @return bool
		 */
		public function isStarted() {
			$this->initialize();
			return file_exists($this->entityIdListFilePath);
		}

		/**
		 * Загружает сущности, которые нужно экспортировать.
		 * По умолчанию, работает со страницами, в качестве аргументов передаются корневые страницы,
		 * а экспорт производится на всю вложенность.
		 * @param iUmiEntinty[]|int[] $exportList список сущностей или их идентификаторов, которые требуется экспортировать
		 * @param iUmiEntinty[]|int[] $ignoreList список сущностей или их идентификаторов, которые требуется проигнорировать
		 * @throws selectorException
		 */
		protected function loadEntityIdList($exportList, $ignoreList) {
			$this->entityIdList = [];

			if (file_exists($this->entityIdListFilePath)) {
				$this->entityIdList = unserialize(file_get_contents($this->entityIdListFilePath));
				return;
			}

			if (empty($exportList)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->level(0);
				$sel->option('no-length')->value(true);
				$exportList = (array) $sel->result();
			}

			$exportIdList = $this->getEntityIdList($exportList);

			foreach ($exportIdList as $id) {
				$this->entityIdList[$id] = $id;
			}

			$ignoreIdList = $this->getEntityIdList($ignoreList);

			foreach ($ignoreIdList as $id) {
				unset($this->entityIdList[$id]);
			}
		}

		/**
		 * Возвращает список идентификаторов сущностей, которые нужно экспортировать.
		 * По умолчанию, работает со страницами, в качестве аргументов передаются корневые страницы,
		 * а экспорт производится на всю вложенность.
		 * @param iUmiEntinty[]|int[] $exportList список сущностей или их идентификаторов, которые требуется экспортировать
		 * @return array
		 */
		protected function getEntityIdList($exportList) {
			$umiHierarchy = umiHierarchy::getInstance();
			$entityIdList = [];

			foreach ($exportList as $branch) {
				if (!$branch instanceof iUmiHierarchyElement) {
					$branch = $umiHierarchy->getElement($branch, true, true);
				}

				if (!$branch instanceof iUmiHierarchyElement) {
					continue;
				}

				$branchId = $branch->getId();
				$entityIdList[] = $branchId;

				$childrenIdList = $umiHierarchy->getChildrenList($branchId);
				foreach ($childrenIdList as $id) {
					$entityIdList[] = $id;
				}
			}

			return $entityIdList;
		}

		/** Инициализирует систему для подготовки к экспорту */
		private function initialize() {
			$this->shouldFlushToBuffer = (getRequest('as_file') === '0');

			$scenarioId = $this->getFileName() ?: getRequest('param0');
			$this->sourceName = "{$scenarioId}.{$this->getFileExt()}";
			$exportDir = $this->getExportPath();

			if (!is_dir($exportDir)) {
				mkdir($exportDir, 0777, true);
			}

			$this->csvFilePath = $exportDir . $this->sourceName;
			$this->entityIdListFilePath = $this->csvFilePath . 'array';

			if (file_exists($this->csvFilePath) && !file_exists($this->entityIdListFilePath)) {
				unlink($this->csvFilePath);
			}
		}

		/**
		 * Возвращает имя файла
		 * @return string
		 */
		private function getFileName() {
			return $this->fileName;
		}

		/** Выполняет одну итерацию экспорта */
		private function doOneIteration() {
			$this->initializeExporter();
			$document = $this->exporter->execute();
			$this->loadHeaders();
			$propertyList = $this->getPropertyList($document);
			$this->saveHeaders();
			$this->appendRowsToCsvFile($propertyList);
		}

		/**
		 * Создает и настраивает объект экспорта.
		 * По умолчанию работает со страницами.
		 */
		protected function initializeExporter() {
			$this->exporter = $this->createXmlExporter($this->sourceName, $this->getLimit());
			$this->exporter->ignoreRelationsExcept('options');

			$entityIdList = $this->entityIdList;

			if (!$this->shouldFlushToBuffer) {
				$entityIdList = array_slice($entityIdList, 0, $this->getLimit() + 1);
			}

			$this->exporter->addElements($entityIdList);
		}

		/** @inheritdoc */
		protected function getLimit() {
			if ($this->shouldFlushToBuffer) {
				return false;
			}
			return parent::getLimit();
		}

		/**
		 * Возвращает список свойства всех экспортированных сущностей.
		 * По умолчанию работает со страницами.
		 * @param DOMDocument $document результат экспорта в формате UMIDUMP
		 * @return string[]
		 */
		protected function getPropertyList(DOMDocument $document) {
			$propertyList = [];

			$xpath = new DOMXPath($document);
			$pageNodes = $xpath->query('//pages/page');

			/** @var DOMElement $node */
			foreach ($pageNodes as $node) {
				$propertyList[] = $this->getEntityPropertyList($node);
			}

			return $propertyList;
		}

		/**
		 * Возвращает список свойств сущности.
		 * По умолчанию работает со страницами.
		 * @param DOMElement $entity узел сущности в формате UMIDUMP
		 * @return array|mixed
		 */
		protected function getEntityPropertyList(DOMElement $entity) {
			$propertyList = $this->getSystemProperties($entity);
			$propertyNodeList = $entity->getElementsByTagName('property');

			/** @var DOMElement $propertyNode */
			foreach ($propertyNodeList as $propertyNode) {
				$name = $propertyNode->getAttribute('name');
				$value = $this->getPropertyValue($propertyNode);

				$index = array_search($name, $this->nameList);

				if (!$index) {
					$index = $this->appendPropertyToHeaders($propertyNode);
				}

				$propertyList[$index] = $value;
			}

			return $this->normalizeProperties($propertyList);
		}

		/**
		 * Возвращает системные свойства сущности.
		 * По умолчанию работает со страницами.
		 * @param DOMElement $entity узел сущности в формате UMIDUMP
		 * @return mixed
		 */
		protected function getSystemProperties(DOMElement $entity) {
			$id = $entity->getAttribute('id');
			$name = $entity->getElementsByTagName('name')->item(0)->nodeValue;
			$typeId = $entity->getAttribute('type-id');
			$isActive = $entity->getAttribute('is-active');

			$templateId = '';

			if ($entity->getElementsByTagName('template')->length) {
				$templateId = $entity->getElementsByTagName('template')
					->item(0)
					->getAttribute('id');
			}

			$parentId = $entity->hasAttribute('parentId') ? $entity->getAttribute('parentId') : 0;
			$altName = $entity->getAttribute('alt-name');
			return [
				$id,
				$name,
				$typeId,
				$isActive,
				$templateId,
				$parentId,
				$altName
			];
		}

		/** Загружает заголовки по умолчанию (с системными свойствами). */
		protected function loadSystemHeaders() {
			$this->nameList = [
				'id',
				'name',
				'type-id',
				'is-active',
				'template-id',
				'parent-id',
				'alt-name'
			];
			$this->titleList = [
				getLabel('csv-property-id', 'exchange'),
				getLabel('csv-property-name', 'exchange'),
				getLabel('csv-property-type-id', 'exchange'),
				getLabel('csv-property-is-active', 'exchange'),
				getLabel('csv-property-template-id', 'exchange'),
				getLabel('csv-property-parent-id', 'exchange'),
				getLabel('csv-property-alt-name', 'exchange'),
			];
			$this->typeList = [
				'native',
				'native',
				'native',
				'native',
				'native',
				'native',
				'native',
			];
		}

		/**
		 * Обновляет список идентификаторов сущностей, которые еще нужно экспортировать.
		 * По умолчанию работает со страницами.
		 */
		protected function updateEntityIdList() {
			$exportedEntityList = array_keys($this->exporter->getExportedElements());
			$this->entityIdList = array_diff($this->entityIdList, $exportedEntityList);

			if (!$this->shouldFlushToBuffer) {
				$this->saveEntityIdList();
			}
		}

		/**
		 * Сохраняет в кэш-файл список сущностей, которые еще нужно экспортировать.
		 * Если таких сущностей не осталось, кэш-файл удаляется.
		 */
		protected function saveEntityIdList() {
			if (umiCount($this->entityIdList)) {
				file_put_contents($this->entityIdListFilePath, serialize($this->entityIdList));
			} elseif (file_exists($this->entityIdListFilePath)) {
				unlink($this->entityIdListFilePath);
			}
		}

		/**
		 * Загружает заголовки CSV-файла.
		 *
		 * Заголовки - это три первых ряда в файле:
		 *   - строковые идентификаторы свойств
		 *   - названия свойств
		 *   - типы свойств
		 */
		private function loadHeaders() {
			$temporaryFile = $this->getTemporaryFile();

			if ($temporaryFile->isReadable()) {
				list(
					$this->nameList,
					$this->titleList,
					$this->typeList
					) = unserialize($temporaryFile->getContent());
			} else {
				$this->loadSystemHeaders();
			}
		}

		/**
		 * Возвращает временный файл
		 * @return iUmiFile
		 */
		private function getTemporaryFile() {
			return new umiFile($this->getTemporaryFilePath());
		}

		/**
		 * Возвращает путь до временного файла
		 * @return string
		 */
		private function getTemporaryFilePath() {
			return "{$this->csvFilePath}.tmp";
		}

		/**
		 * Возвращает тип свойства
		 * @param DOMElement $property узел свойства
		 * @return string
		 */
		private function getPropertyType(DOMElement $property) {
			$type = $property->getAttribute('type');
			if ($type == 'relation') {
				if ($property->hasAttribute('multiple') && $property->getAttribute('multiple') == 'multiple') {
					$type = 'multiple-relation';
				}
			}
			return $type;
		}

		/**
		 * Возвращает значение свойства
		 * @param DOMElement $property узел свойства
		 * @return string
		 */
		private function getPropertyValue(DOMElement $property) {

			switch ($this->getPropertyType($property)) {
				case 'relation' :
				case 'multiple-relation' : {
					return $this->getRelationValue($property);
				}
				case 'tags' : {
					return $property->getElementsByTagName('combined')->item(0)->nodeValue;
				}
				case 'symlink' : {
					return $this->getSymlinkValue($property);
				}
				case 'multiple_image' : {
					return $this->getMultipleImageValue($property);
				}
				case 'optioned' : {
					return $this->getOptionedValue($property);
				}
				case 'domain_id' :
				case 'domain_id_list' : {
					return $this->getDomainValue($property);
				}
				default : {
					return $property->getElementsByTagName('value')->item(0)->nodeValue;
				}
			}
		}

		/**
		 * Возвращает значение свойства с типом "Выпадающий список"
		 * или "Выпадающий список со множественным выбором"
		 * @param DOMElement $property узел свойства
		 * @return string
		 */
		private function getRelationValue(DOMElement $property) {
			$names = [];
			$valueNode = $property->getElementsByTagName('value')->item(0);

			/** @var DOMElement $itemNode */
			foreach ($valueNode->getElementsByTagName('item') as $itemNode) {
				$names[] = $itemNode->getAttribute('name');
			}

			return $this->joinMultipleValues($names);
		}

		/**
		 * Возвращает значение свойства с типом "Ссылка на домен"
		 * или "Ссылка на список доменов"
		 * @param DOMElement $property узел свойства
		 * @return string
		 */
		private function getDomainValue(DOMElement $property) {
			$valueNode = $property->getElementsByTagName('value')->item(0);
			$domainIdList = [];

			/** @var DOMElement $domainNode */
			foreach ($valueNode->getElementsByTagName('domain') as $domainNode) {
				$domainIdList[] = $domainNode->getAttribute('id');
			}

			return $this->joinMultipleValues($domainIdList);
		}

		/**
		 * Соединяет несколько значений свойства в одну строку
		 * @param string[] $values значения
		 * @return string
		 */
		private function joinMultipleValues($values) {
			return implode($this->valueDelimiter, $values);
		}

		/**
		 * Возвращает значение свойства с типом "Ссылка на дерево"
		 * @param DOMElement $property узел свойства
		 * @return string
		 */
		private function getSymlinkValue(DOMElement $property) {
			$ids = [];
			$valueNode = $property->getElementsByTagName('value')->item(0);

			/** @var DOMElement $pageNode */
			foreach ($valueNode->getElementsByTagName('page') as $pageNode) {
				$ids[] = $pageNode->getAttribute('id');
			}

			return $this->joinMultipleValues($ids);
		}

		/**
		 * Возвращает значение свойства с типом "Множественное изображение"
		 * @param DOMElement $property узел свойства
		 * @return string
		 */
		private function getMultipleImageValue($property) {
			$images = [];

			/** @var DOMElement $valueNode */
			foreach ($property->getElementsByTagName('value') as $valueNode) {
				$parts = [];
				foreach ($this->multipleImageParts as $partName) {
					$parts[] = $this->makePart($partName, $valueNode->getAttribute($partName));
				}

				$images[] = $this->joinMultipleParts($parts);
			}

			return $this->joinMultipleValues($images);
		}

		/**
		 * Создает часть значения свойства
		 * @param string $name название части
		 * @param string $value значение части
		 * @return string
		 */
		private function makePart($name, $value) {
			return "{$name}{$this->subPartDelimiter}{$value}";
		}

		/**
		 * Соединяет несколько частей значения свойства в одну строку
		 * @param string[] $parts значения
		 * @return string
		 */
		private function joinMultipleParts($parts) {
			return implode($this->partDelimiter, $parts);
		}

		/**
		 * Возвращает значение свойства с типом "Составное"
		 * @param DOMElement $property узел свойства
		 * @return string
		 */
		private function getOptionedValue($property) {
			$options = [];

			/** @var DOMElement $optionNode */
			foreach ($property->getElementsByTagName('option') as $optionNode) {
				$options[] = $this->getOption($optionNode);
			}

			return $this->joinMultipleValues($options);
		}

		/**
		 * Возвращает значение одной опции из опционного свойства
		 * @param DOMElement $optionNode узел опции
		 * @return string
		 */
		private function getOption(DOMElement $optionNode) {
			$objectId = '';
			$objectNodeList = $optionNode->getElementsByTagName('object');
			if ($objectNodeList->length > 0) {
				$objectId = $objectNodeList->item(0)->getAttribute('id');
			}

			$pageId = '';
			$pageNodeList = $optionNode->getElementsByTagName('page');
			if ($pageNodeList->length > 0) {
				$pageId = $pageNodeList->item(0)->getAttribute('id');
			}

			$parts = [
				$this->makePart('object-id', $objectId),
				$this->makePart('page-id', $pageId)
			];

			foreach ($this->optionedParts as $partName) {
				$value = '';
				if ($optionNode->hasAttribute($partName)) {
					$value = $optionNode->getAttribute($partName);
				}

				$parts[] = $this->makePart($partName, $value);
			}

			return $this->joinMultipleParts($parts);
		}

		/**
		 * Добавляет в CSV-заголовки информацию о новом свойстве
		 * и возвращает его индекс.
		 * @param DOMElement $property узел свойства
		 * @return int
		 */
		private function appendPropertyToHeaders(DOMElement $property) {
			$name = $property->getAttribute('name');
			$title = $property->getElementsByTagName('title')->item(0)->nodeValue;
			$type = $this->getPropertyType($property);

			$this->nameList[] = $name;
			$index = (int) array_search($name, $this->nameList);
			$this->titleList[$index] = $title;
			$this->typeList[$index] = $type;

			return $index;
		}

		/**
		 * Возвращает свойства сущности в нормализованном виде
		 * @param string[] $propertyList свойства сущности
		 * @return array
		 */
		private function normalizeProperties($propertyList) {
			foreach (array_keys($this->nameList) as $index) {
				if (array_key_exists($index, $propertyList)) {
					$propertyList[$index] = str_replace(
						self::VALUE_LIMITER,
						str_repeat(self::VALUE_LIMITER, 2),
						$propertyList[$index]
					);
				} else {
					$propertyList[$index] = '';
				}
			}

			ksort($propertyList);
			return $propertyList;
		}

		/**
		 * Сохраняет в кэш-файл заголовки CSV-файла.
		 * @see csvExporter::loadHeaders()
		 */
		private function saveHeaders() {
			$temporaryFile = $this->getTemporaryFile();
			$temporaryFile->putContent(serialize([
				$this->nameList,
				$this->titleList,
				$this->typeList,
			]));
		}

		/**
		 * Дополняет CSV-файл новыми рядами
		 * @param array $propertyList список свойств всех экспортированных сущностей
		 */
		private function appendRowsToCsvFile($propertyList) {
			$csvFile = fopen($this->csvFilePath, 'a');

			foreach ($propertyList as $entityPropertyList) {
				$row = $this->makeRow($entityPropertyList);
				fwrite($csvFile, $row);
			}

			fclose($csvFile);
		}

		/**
		 * Создает CSV-ряд из списка свойств сущности и возвращает этот ряд.
		 * @param string[] $propertyList свойства сущности
		 * @return string
		 */
		private function makeRow(array $propertyList) {
			$delimiter = self::VALUE_LIMITER . $this->propertyDelimiter . self::VALUE_LIMITER;
			$row = self::VALUE_LIMITER . implode($delimiter, $propertyList) . self::VALUE_LIMITER . PHP_EOL;
			return mb_convert_encoding($row, $this->encoding, self::SOURCE_ENCODING);
		}

		/** Определяет статус завершенности экспорта */
		private function determineCompleteness() {
			$this->completed = (umiCount($this->entityIdList) === 0);
		}

		/** Записывает конечный результат экспорта в CSV-файл */
		private function writeFinalCsvFile() {
			$csvHeaders = (array) unserialize($this->getTemporaryFile()->getContent());
			$temporaryCsvFile = fopen($this->getTemporaryFilePath(), 'w');

			foreach ($csvHeaders as $propertyList) {
				$row = $this->makeRow($propertyList);
				fwrite($temporaryCsvFile, $row);
			}

			$csvFile = fopen($this->csvFilePath, 'r');
			while (!feof($csvFile)) {
				$row = $this->findNextRowInCsvFile($csvFile);
				fwrite($temporaryCsvFile, $row);
			}

			fclose($temporaryCsvFile);
			fclose($csvFile);

			unlink($this->csvFilePath);
			rename($this->getTemporaryFilePath(), $this->csvFilePath);
			chmod($this->csvFilePath, 0777);
		}

		/**
		 * Находит следующий ряд в CSV-файле и возвращает его
		 * @param resource $file CSV-файл
		 * @return string
		 */
		private function findNextRowInCsvFile($file) {
			$candidateRow = '';
			do {
				$candidateRow .= (string) fgets($file);
			} while (!feof($file) && !$this->isValidRow($candidateRow));
			return $candidateRow;
		}

		/**
		 * Определяет, правильно ли сформирован CSV-ряд.
		 * Ряд считается правильным, если в нем сбалансированы двойные кавычки.
		 * @param string $row ряд
		 * @return bool
		 */
		private function isValidRow($row) {
			return mb_substr_count($row, self::VALUE_LIMITER) % 2 == 0;
		}
	}
