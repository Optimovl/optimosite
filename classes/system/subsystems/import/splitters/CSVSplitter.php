<?php

	use UmiCms\Service;

	/** Тип импорта в формате CSV */
	class csvSplitter extends umiImportSplitter {

		/** @const string Символ, обрамляющий значения полей */
		const VALUE_LIMITER = '"';

		/** @const string Наименование кодировки, в которую будут преобразованы данные */
		const TARGET_ENCODING = 'utf-8';

		/** @const string Версия UMIDUMP */
		const VERSION = '2.0';

		/** @const int Количество рядов-заголовков в CSV-файле импорта */
		const HEADER_COUNT = 3;

		/** @const int Количество частей значения поля "Множественное изображение" */
		const MULTIPLE_IMAGE_PART_COUNT = 3;

		/** @const int Количество частей значения поля "Составное" */
		const OPTIONED_PART_COUNT = 6;

		/** @var bool создавать ли справочники автоматически */
		public $autoGuideCreation = true;

		/** @var array список поддерживаемых кодировок */
		protected static $supportedEncodings = ['utf-8', 'windows-1251', 'cp1251'];

		/** @var string имя источника импорта */
		protected $sourceName;

		/**
		 * @var array атрибуты сущности, значения которых берутся прямо
		 * из файла импорта.
		 */
		protected $simpleAttributeList = ['id', 'is-active', 'is-visible', 'is-deleted', 'alt-name'];

		/**
		 * @var array атрибуты сущности, значения которых определяются
		 * отдельными алгоритмами.
		 */
		protected $specialAttributeList = ['type-id', 'parent-id'];

		/** @var DOMDocument документ в формате UMIDUMP */
		protected $document;

		/** @var iUmiImportRelations объект для связи импортируемых сущностей */
		protected $relations;

		/** @var DOMElement Узел текущей сущности */
		protected $entity;

		/**
		 * @var DOMElement Узел группы текущей сущности
		 * (в конечном UMIDUMP создается только одна группа)
		 */
		protected $group;

		/** @var array список имен полей */
		protected $nameList = [];

		/** @var int Идентификатор текущей сущности */
		private $entityId;

		/** @var DOMElement Узел текущего свойства текущей сущности */
		private $property;

		/** @var resource указатель на CSV-файл импорта */
		private $importFile;

		/** @var string Идентификатор сценария импорта */
		private $importId;

		/** @var int Порядковый номер текущего ряда в CSV-файле */
		private $position = 0;

		/** @var string наименование кодировки, в которой находятся импортируемые данные */
		private $encoding = 'windows-1251';

		/** @var array список кодовых названий типов полей */
		private $typeList = [];

		/** @var array список заголовков полей */
		private $titleList = [];

		/** @var bool сбрасывать ли значение параметра объекта при пустом значении */
		private $resetVal = false;

		/** @var string разделитель полей */
		private $propertyDelimiter = ';';

		/** @var string разделитель значений */
		private $valueDelimiter = ',';

		/** @var string разделитель частей значений */
		private $partDelimiter = '|';

		/** @var string разделитель под-частей */
		private $subPartDelimiter = ':';

		/** @inheritdoc */
		public function __construct($type) {
			parent::__construct($type);
			ini_set('mbstring.substitute_character', 'none');
			$this->setResetVal(mainConfiguration::getInstance()->get('kernel', 'import-csv-reset-value'));
		}

		/**
		 * Устанавливает значение опции "Сбрасывать значение параметра объекта при пустом значении"
		 * @param bool $val новое значение
		 */
		public function setResetVal($val) {
			$this->resetVal = (bool) $val;
		}

		/**
		 * Устанавливает кодировку, в которой находятся импортируемые данные
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
		 * Устанавливает имя источника данных
		 * @param string $name имя источника
		 * @return $this
		 * @throws InvalidArgumentException
		 */
		public function setSourceName($name) {
			if (!is_string($name) || $name === '') {
				throw new InvalidArgumentException('Incorrect source name given');
			}

			$this->sourceName = $name;
			return $this;
		}

		/** @inheritdoc */
		protected function readDataBlock() {
			$this->initialize();
			$this->doOneIteration();
			return $this->document;
		}

		/** Сбрасывает значения полей, которые могли остаться от предыдущей итерации. */
		protected function resetState() {
			$this->position = 0;
			$this->document = null;
			$this->entity = null;
			$this->entityId = null;
			$this->group = null;
			$this->property = null;
			$this->importFile = null;
			$this->importId = null;
			$this->sourceName = null;
			$this->complete = false;
			$this->nameList = [];
			$this->titleList = [];
			$this->typeList = [];
		}

		/**
		 * Создает скелет документа в формате UMIDUMP.
		 * По умолчанию работает со страницами.
		 */
		protected function createDocumentSkeleton() {
			$doc = $this->document;
			$rootNode = $doc->createElement('umidump');
			$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');
			$doc->appendChild($rootNode);

			$versionNode = $doc->createAttribute('version');
			$versionNode->appendChild($doc->createTextNode(self::VERSION));
			$rootNode->appendChild($versionNode);

			$metaNode = $doc->createElement('meta');
			$rootNode->appendChild($metaNode);

			$siteNameNode = $doc->createElement('site-name');
			$siteNameNode->appendChild($doc->createCDATASection(def_module::parseTPLMacroses(macros_sitename())));
			$metaNode->appendChild($siteNameNode);

			$domain = Service::DomainDetector()->detect();
			$domainNode = $doc->createElement('domain');
			$domainNode->appendChild($doc->createCDATASection($domain->getHost()));
			$metaNode->appendChild($domainNode);

			$lang = Service::LanguageDetector()->detect();
			$langNode = $doc->createElement('lang');
			$langNode->appendChild($doc->createCDATASection($lang->getPrefix()));
			$metaNode->appendChild($langNode);

			$sourceNameNode = $doc->createElement('source-name');
			$sourceName = $this->sourceName ?: md5($domain->getId() . $lang->getId());
			$sourceNameNode->appendChild($doc->createCDATASection($sourceName));
			$metaNode->appendChild($sourceNameNode);

			$generateTimeNode = $doc->createElement('generate-time');
			$date = new umiDate(time());
			$timeStampNode = $doc->createElement('timestamp');
			$timeStampNode->appendChild($doc->createTextNode($date->getFormattedDate('U')));
			$generateTimeNode->appendChild($timeStampNode);

			$rfcNode = $doc->createElement('rfc');
			$rfcNode->appendChild($doc->createTextNode($date->getFormattedDate('r')));
			$generateTimeNode->appendChild($rfcNode);

			$utcNode = $doc->createElement('utc');
			$utcNode->appendChild($doc->createTextNode($date->getFormattedDate(DATE_ATOM)));
			$generateTimeNode->appendChild($utcNode);
			$metaNode->appendChild($generateTimeNode);

			$rootNode->appendChild($doc->createElement('pages'));
			$rootNode->appendChild($doc->createElement('hierarchy'));
			$rootNode->appendChild($doc->createElement('relations'));
			$rootNode->appendChild($doc->createElement('options'));
		}

		/**
		 * Определяет внутренний тип текущей сущности по внешнему типу и возвращает его идентификатор.
		 * При необходимости создает связь между внешним и внутренним типом.
		 * По умолчанию работает со страницами.
		 * @param string[] $cells CSV-ячейки
		 * @return int|bool
		 */
		protected function determineEntityTypeFromExternalType($cells) {
			$key = array_search('type-id', $this->nameList);
			if ($key === false) {
				return false;
			}

			$extTypeId = $cells[$key];
			if (!is_numeric($extTypeId)) {
				return false;
			}

			$sourceId = $this->getSourceId();
			$typeId = $this->relations->getNewTypeIdRelation($sourceId, $extTypeId);
			if ($typeId) {
				return $typeId;
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($extTypeId);
			if ($type instanceof iUmiObjectType) {
				$typeId = $extTypeId;
				$this->relations->setTypeIdRelation($sourceId, $extTypeId, $typeId);
			}

			return $typeId;
		}

		/**
		 * Возвращает идентификатор источника импорта
		 * @return bool|int
		 */
		protected function getSourceId() {
			return $this->relations->getSourceId($this->sourceName);
		}

		/**
		 * Преобразует данные текущей сущности в формат UMIDUMP
		 * @param string[] $cells CSV-ячейки
		 */
		protected function translateEntity($cells) {
			$parentId = $this->determinePageParent($cells);
			$typeId = $this->determineEntityType($cells, $parentId);
			$this->determineEntityId($cells);
			$this->determinePageRelation($parentId);
			$this->initializePage($typeId, $parentId);
			$this->translatePropertyList($cells, $typeId);
		}

		/**
		 * Определяет идентификатор текущей сущности и сохраняет его
		 * @param string[] $cells CSV-ячейки
		 */
		protected function determineEntityId($cells) {
			$key = array_search('id', $this->nameList);

			if ($key !== false && $cells[$key]) {
				$id = $cells[$key];
			} else {
				$id = md5(mt_rand());
			}

			$this->entityId = $id;
		}

		/**
		 * Преобразует свойства текущей сущности в формат UMIDUMP.
		 * @param array $propertyList список свойств (включая атрибуты и системные свойства)
		 * @param int $typeId идентификатор типа данных
		 */
		protected function translatePropertyList(array $propertyList, $typeId) {
			foreach ($propertyList as $key => $value) {
				$this->translateProperty($key, $value, $typeId);
			}
		}

		/**
		 * Определяет, является ли свойство дочерним узлом узла сущности
		 * @param string $name название свойства
		 * @return bool
		 */
		protected function isEntityChildNode($name) {
			return in_array($name, ['name', 'template-id']);
		}

		/**
		 * Определяет внутренний тип текущей сущности и возвращает его идентификатор.
		 * При необходимости создает связь между внешним и внутренним типом.
		 * Пол умолчанию работает со страницами.
		 * @param string[] $cells CSV-ячейки
		 * @param int $parentId идентификатор родителя страницы
		 * @return bool|int
		 */
		private function determineEntityType($cells, $parentId) {
			$typeId = $this->determineEntityTypeFromExternalType($cells);

			if (!$typeId) {
				$typeId = $this->pickAppropriatePageType($parentId);
			}

			$sourceId = $this->getSourceId();

			if ($typeId != $this->relations->getNewTypeIdRelation($sourceId, $typeId)) {
				$this->relations->setTypeIdRelation($sourceId, $typeId, $typeId);
			}

			return $typeId;
		}

		/** Подготавливает тип импорта к преобразованию данных */
		private function initialize() {
			$this->resetState();
			$this->importId = getRequest('param0');

			$file = new umiFile($this->file_path);

			if (!$this->sourceName) {
				$this->sourceName = $file->getFileName();
			}

			$this->relations = umiImportRelations::getInstance();
			$this->relations->addNewSource($this->sourceName);
			$this->initializeDocument();
		}

		/**
		 * Создает и инициализирует документ в формате UMIDUMP,
		 * в который будут преобразованы импортируемые данные.
		 */
		private function initializeDocument() {
			$this->document = new DOMDocument('1.0', 'utf-8');
			$this->document->formatOutput = XML_FORMAT_OUTPUT;
			$this->createDocumentSkeleton();
		}

		/** Выполняет одну итерацию преобразования данных */
		private function doOneIteration() {
			$this->openImportFile();
			$this->buildUmidump();
			$this->advanceOffset();
			$this->determineCompleteness();
			$this->closeImportFile();
		}

		/** Открывает файл импорта для чтения */
		private function openImportFile() {
			$this->importFile = fopen($this->file_path, 'r');
		}

		/**
		 * Преобразовывает очередную часть импортируемых данных
		 * в документ формата UMIDUMP.
		 */
		private function buildUmidump() {
			while (!$this->isIterationFinished()) {
				$row = $this->findNextRow();
				if (!$row) {
					continue;
				}

				$this->incrementPosition();
				$this->translateRow($row);
			}
		}

		/**
		 * Определяет статус завершенности одной итерации
		 * @return bool
		 */
		private function isIterationFinished() {
			if (feof($this->importFile)) {
				return true;
			}
			return $this->position - self::HEADER_COUNT >= $this->block_size;
		}

		/**
		 * Находит следующий ряд в CSV-файле и возвращает его
		 * @return string
		 */
		private function findNextRow() {
			$candidateRow = '';
			do {
				$candidateRow .= (string) fgets($this->importFile);
			} while (!feof($this->importFile) && !$this->isValidRow($candidateRow));
			return $candidateRow;
		}

		/**
		 * Определяет, правильно ли сформирован CSV-ряд.
		 * Ряд считается правильно сформированным, если в нем сбалансированы двойные кавычки.
		 * @param string $row ряд
		 * @return bool
		 */
		private function isValidRow($row) {
			return mb_substr_count($row, self::VALUE_LIMITER) % 2 === 0;
		}

		/** Обновляет порядковый номер текущего ряда в CSV-файле */
		private function incrementPosition() {
			$this->position += 1;
		}

		/**
		 * Преобразует один CSV-ряд в формат UMIDUMP
		 * @param string $row ряд
		 */
		private function translateRow($row) {
			$row = $this->normalizeRow($row);
			$cells = $this->getRowCells($row);

			if ($this->isCurrentRowAHeader()) {
				$this->updateHeader($cells);
			} else {
				$this->translateEntity($cells);
			}
		}

		/**
		 * Приводит CSV-ряд к стандартному виду
		 * @param string $row CSV-ряд
		 * @return string
		 */
		private function normalizeRow($row) {
			$row = html_entity_decode($row, ENT_QUOTES, $this->encoding);
			$row = (string) preg_replace(
				"/([^{$this->propertyDelimiter}])" . str_repeat(self::VALUE_LIMITER, 2) . '/s',
				"$1'*//*'",
				$row
			);
			preg_match_all('/' . self::VALUE_LIMITER . '(.*?)' . self::VALUE_LIMITER . '/s', $row, $matches);

			foreach ($matches[0] as $quotes) {
				$newQuotes = str_replace($this->propertyDelimiter, "'////'", $quotes);
				$row = str_replace($quotes, $newQuotes, $row);
			}

			$row = (string) preg_replace('/(.+)' . $this->propertyDelimiter . '$/s', '$1', trim($row));
			return $row;
		}

		/**
		 * Возвращает все ячейки одного CSV-ряда
		 * @param string $row CSV-ряд
		 * @return string[]
		 */
		private function getRowCells($row) {
			$cellList = explode($this->propertyDelimiter, $row);
			foreach ($cellList as &$cell) {
				$cell = $this->normalizeCell($cell);
			}
			return $cellList;
		}

		/**
		 * Приводит CSV-ячейку к стандартному виду
		 * @param string $cell CSV-ячейка
		 * @return string
		 */
		private function normalizeCell($cell) {
			$cell = mb_convert_encoding($cell, self::TARGET_ENCODING, $this->encoding);
			$cell = str_replace(["'////'", "'*//*'"], [$this->propertyDelimiter, self::VALUE_LIMITER], $cell);
			$cell = (string) preg_replace('/^' . self::VALUE_LIMITER . '(.*)' . self::VALUE_LIMITER . '$/s', '$1', $cell);
			return trim($cell);
		}

		/**
		 * Определяет, является ли текущий CSV-ряд заголовком
		 * @return bool
		 */
		private function isCurrentRowAHeader() {
			return $this->position <= self::HEADER_COUNT;
		}

		/**
		 * Обновляет данные одного из трех CSV-заголовков (названия, описания или типы полей)
		 * @param string[] $cells CSV-ячейки
		 */
		private function updateHeader(array $cells) {
			switch ($this->position) {
				case 1:
					$header = &$this->nameList;
					break;
				case 2:
					$header = &$this->titleList;
					break;
				case 3:
					$header = &$this->typeList;
					if ($this->offset > 0) {
						$this->skipOverTranslatedRows();
					}
					break;
				default:
					throw new LogicException("{$this->position} should be in the range 1..3");
			}

			foreach ($cells as $key => $value) {
				$header[$key] = $value;
			}
		}

		/** Пропускает уже преобразованные ряды CSV-файла */
		private function skipOverTranslatedRows() {
			fseek($this->importFile, $this->offset);
		}

		/**
		 * Определяет родителя текущей страницы и возвращает его идентификатор.
		 * @param string[] $cells CSV-ячейки
		 * @return string|int
		 */
		private function determinePageParent($cells) {
			$parentId = 0;
			$key = array_search('parent-id', $this->nameList);
			if ($key !== false) {
				$parentId = $cells[$key];
			}
			return $parentId;
		}

		/**
		 * Пытается подобрать наиболее подходящий тип для текущей страницы
		 * @param int $parentId идентификатор родителя страницы
		 * @return int|bool
		 */
		private function pickAppropriatePageType($parentId) {
			$typeId = false;

			if (!$parentId && $this->importId) {
				$pageList = umiObjectsCollection::getInstance()
					->getObject($this->importId)
					->getValue('elements');
				if (is_array($pageList) && umiCount($pageList)) {
					$parentId = $pageList[0]->getId();
				}
			}

			if ($parentId) {
				$typeId = umiHierarchy::getInstance()
					->getDominantTypeId($parentId);
			}

			if (!$typeId) {
				$typeId = umiObjectTypesCollection::getInstance()
					->getTypeIdByHierarchyTypeName('content');
			}

			return $typeId;
		}

		/**
		 * Определяет связь текущей страницы с ее родителем.
		 * В случае успеха создает запись в UMIDUMP.
		 * @param int $parentId идентификатор родителя страницы
		 */
		private function determinePageRelation($parentId) {
			if (!$this->entityId) {
				return;
			}

			$relationNode = $this->document->createElement('relation');
			$relationNode->setAttribute('id', $this->entityId);
			$relationNode->setAttribute('parent-id', $parentId);
			$hierarchy = $this->document->getElementsByTagName('hierarchy')->item(0);
			$hierarchy->appendChild($relationNode);
		}

		/**
		 * Создает узел для текущей страницы
		 * и заполняет значения атрибутов 'type-id' и 'parentId'.
		 * @param int $typeId идентификатор типа страницы
		 * @param int $parentId идентификатор родителя страницы
		 */
		private function initializePage($typeId, $parentId) {
			$this->entity = $this->document->createElement('page');
			$this->entity->setAttribute('type-id', $typeId);
			$this->entity->setAttribute('parentId', $parentId);
			$pageList = $this->document->getElementsByTagName('pages')->item(0);
			$pageList->appendChild($this->entity);

			$this->group = $this->document->createElement('group');
			$this->group->setAttribute('name', 'newGroup');

			$propertiesNode = $this->document->createElement('properties');
			$propertiesNode->appendChild($this->group);
			$this->entity->appendChild($propertiesNode);
		}

		/**
		 * Преобразует свойство текущей страницы в формат UMIDUMP.
		 * @param int $key индекс свойства
		 * @param string $value значение свойства
		 * @param int $typeId идентификатор типа данных
		 */
		private function translateProperty($key, $value, $typeId) {
			if ($this->shouldSkipProperty($key, $value)) {
				return;
			}

			$name = $this->nameList[$key];
			$value = $this->escapePropertyValue($value);

			if ($this->isEntityAttribute($name)) {
				$value = ($name === 'id') ? $this->entityId : $value;
				$this->addEntityAttribute($name, $value);
			} elseif ($this->isEntityChildNode($name)) {
				$this->addEntityChildNode($name, $value);
			} else {
				$this->addProperty($key, $value, $typeId);
			}
		}

		/**
		 * Определяет, нужно ли пропустить свойство
		 * @param int $key индекс свойства
		 * @param string $value значение свойства
		 * @return bool
		 */
		private function shouldSkipProperty($key, $value) {
			if (!isset($this->nameList[$key])) {
				return true;
			}
			if (!$value && !$this->resetVal && !isset($this->typeList[$key])) {
				return true;
			}
			return false;
		}

		/**
		 * Возвращает экранированное значение свойства
		 * @param string $value значение свойства
		 * @return string
		 */
		private function escapePropertyValue($value) {
			return strtr($value, [
				'&' => '&amp;',
				'<' => '&lt;',
				'>' => '&gt;',
			]);
		}

		/**
		 * Определяет, является ли свойство атрибутом сущности
		 * @param string $name название свойства
		 * @return bool
		 */
		private function isEntityAttribute($name) {
			return
				in_array($name, $this->simpleAttributeList) ||
				in_array($name, $this->specialAttributeList);
		}

		/**
		 * Добавляет простой атрибут в текущую сущности
		 * @param string $name название
		 * @param string $value значение
		 */
		private function addEntityAttribute($name, $value) {
			if (in_array($name, $this->simpleAttributeList)) {
				$this->entity->setAttribute($name, $value);
			}
		}

		/**
		 * Добавляет дочерний узел в текущую сущность
		 * @param string $name название
		 * @param string $value значение
		 */
		private function addEntityChildNode($name, $value) {
			if ($name === 'name') {
				$nameNode = $this->document->createElement('name', $value);
				$this->entity->appendChild($nameNode);
			} elseif ($name === 'template-id') {
				$this->addPageTemplate($value);
			}
		}

		/**
		 * Добавляет в текущую страницу шаблон (дочерний узел <template>)
		 * @param int $templateId идентификатор шаблона страницы
		 */
		private function addPageTemplate($templateId) {
			$template = templatesCollection::getInstance()
				->getTemplate($templateId);

			if ($template instanceof iTemplate) {
				$templateNode = $this->document->createElement('template', $template->getFilename());
				$templateNode->setAttribute('id', $templateId);
				$this->entity->appendChild($templateNode);
			}
		}

		/**
		 * Добавляет в группу текущей сущности новое свойство (узел <property>)
		 * @param int $key индекс свойства
		 * @param string $value значение свойства
		 * @param int $typeId идентификатор типа данных
		 */
		private function addProperty($key, $value, $typeId) {
			$this->property = $this->document->createElement('property');
			$name = $this->nameList[$key];
			$title = $this->titleList[$key];
			$type = $this->typeList[$key];
			$isMultiple = in_array($type, $this->getMultipleDataTypes());
			$type = ($type === 'multiple-relation') ? 'relation' : $type;

			$this->property->setAttribute('name', $name);
			$this->property->setAttribute('title', $title);
			$this->property->setAttribute('type', $type);
			if ($isMultiple) {
				$this->property->setAttribute('multiple', 'multiple');
			}
			$this->property->setAttribute('visible', '1');
			$isFilled = (bool) $value;
			$this->property->setAttribute('allow-runtime-add', (int) $isFilled);
			$objectType = umiObjectTypesCollection::getInstance()
				->getType($typeId);

			if (!$isFilled && $objectType instanceof iUmiObjectType && !$objectType->getFieldId($name)) {
				return;
			}

			$typeNode = $this->document->createElement('type');
			$typeNode->setAttribute('data-type', $type);
			$typeTitle = $this->getPropertyTypeTitle($name, $type, $isMultiple);
			$typeNode->setAttribute('name', $typeTitle);
			$this->property->appendChild($typeNode);

			$titleNode = $this->document->createElement('title', $title);
			$this->property->appendChild($titleNode);
			$this->group->appendChild($this->property);

			switch ($type) {
				case 'relation' : {
					$this->setRelationProperty($value, $isMultiple);
					break;
				}
				case 'tags' : {
					$this->setTagsProperty($value);
					break;
				}
				case 'symlink' : {
					$this->setSymlinkProperty($name, $value);
					break;
				}
				case 'multiple_image' : {
					$this->setMultipleImageProperty($value);
					break;
				}
				case 'optioned' : {
					$this->setOptionedProperty($name, $value);
					break;
				}
				case 'domain_id' :
				case 'domain_id_list' : {
					$this->setDomainProperty($value, $isMultiple);
					break;
				}
				default : {
					$this->setSimpleProperty($value);
				}
			}
		}

		/**
		 * Возвращает список типов данных полей, которые могут иметь несколько значений
		 * @return array
		 */
		private function getMultipleDataTypes() {
			return [
				'multiple-relation',
				'tags',
				'multiple_image',
				'symlink',
				'optioned',
				'domain_id_list',
				'offer_id_list'
			];
		}

		/**
		 * Возвращает название типа данных свойства
		 * @param string $name название свойства
		 * @param string $type строковой идентификатор типа данных свойства
		 * @param bool $isMultiple флаг множественного значения свойства
		 * @return string
		 * @throws coreException
		 */
		private function getPropertyTypeTitle($name, $type, $isMultiple) {
			$fieldType = umiFieldTypesCollection::getInstance()
				->getFieldTypeByDataType($type, $isMultiple);
			if (!$fieldType instanceof iUmiFieldType) {
				throw new coreException('Wrong datatype "' . $type . '" is given for property "' . $name . '"');
			}
			return $fieldType->getName();
		}

		/**
		 * Устанавливает значение текущему свойству с типом данных
		 * "Выпадающий список" или "Выпадающий список со множественным выбором"
		 * @param string $value значение свойства
		 * @param bool $isMultiple является ли поле многозначным
		 */
		private function setRelationProperty($value, $isMultiple) {
			$valueNode = $this->document->createElement('value');
			$this->property->appendChild($valueNode);

			$nameList = $this->extractMultiplePropertyValues($value);
			$nameList = $isMultiple ? $nameList : (array) getFirstValue($nameList);

			foreach ($nameList as $name) {
				$itemNode = $this->document->createElement('item');
				$itemNode->setAttribute('name', $name);
				$valueNode->appendChild($itemNode);
			}
		}

		/**
		 * Возвращает все значения свойства со множественными значениями
		 * @param string $value значение свойства
		 * @return array
		 */
		private function extractMultiplePropertyValues($value) {
			return explode(',', $value);
		}

		/**
		 * Устанавливает значение текущему свойству с типом данных "Теги"
		 * @param string $value значение свойства
		 */
		private function setTagsProperty($value) {
			$tagList = explode(',', $value);
			foreach ($tagList as $tag) {
				$valueNode = $this->document->createElement('value', trim($tag));
				$this->property->appendChild($valueNode);
			}

			$combinedNode = $this->document->createElement('combined', $value);
			$this->property->appendChild($combinedNode);
		}

		/**
		 * Устанавливает значение текущему свойству с типом данных
		 * "Ссылка на домен" или "Ссылка на список доменов"
		 * @param string $value значение свойства из csv файла
		 * @param bool $isMultiple является ли поле многозначным
		 */
		private function setDomainProperty($value, $isMultiple) {
			$valueNode = $this->document->createElement('value');
			$this->property->appendChild($valueNode);

			$domainIdList = $this->extractMultiplePropertyValues($value);
			$domainIdList = $isMultiple ? $domainIdList : (array) getFirstValue($domainIdList);

			foreach ($domainIdList as $domainId) {
				$domainNode = $this->document->createElement('domain');
				$domainNode->setAttribute('id', $domainId);
				$valueNode->appendChild($domainNode);
			}
		}

		/**
		 * Устанавливает значение текущему свойству с типом данных "Ссылка на дерево"
		 * @param string $name название поля
		 * @param string $value значение свойства
		 */
		private function setSymlinkProperty($name, $value) {
			if (!$this->entityId) {
				return;
			}

			$relationNode = $this->document->createElement('relation');
			$relations = $this->document->getElementsByTagName('relations')->item(0);
			$relations->appendChild($relationNode);

			$relationNode->setAttribute('page-id', $this->entityId);
			$relationNode->setAttribute('field-name', $name);

			$pageIdList = $this->extractMultiplePropertyValues($value);

			foreach ($pageIdList as $pageId) {
				$pageNode = $this->document->createElement('page');
				$pageNode->setAttribute('id', $pageId);
				$relationNode->appendChild($pageNode);
			}
		}

		/**
		 * Устанавливает значение текущему свойству с типом данных "Множественное изображение".
		 * Свойство состоит из трех частей: 'path', 'alt', 'ord'.
		 * @param string $value значение свойства
		 */
		private function setMultipleImageProperty($value) {
			$imageList = explode($this->valueDelimiter, $value);

			foreach ($imageList as $image) {
				$partList = explode($this->partDelimiter, $image);
				if (umiCount($partList) !== self::MULTIPLE_IMAGE_PART_COUNT) {
					continue;
				}

				$valueNode = $this->document->createElement('value');
				$this->property->appendChild($valueNode);

				foreach ($partList as $part) {
					list($partName, $partValue) = explode($this->subPartDelimiter, $part);
					$valueNode->setAttribute($partName, $partValue);
				}
			}
		}

		/**
		 * Устанавливает значение текущему свойству с типом данных "Составное".
		 *
		 * Свойство состоит из следующих частей:
		 * 'object-id', 'page-id', 'int', 'varchar', 'text', 'float'.
		 * @param string $name название поля
		 * @param string $value значение свойства
		 */
		private function setOptionedProperty($name, $value) {
			$optionsNode = $this->document->getElementsByTagName('options')->item(0);
			$entityNode = $this->document->createElement('entity');
			$entityNode->setAttribute('page-id', $this->entityId);
			$entityNode->setAttribute('field-name', $name);
			$optionsNode->appendChild($entityNode);

			$optionList = explode($this->valueDelimiter, $value);
			foreach ($optionList as $option) {
				$partList = explode($this->partDelimiter, $option);
				if (umiCount($partList) !== self::OPTIONED_PART_COUNT) {
					continue;
				}

				$optionNode = $this->document->createElement('option');
				$entityNode->appendChild($optionNode);

				foreach ($partList as $part) {
					list($partName, $partValue) = explode($this->subPartDelimiter, $part);
					$optionNode->setAttribute($partName, $partValue);
				}
			}
		}

		/**
		 * Устанавливает значение текущему свойству
		 * @param string $value значение свойства
		 */
		private function setSimpleProperty($value) {
			$valueNode = $this->document->createElement('value', $value);
			$this->property->appendChild($valueNode);
		}

		/** Обновляет отступ в файле импорта */
		private function advanceOffset() {
			$this->offset = ftell($this->importFile);
		}

		/** Определяет статус завершенности преобразования */
		private function determineCompleteness() {
			$this->complete = !$this->importFile || feof($this->importFile);
		}

		/** Закрывает файл импорта */
		private function closeImportFile() {
			fclose($this->importFile);
		}

		/** @inheritdoc */
		public function translate(DOMDocument $document) {
			return $document->saveXML();
		}
	}
