<?php

	/** @deprecated */
	interface iQuickCsvExporter {

		public function __construct(selector $sel);

		public function setResultsMode($resultsMode);

		public function exportToFile($filePath);

		public function setEncoding($encoding);

		public static function autoExport(selector $sel, $forceHierarchy = false, $encoding, $downloadFile);
	}

	;

	/** @deprecated */
	class quickCsvExporter implements iQuickCsvExporter {

		/** @var string $encoding наименование кодировки, в которой будут экспортированы данные */
		private $encoding = 'windows-1251';

		protected $sel;

		protected $filepath;

		protected $fileHandler;

		protected $resultsMode = "element";

		protected $fields = [];

		protected $foundFields = [];

		protected $objectTypes = [];

		/** @var array список поддерживаемых кодировок */
		protected static $supportedEncodings = ['utf-8', 'windows-1251', 'cp1251'];

		/** @const COLUMN_SEPARATOR разделитель полей */
		const COLUMN_SEPARATOR = ";";

		/** @const VALUE_LIMITER символ, обрамляющий значения полей */
		const VALUE_LIMITER = '"';

		/** @const SOURCE_ENCODING наименование исходной кодировки данных */
		const SOURCE_ENCODING = 'utf-8';

		/**
		 * Конструктор класса
		 * @param selector $sel выборка данных, которые будут экспортированы
		 */
		public function __construct(selector $sel) {
			ini_set('mbstring.substitute_character', "none");

			$innerSel = $sel;
			$innerSel->limit(0, 1000000);    //4 realloc: один миллион записей максимум
			$this->sel = $innerSel;
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
		 * Возвращает установленную кодировку экспортируемых данных
		 * @return string наименование кодировки
		 */
		public function getEncoding() {
			return $this->encoding;
		}

		public function setResultsMode($resultsMode) {
			if (in_array($resultsMode, ['object', 'element'])) {
				$this->resultsMode = $resultsMode;
				return true;
			}

			return false;
		}

		public function exportToFile($filepath) {
			if ($this->checkFilePath($filepath)) {
				touch($filepath);
				$this->filepath = realpath($filepath);
			} else {
				throw new coreException("Can't access store file \"{$filepath}\"");
			}
			$selectionResults = $this->getSelectionResults();

			$this->exportResults($selectionResults);
			return new umiFile($filepath);
		}

		public function setObjectTypeId($objectTypeId) {
			$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
			if ($objectType instanceof umiObjectType) {
				$this->objectTypes[] = $objectType;
			} else {
				throw new coreException("Object type #{$objectTypeId} doesn't exist");
			}
		}

		public function setUsedFields($fields) {
			if (is_array($fields)) {
				$this->fields = $fields;
				return true;
			}

			return false;
		}

		public static function autoExport(
			selector $sel,
			$forceHierarchy = false,
			$encoding = 'windows-1251',
			$downloadFile = true
		) {
			$csvExporter = new quickCsvExporter($sel);

			$defaultConfigEncoding = mainConfiguration::getInstance()->get('system', 'default-exchange-encoding');
			$defaultEncoding = 'windows-1251';

			$encoding = $encoding ?: $defaultConfigEncoding;

			try {
				$csvExporter->setEncoding($encoding);
			} catch (InvalidArgumentException $e) {
				$csvExporter->setEncoding($defaultEncoding);
			}

			if ($sel->mode == "pages" || $sel->hierarchy || $forceHierarchy) {
				$csvExporter->setResultsMode("element");
			} else {
				$csvExporter->setResultsMode("object");
			}

			$moduleName = cmsController::getInstance()->getCurrentModule();
			$config = mainConfiguration::getInstance();
			$filename = ($moduleName) ? $moduleName . "-" . date("Y-m-d_H.i.s") : "csv-export-" . uniqid();
			$exportFilePath = $config->includeParam('system.runtime-cache') . $filename . ".csv";

			$csvExporter->setUsedFields(getRequest('used-fields'));
			$csvExporter->exportToFile($exportFilePath);

			if ($downloadFile) {
				$file = new umiFile($exportFilePath);
				$file->download(true);
			} else {
				return @file_get_contents($exportFilePath);
			}
		}

		protected function checkFilePath($filepath) {
			if (is_file($filepath)) {
				return is_writable($filepath);
			}

			$dirname = dirname($filepath);
			if (is_dir($dirname)) {
				return is_writable($dirname);
			}

			return false;
		}

		protected function getSelectionResults() {
			return $this->sel->result;
		}

		protected function exportResults($results) {
			$data = [];
			if ($this->resultsMode == "object") {
				$objects = umiObjectsCollection::getInstance();
				foreach ($results as $object) {
					$data[] = $this->storeObjectData($object);
					$objects->unloadObject($object->id);
				}
			}

			if ($this->resultsMode == "element") {
				$hierarchy = umiHierarchy::getInstance();
				foreach ($results as $element) {
					$data[] = $this->storeElementData($element);
					$hierarchy->unloadElement($element->id);
				}
			}

			$this->openFile();
			$this->writeHeader();
			foreach ($data as $row) {
				foreach ($row as $fieldName => $column) {
					if (!is_numeric($fieldName) && !in_array($fieldName, $this->foundFields)) {
						unset($row[$fieldName]);
					}
				}
				$this->writeFileLine($row);
			}
			$this->closeFile();
		}

		protected function openFile() {
			$this->fileHandler = fopen($this->filepath, "w");
		}

		protected function writeHeader() {
			$data = [
				['string', 'Id'],
				['string', getLabel('label-name')]
			];

			$types = $this->objectTypes;
			$fieldsCollection = umiFieldsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();

			$typesList = [];
			foreach ($types as $objectType) {
				$objectTypeId = $objectType->getId();
				$typesList += $objectTypes->getChildTypeIds($objectTypeId);
				$typesList[] = $objectTypeId;
			}
			$fieldTitles = [];
			foreach ($this->fields as $fieldName) {
				if (!in_array($fieldName, $this->foundFields)) {
					continue;
				}

				$fieldTitle = $fieldName;
				foreach ($typesList as $objectTypeId) {
					$type = $objectTypes->getType($objectTypeId);
					if ($type instanceof iUmiObjectType) {
						if ($fieldId = $type->getFieldId($fieldName)) {
							$field = $fieldsCollection->getField($fieldId);
							if ($field instanceof iUmiField) {
								$fieldTitle = $field->getTitle();
								break;
							}
						}
					}
				}
				$fieldTitles[] = $fieldTitle;
			}

			if ($this->resultsMode == "element") {
				$data[] = ['string', getLabel('label-alt-name')];
				$data[] = ['string', getLabel('field-is_active')];
			}

			foreach ($fieldTitles as $fieldTitle) {
				$data[] = ['string', $fieldTitle];
			}

			$this->writeFileLine($data);
		}

		protected function writeFileLine($data) {
			$str = "";

			foreach ($data as $fieldName => $valueInfo) {
				if (is_array($valueInfo)) {
					$str .= $this->prepareCsvColumn($valueInfo[0], $valueInfo[1]);
				}
				$str .= self::COLUMN_SEPARATOR;
			}
			$str .= "\n";

			fwrite($this->fileHandler, $str);
		}

		protected function prepareCsvColumn($dataType, $value) {
			switch ($dataType) {
				case 'relation': {
					$value = $this->getRelationValue($value);
					break;
				}

				case 'domain_id_list':
				case 'tags': {
					$value = implode(", ", $value);
					break;
				}

				case 'date': {
					if ($value instanceof umiDate) {
						$value = $value->getFormattedDate("Y-m-d H:i");
					}
					break;
				}
			}

			$from = ['\n', self::VALUE_LIMITER];
			$to = ['\\n', str_repeat(self::VALUE_LIMITER, 2)];
			$value = str_replace($from, $to, $value);
			if (!is_numeric($value)) {
				$value = mb_convert_encoding($value, $this->encoding, self::SOURCE_ENCODING);
				$value = self::VALUE_LIMITER . $value . self::VALUE_LIMITER;
			}
			return $value;
		}

		protected function closeFile() {
			fclose($this->fileHandler);
		}

		protected function getObject($itemId) {
			$objects = umiObjectsCollection::getInstance();

			switch ($this->resultsMode) {
				case "object": {
					return $objects->getObject($itemId);
					break;
				}

				case "element": {
					$hierarchy = umiHierarchy::getInstance();
					$element = $hierarchy->getElement($itemId);
					if ($element instanceof umiHierarchyElement) {
						return $element;
					}

					return false;
					break;
				}

				default: {
					throw new coreException("Unknown results type \"{$this->resultsMode}\"");
				}
			}
		}

		protected function storeObjectData(umiObject $object) {
			$data = [
				["int", $object->getId()],
				["string", $object->getName()]
			];

			foreach ($this->fields as $fieldName) {
				$prop = $object->getPropByName($fieldName);
				if ($prop instanceof umiObjectProperty) {
					$dataType = $prop->getDataType();
					$value = $object->getValue($fieldName);
					$data[$fieldName] = [$dataType, $value];

					if (!in_array($fieldName, $this->foundFields)) {
						$this->foundFields[] = $fieldName;
					}
				} else {
					$data[$fieldName] = null;
				}
			}

			return $data;
		}

		protected function storeElementData(umiHierarchyElement $element) {
			$data = [
				["int", $element->getId()],
				["string", $element->getName()],
				["string", $element->getAltName()],
				["int", $element->getIsActive()]
			];

			$object = $element->getObject();
			foreach ($this->fields as $fieldName) {
				$prop = $object->getPropByName($fieldName);
				if ($prop instanceof umiObjectProperty) {
					$dataType = $prop->getDataType();
					$value = $object->getValue($fieldName);
					$data[$fieldName] = [$dataType, $value];

					if (!in_array($fieldName, $this->foundFields)) {
						$this->foundFields[] = $fieldName;
					}
				} else {
					$data[$fieldName] = null;
				}
			}

			return $data;
		}

		protected function getRelationValue($value) {
			$objects = umiObjectsCollection::getInstance();
			if (is_array($value)) {
				$tmp = [];
				foreach ($value as $objectId) {
					$object = $objects->getObject($objectId);
					if ($object instanceof umiObject) {
						$tmp[] = $object->getName();
					}
				}
				return implode(", ", $tmp);
			}

			if (is_numeric($value)) {
				$object = $objects->getObject($value);
				if ($object instanceof umiObject) {
					return $object->getName();
				}

				return false;
			}

			return false;
		}
	}

?>
