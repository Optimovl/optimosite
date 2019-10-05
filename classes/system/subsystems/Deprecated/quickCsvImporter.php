<?php

	use UmiCms\Service;

	/** @deprecated */
	interface iQuickCsvImporter {

		public function __construct(umiFile $csvFile);

		public function importAsElements($hierarchyTypeId = false, $parentElementId = false);

		public function importAsObjects($objectTypeId = false);

		public function setEncoding($encoding);

		public static function autoImport(selector $sel, $forceHierarchy = false);
	}

	;

	/** @deprecated */
	class quickCsvImporter implements iQuickCsvImporter {

		/** @var string $encoding наименование кодировки, в которой находятся импортируемые данные */
		private $encoding = 'windows-1251';

		/** @var string $delimiter разделитель полей */
		private $delimiter = ';';

		protected $csvFile;

		protected $fileHandler;

		protected $mode = 'object';

		protected $fields = false;

		protected $forceObjectCreation = false;

		/** @var array $supportedEncodings список поддерживаемых кодировок */
		protected static $supportedEncodings = ['utf-8', 'windows-1251', 'cp1251'];

		/** @var array $supportedFieldTypes список типов полей, которые могут быть импортированы */
		protected static $supportedFieldTypes = [
			'relation',
			'tags',
			'int',
			'float',
			'price',
			'date',
			'file',
			'img_file',
			'swf_file',
			'string',
			'text',
			'wysiwyg',
			'password',
			'counter',
			'color',
			'link_to_object_type',
			'boolean',
			'domain_id',
			'domain_id_list'
		];

		public $allowNewItemsCreation = true;

		public $forceHierarchy = false;

		public $errors = [];

		/** @const VALUE_LIMITER символ, обрамляющий значения полей */
		const VALUE_LIMITER = '"';

		/** @const TARGET_ENCODING наименование кодировки, в которую будут преобразованы данные */
		const TARGET_ENCODING = 'utf-8';

		/**
		 * Конструктор класса
		 * @param umiFile $csvFile импортируемый CSV-файл
		 * @throws coreException
		 */
		public function __construct(umiFile $csvFile) {
			if ($csvFile->getIsBroken()) {
				throw new coreException('CSV file doesn\'t exists: "' . $csvFile->getFilePath() . '"');
			}

			$this->forceObjectCreation = umiObjectProperty::$USE_FORCE_OBJECTS_CREATION;
			if (getRequest('ignore-id')) {
				umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;
			}

			ini_set('mbstring.substitute_character', "none");
			$this->csvFile = $csvFile;
			$this->openFile();
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

		public function __destruct() {
			umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = $this->forceObjectCreation;
			$this->closeFile();
		}

		/**
		 * Запускает импорт страниц
		 * @param int|bool $hierarchyTypeId идентификатор иерархического типа импортируемых страниц
		 * @param int|bool $parentElementId идентификатор родителя для импортируемых страниц
		 */
		public function importAsElements($hierarchyTypeId = false, $parentElementId = false) {
			$this->mode = 'element';

			$objectTypeId = false;
			$objectTypes = umiObjectTypesCollection::getInstance();
			if ($parentElementId) {
				$objectTypeId = umiHierarchy::getInstance()->getDominantTypeId($parentElementId);
			}
			if (!$objectTypeId) {
				$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeId($hierarchyTypeId);
			}

			$objectType = $objectTypes->getType($objectTypeId);
			if (!$hierarchyTypeId) {
				$hierarchyTypeId = $objectType->getHierarchyTypeId();
			}

			$this->importElements($objectType, $hierarchyTypeId, $parentElementId);
		}

		/**
		 * Запускает импорт объектов
		 * @param umiObjectType|bool $objectType объектный тип
		 */
		public function importAsObjects($objectType = false) {
			if (!$objectType instanceof iUmiObjectType) {
				return self::releaseBuffer("alert('Cant detect object type id');");
			}

			$this->importObjects($objectType);
		}

		/**
		 * Импортирует csv файл, запускается через табличный контрол модуля.
		 * Результат возвращается в буффер.
		 * @param selector $sel выборка объектов или страниц модуля, которые отображаются в таблице
		 * @param bool $forceHierarchy игнорировать иерархию
		 * @param string $encoding кодировка csv файла
		 * @return bool
		 */
		public static function autoImport(selector $sel, $forceHierarchy = false, $encoding = 'windows-1251') {
			if (!isset($_FILES['csv-file'])) {
				self::releaseBuffer("alert('File is not posted');");
				return false;
			}

			$fileInfo = getArrayKey($_FILES, 'csv-file');
			$name = getArrayKey($fileInfo, 'name');
			$tempPath = getArrayKey($fileInfo, 'tmp_name');
			$error = getArrayKey($fileInfo, 'error');
			$size = getArrayKey($fileInfo, 'size');

			if ($error) {
				self::releaseBuffer("alert('Failed to upload file');");
				return false;
			}

			$config = mainConfiguration::getInstance();
			$file = umiFile::manualUpload($name, $tempPath, $size, $config->includeParam('system.runtime-cache'));

			if (!($file instanceof iUmiFile) || $file->getIsBroken()) {
				self::releaseBuffer("alert('Upload file is broken');");
				return false;
			}

			$import = new quickCsvImporter($file);
			$import->setEncoding($encoding);
			$import->forceHierarchy = $forceHierarchy;

			$objectTypesIds = [];
			$hierarchyTypesIds = [];

			/* @var selectorType $type */
			foreach ($sel->types as $type) {
				if (null !== $type->objectTypeIds) {
					foreach ($type->objectTypeIds as $id) {
						$objectTypesIds[] = $id;
					}
				}
				if (null !== $type->hierarchyTypeIds) {
					foreach ($type->hierarchyTypeIds as $id) {
						$hierarchyTypesIds[] = $id;
					}
				}
			}

			$objectTypesIds = array_unique($objectTypesIds);
			$hierarchyTypesIds = array_unique($hierarchyTypesIds);

			$isPagesMode = true;

			if (!$forceHierarchy && umiCount($sel->hierarchy) == 0) {
				$isPagesMode = false;
			}

			$objectType = null;
			$hierarchyTypeId = null;
			$parentElementId = null;

			try {
				if (!$isPagesMode) {
					$objectType = self::getObjectType($objectTypesIds, $hierarchyTypesIds);

					if (!$objectType instanceof umiObjectType) {
						throw new publicAdminException("alert('Cant detect object type id');");
					}
				} else {
					if (is_array($sel->hierarchy) && umiCount($sel->hierarchy) > 0) {
						$parentElementId = $sel->hierarchy[0]->elementId;
					}

					$hierarchyTypeId = self::getHierarchyTypeId($hierarchyTypesIds, $parentElementId, $objectTypesIds);

					if (!is_numeric($hierarchyTypeId)) {
						throw new publicAdminException("alert('Cant detect hierarchy type id');");
					}
				}
			} catch (publicAdminException $e) {
				$file->delete();
				self::releaseBuffer($e->getMessage());
				return false;
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/html');
			$buffer->push("<script type='text/javascript'>\n");

			if (!$isPagesMode) {
				$import->importAsObjects($objectType);
			} else {
				$import->importAsElements($hierarchyTypeId, $parentElementId);
			}

			$buffer->push("//Upload completed\n");
			$file->delete();
			$buffer->push("window.parent.csvQuickImportCallback();\n</script>\n");
			$buffer->end();
			return true;
		}

		/**
		 * Возвращает идентификатор иерархического типа данных для импорта страниц
		 * или false, если тип не удалось вычислить
		 * @param array $hierarchyTypesIds массив с идентификаторами иерархических типов данных
		 * @param int|bool $parentElementId идентификатор родительской страницы, если таковая есть
		 * @param array $objectTypesIds массив с идентификаторами объектных типов
		 * @return int|bool
		 */
		private static function getHierarchyTypeId(array $hierarchyTypesIds, $parentElementId, $objectTypesIds) {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$commentsHierarchyType = $umiHierarchyTypes->getTypeByName('comments', 'comment');
			$commentsHierarchyTypeId = $commentsHierarchyType->getId();

			if (is_numeric($parentElementId)) {
				$dominantObjectTypeId = umiHierarchy::getInstance()->getDominantTypeId($parentElementId);
				if ($dominantObjectTypeId) {
					$objectType = $umiObjectTypes->getType($dominantObjectTypeId);
					if ($dominantHierarchyTypeId = $objectType->getHierarchyTypeId()) {
						return $dominantHierarchyTypeId;
					}
				}
			}

			$hierarchyTypesCount = umiCount($hierarchyTypesIds);
			$objectTypesCount = umiCount($objectTypesIds);

			switch (true) {
				case $hierarchyTypesCount == 0 && $objectTypesCount == 0: {
					return false;
				}
				case $hierarchyTypesCount == 0 && $objectTypesCount > 0: {

					$needIgnoreComments = (cmsController::getInstance()->getCurrentModule() == 'comments') ? false : true;

					foreach ($objectTypesIds as $objectTypeId) {
						/* @var iUmiObjectType|umiEntinty $objectType */
						$objectType = $umiObjectTypes->getType($objectTypeId);

						if ($needIgnoreComments) {
							if ($objectType->getHierarchyTypeId() !== $commentsHierarchyTypeId) {
								$hierarchyTypesIds[] = $objectType->getHierarchyTypeId();
							}
						} else {
							$hierarchyTypesIds[] = $objectType->getHierarchyTypeId();
						}
					}

					return array_pop($hierarchyTypesIds);
				}
				case $hierarchyTypesCount > 1: {
					foreach ($hierarchyTypesIds as $key => $value) {
						if ($value == $commentsHierarchyTypeId) {
							unset($hierarchyTypesIds[$key]);
						}
					}
				}
			}

			return array_shift($hierarchyTypesIds);
		}

		/**
		 * Возвращает объектный тип данных для импорта объектов
		 * или false, если тип не удалось вычислить
		 * @param array $objectTypesIds массив с идентификаторами объектных типов данных
		 * @param array $hierarchyTypesIds массив с идентификаторами иерархических типов данных
		 * @return umiObjectType|bool
		 */
		private static function getObjectType(array $objectTypesIds, array $hierarchyTypesIds) {
			$objectTypeId = null;
			$umiObjectTypes = umiObjectTypesCollection::getInstance();

			switch (true) {
				case umiCount($objectTypesIds) > 0: {
					$objectTypeId = array_shift($objectTypesIds);
					break;
				}
				case umiCount($hierarchyTypesIds) > 0: {
					$hierarchyTypeId = array_shift($hierarchyTypesIds);
					$objectTypeId = $umiObjectTypes->getTypeIdByHierarchyTypeId($hierarchyTypeId);
					break;
				}
			}

			return $umiObjectTypes->getType($objectTypeId);
		}

		/**
		 * Выводит буфер с сообщением
		 * @param string $message сообщение
		 */
		private static function releaseBuffer($message) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/html');
			$buffer->push("<script type='text/javascript'>\n");
			$buffer->push("$message\n");
			$buffer->push("window.parent.csvQuickImportCallback();\n</script>\n");
			$buffer->end();
		}

		/**
		 * Импортирует страницы и помещает отчет о результате в буфер
		 * @param umiObjectType $objectType объектный тип импортируемых страниц
		 * @param int $hierarchyTypeId идентификатор иерархического типа импортируемых страниц
		 * @param int|bool $parentElementId идентификатор родительской страницы импортируемых страниц
		 */
		protected function importElements(umiObjectType $objectType, $hierarchyTypeId, $parentElementId) {
			$buffer = Service::Response()
				->getCurrentBuffer();

			$headers = $this->readNextRow();
			$this->fields = umiCount($headers);
			$hierarchy = umiHierarchy::getInstance();
			$permissions = permissionsCollection::getInstance();

			def_module::$noRedirectOnPanic = true;
			$errorsList = [];
			$headers = $this->analyzeHeaders($objectType, $headers);

			$buffer->push(str_repeat(" ", 1024));
			$buffer->send();

			while ($cols = $this->readNextRow()) {

				$cols = $this->analyzeColumns($headers, $cols);

				if (!isset($cols['id'])) {
					continue;
				}

				$elementId = $cols['id'];
				$name = (isset($cols['name'])) ? $cols['name'] : '';

				if ($elementId) {
					$eventId = 'systemModifyElement';
					$requestKey = $elementId;
				} else {
					if ($parentElementId && !$this->forceHierarchy) {
						$eventId = 'systemCreateElement';
						$requestKey = 'new';

						if (!$cols['alt-name']) {
							$cols['alt-name'] = $name;
						}

						$elementId = $hierarchy->addElement(
							$parentElementId,
							$hierarchyTypeId,
							$name,
							$cols['alt-name'],
							$objectType->getId()
						);
						$buffer->push('//Create new page "' . $name . '" of type ' . $hierarchyTypeId . ', id #' .
							$elementId . "\n");
						$cols['id'] = $elementId;

						$permissions->setDefaultPermissions($elementId);
					} else {
						continue;
					}
				}

				$element = $hierarchy->getElement($elementId, ($elementId == 'new'));
				if ($element instanceof umiHierarchyElement == false) {
					$errorsList[] = [
						'id' => $elementId,
						'name' => $name,
						'error' => getLabel('csv-error-not-found')
					];
					continue;
				}

				if ($requestKey != 'new') {
					if ($element->getTypeId() != $hierarchyTypeId) {
						$errorsList[] = [
							'id' => $elementId,
							'name' => $name,
							'error' => getLabel('csv-error-wrong-type')
						];
						continue;
					}
				}

				if ($name !== '') {
					$_REQUEST['name'] = $name;
				}
				if (isset($cols['alt-name'])) {
					$_REQUEST['alt_name'] = $cols['alt-name'];
				}
				if (isset($cols['is-active'])) {
					$_REQUEST['is_active'] = $cols['is-active'];
				}
				foreach ($cols as $fieldName => $value) {
					$_REQUEST['data'][$requestKey][$fieldName] = $value;
				}

				try {
					$event = new umiEventPoint($eventId);
					$event->addRef('element', $element);
					$event->setMode('before');
					$event->call();
				} catch (errorPanicException $e) {
					$errorsList[] = [
						'id' => $requestKey,
						'error' => $e->getMessage(),
						'name' => $name
					];

					if ($requestKey == 'new') {
						$hierarchy->delElement($element->getId());
					}
					continue;
				}

				if ($requestKey == 'new') {
					$isVisible = ($element->getModule() == 'content');
					$element->setIsVisible($isVisible);
				}

				foreach ($cols as $fieldName => $value) {
					switch ($fieldName) {
						case 'id': {
							continue;
						}
						case 'name': {
							$element->setName($value);
							break;
						}
						case 'alt-name': {
							$element->setAltName($cols['alt-name']);
							break;
						}
						case 'is-active': {
							$element->setIsActive($cols['is-active']);
							break;
						}
						default: {
							try {
								$this->modifyProperty($element, $fieldName, $value);
							} catch (fieldRestrictionException $e) {
								$e->unregister();
								$errorsList[] = [
									'id' => $requestKey,
									'error' => $e->getMessage(),
									'name' => $cols[$fieldName]
								];
							}
							break;
						}
					}
				}

				try {
					$event->setMode('after');
					$event->call();
				} catch (errorPanicException $e) {
					$errorsList[] = [
						'id' => $requestKey,
						'error' => $e->getMessage(),
						'name' => $name
					];

					if ($requestKey == 'new') {
						$hierarchy->delElement($elementId);
					}
					continue;
				}

				unset($_REQUEST['name']);
				unset($_REQUEST['alt_name']);
				unset($_REQUEST['is_active']);
				$_REQUEST['data'][$requestKey];

				$element->commit();
				unset($element);
				$this->clearMemoryCache();
			}

			def_module::$noRedirectOnPanic = true;

			if (umiCount($errorsList)) {
				$buffer->push('var err = \'' . addslashes(getLabel('csv-error-import-list')) . "\\n';\n");
				foreach ($errorsList as $errorInfo) {
					if ($errorInfo['id'] == 'new') {
						$buffer->push(
							'err += \'' .
							addslashes($errorInfo['name'] . ' (' . getLabel('csv-new-item') . ') - ' .
								$errorInfo['error']) .
							"\\n';\n"
						);
					} else {
						$buffer->push(
							'err += \'' .
							addslashes($errorInfo['name'] . ' (#' . $errorInfo['id'] . ') - ' . $errorInfo['error']) .
							"\\n';\n"
						);
					}
				}
				$buffer->push("alert(err);\n\n");
			}
		}

		/**
		 * Импортирует объекты и помещает отчет о результате в буфер
		 * @param umiObjectType $objectType объектный тип
		 */
		protected function importObjects(umiObjectType $objectType) {
			$headers = $this->readNextRow();
			$this->fields = umiCount($headers);
			$objects = umiObjectsCollection::getInstance();
			$types = umiObjectTypesCollection::getInstance();

			$subscriber = false;
			if ($objectType->getId() == $types->getTypeIdByGUID('dispatches-subscriber')) {
				$subscriber = true;
			}

			$buffer = Service::Response()
				->getCurrentBuffer();

			def_module::$noRedirectOnPanic = true;
			$headers = $this->analyzeHeaders($objectType, $headers);

			$errorsList = [];

			$buffer->push(str_repeat(" ", 1024));
			$buffer->send();

			while ($cols = $this->readNextRow()) {

				$cols = $this->analyzeColumns($headers, $cols);

				if (!isset($cols['id'])) {
					continue;
				}

				$objectId = $cols['id'];

				$name = (isset($cols['name'])) ? $cols['name'] : '';

				if ($subscriber && !$objectId) {
					$sel = new selector('objects');
					$sel->types('object-type')->name('dispatches', 'subscriber');
					$sel->where('name')->equals($name);
					$sel->option('return')->value('id');
					$result = $sel->first;
					if (is_array($result) && umiCount($result)) {
						$objectId = $result['id'];
					}
				}

				if ($objectId) {
					$requestDataKey = $objectId;
					$eventId = 'systemModifyObject';
				} else {
					$eventId = 'systemCreateObject';

					$objectId = $objects->addObject('Temporary object name', $objectType->getId());
					$buffer->push(
						'//Create new object "' . $name . '" of type ' . $objectType->getId() . ', id #' . $objectId . "\n"
					);

					$requestDataKey = 'new';
				}

				$object = $objects->getObject($objectId);
				if ($object instanceof umiObject == false) {
					$errorsList[] = [
						'id' => $objectId,
						'error' => getLabel('csv-error-not-found'),
						'name' => $name
					];
					continue;
				}

				if ($object->getTypeId() != $objectType->getId()) {
					$errorsList[] = [
						'id' => $objectId,
						'error' => getLabel('csv-error-wrong-type'),
						'name' => $name
					];
					continue;
				}

				$_REQUEST['data'][$requestDataKey] = [];
				foreach ($headers as $i => $propName) {
					if ($i == 'id') {
						continue;
					}
					if ($i == 'name' && isset($cols[$i])) {
						$_REQUEST['name'] = $cols[$i];
					}
					if (isset($cols[$i])) {
						$_REQUEST['data'][$requestDataKey][$i] = $cols[$i];
					}
				}
				/*
				 * TODO
				 */
				if ($objectType->getMethod() == 'user') {
					$_REQUEST['data'][$requestDataKey]['password'][0] = 'dummyPassword';
				}

				try {
					$event = new umiEventPoint($eventId);
					$event->addRef("object", $object);
					$event->setMode("before");
					$event->call();
				} catch (errorPanicException $e) {
					$errorsList[] = [
						'id' => $requestDataKey,
						'error' => $e->getMessage(),
						'name' => $name
					];

					if ($requestDataKey == 'new') {
						$objects->delObject($objectId);
					}
					continue 1;
				}

				foreach ($cols as $fieldName => $value) {
					switch ($fieldName) {
						case 'id': {
							continue;
						}
						case 'name': {
							$object->setName($value);
							break;
						}
						default: {
							try {
								$this->modifyProperty($object, $fieldName, $value);
							} catch (fieldRestrictionException $e) {
								$e->unregister();
								$errorsList[] = [
									'id' => $requestDataKey,
									'error' => $e->getMessage(),
									'name' => $cols[$fieldName]
								];
							}
							break;
						}
					}
				}
				$object->commit();

				try {
					$event->setMode('after');
					$event->call();
				} catch (errorPanicException $e) {
					$errorsList[] = [
						'id' => $requestDataKey,
						'error' => $e->getMessage(),
						'name' => $name
					];

					if ($requestDataKey == 'new') {
						$objects->delObject($objectId);
					}
					continue 1;
				}

				unset($_REQUEST['data'][$requestDataKey]);
				unset($object);
				$this->clearMemoryCache();
			}

			def_module::$noRedirectOnPanic = false;

			if (umiCount($errorsList)) {
				$buffer->push('var err = \'' . addslashes(getLabel('csv-error-import-list')) . "\\n';\n");
				foreach ($errorsList as $errorInfo) {
					if ($errorInfo['id'] == 'new') {
						$buffer->push(
							'err += \'' .
							addslashes($errorInfo['name'] . ' (' . getLabel('csv-new-item') . ') - ' .
								$errorInfo['error']) .
							"\\n';\n"
						);
					} else {
						$buffer->push(
							'err += \'' .
							addslashes($errorInfo['name'] . ' (#' . $errorInfo['id'] . ') - ' . $errorInfo['error']) .
							"\\n';\n"
						);
					}
				}
				$buffer->push("alert(err);\n\n");
			}
		}

		/** Очищает кеш в памяти */
		private function clearMemoryCache() {
			umiHierarchy::getInstance()->clearCache();
			umiPropertiesHelper::getInstance()->clearCache();
			umiLinksHelper::getInstance()->clearCache();
			umiTypesHelper::getInstance()->clearCache();
			umiObjectsCollection::getInstance()->clearCache();
			umiObjectTypesCollection::getInstance()->clearCache();
			permissionsCollection::getInstance()->clearCache();
			umiFieldsCollection::getInstance()->clearCache();
			umiObjectProperty::setCachedPropData([]);
		}

		protected function readNextRow() {
			$result = "";
			$handler = $this->getFileHandler();
			if (feof($handler)) {
				return false;
			}

			$string = fgets($handler);
			if (!$string) {
				return $this->readNextRow();
			}

			if (mb_substr_count($string, self::VALUE_LIMITER) % 2 != 0) {
				$isRecord = false;
				while (!feof($handler) && !$isRecord) {
					$string .= fgets($handler);
					if (mb_substr_count($string, self::VALUE_LIMITER) % 2 == 0) {
						$isRecord = true;
					}
				}
			}
			$string = html_entity_decode($string, ENT_QUOTES, $this->encoding);
			$row =
				preg_replace("/([^{$this->delimiter}])" . str_repeat(self::VALUE_LIMITER, 2) . "/s", "$1'*//*'", $string);
			preg_match_all("/" . self::VALUE_LIMITER . "(.*?)" . self::VALUE_LIMITER . "/s", $row, $matches);

			foreach ($matches[0] as $quotes) {
				$newQuotes = str_replace($this->delimiter, "'////'", $quotes);
				$row = str_replace($quotes, $newQuotes, $row);
			}

			$row = preg_replace("/(.+)" . $this->delimiter . "$/s", "$1", trim($row));
			$row = explode($this->delimiter, $row);

			foreach ($row as &$cell) {
				$cell = mb_convert_encoding($cell, self::TARGET_ENCODING, $this->encoding);
				$cell = str_replace("'////'", $this->delimiter, $cell);
				$cell = str_replace("'*//*'", self::VALUE_LIMITER, $cell);
				$cell = preg_replace("/^" . self::VALUE_LIMITER . "(.*)" . self::VALUE_LIMITER . "$/s", "$1", $cell);
				$cell = trim($cell);
			}
			return $row;
		}

		/**
		 * Splits csv string into array
		 * @deprecated
		 * @param mixed $stringRow
		 * @return array
		 */
		protected function splitRow($stringRow) {
			$cols = [];
			if (mb_substr($stringRow, -1) != $this->delimiter) {
				$stringRow .= $this->delimiter;
			}
			$len = mb_strlen($stringRow);

			$char = '';
			$prevChar = '';
			$colValue = '';
			for ($i = 0; $i < $len; $i++) {
				$char = mb_substr($stringRow, $i, 1);

				switch ($char) {
					case ';': {
						if ($prevChar != '\\') {
							$cols[] = $colValue;
							$colValue = '';
							break;
						}

						$colValue = mb_substr($colValue, 0, mb_strlen($colValue) - 1) . $this->delimiter;
						break;
					}

					case self::VALUE_LIMITER: {
						if (mb_substr($stringRow, $i, 2) != str_repeat(self::VALUE_LIMITER, 2)) {
							break;
						}

						if (mb_substr($stringRow, $i, 3) != str_repeat(self::VALUE_LIMITER, 2) . $this->delimiter) {
							$colValue = $colValue . self::VALUE_LIMITER;
						}
						$i++;
						break;
					}

					default: {
						$colValue .= $char;
					}
				}
				$prevChar = $char;
			}

			return $cols;
		}

		protected function modifyProperty(umiEntinty $object, $fieldName, $stringValue) {
			if ($object instanceof umiObject) {
				$objectTypeId = $object->getTypeId();
			} else {
				$objectTypeId = $object->getObject()->getTypeId();
			}

			$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
			if ($objectType instanceof umiObjectType == false) {
				throw new coreException("Object type #{$objectTypeId} not found");
			}

			$fieldId = $objectType->getFieldId($fieldName);
			$field = umiFieldsCollection::getInstance()->getField($fieldId);

			if ($field instanceof umiField) {
				if (in_array($field->getDataType(), self::$supportedFieldTypes)) {
					$value = $this->prepareValue($field, $stringValue);
					return $object->setValue($field->getName(), $value);
				}
			} else {
				return false;
			}
		}

		protected function getFieldId(umiObjectType $objectType, $propName) {
			/* @var iUmiField $field */
			foreach ($objectType->getAllFields() as $field) {
				if ($field->getTitle() == $propName || $field->getName() == $propName) {
					return $field->getId();
				}

				if ($this->getFieldAlias($propName)) {
					if ($this->getFieldAlias($field->getTitle()) == $this->getFieldAlias($propName)) {
						return $field->getId();
					}
				}
			}
			return false;
		}

		protected function getFieldAlias($propName) {
			$propName = ulangStream::getI18n($propName);
			$arr = ['photo' => ['field-photo', 'field-image', 'field-izobrazhenie', 'field-photo-s']];

			if (mb_substr($propName, 0, 6) == 'i18n::') {
				$propName = mb_substr($propName, 6);
			}

			foreach ($arr as $i => $v) {
				if (in_array($propName, $v)) {
					return $i;
				}
			}

			return false;
		}

		protected function prepareValue(umiField $field, $stringValue) {
			$fieldType = $field->getFieldType();
			switch ($fieldType->getDataType()) {
				case 'relation': {
					$result = preg_split('/, ?/', $stringValue);
					foreach ($result as $i => $val) {
						if ($val) {
							$i18n = ulangStream::getI18n($val);
							$result[$i] = $i18n ?: $val;
						}
					}
					return $result;
				}

				case 'domain_id_list':
				case 'tags': {
					return preg_split('/, ?/', $stringValue);
				}

				case 'int': {
					return (int) $stringValue;
				}

				case 'float':
				case 'price': {
					$stringValue = str_replace(",", ".", $stringValue);
					return (float) $stringValue;
					break;
				}

				case 'date': {
					if ($stringValue) {
						return umiDate::getTimeStamp($stringValue);
					}

					return false;
				}

				case 'file':
				case 'img_file': {
					if (preg_match('/[а-яА-Я ]/', $stringValue)) {
						$oldStringValue = iconv('UTF-8', 'CP1251//IGNORE', $stringValue);
						$file1 = CURRENT_WORKING_DIR . $stringValue;
						$file2 = CURRENT_WORKING_DIR . $oldStringValue;
						$file = false;
						if (file_exists($file1)) {
							$file = $stringValue;
						} elseif (file_exists($file2)) {
							$file = $oldStringValue;
						}

						if ($file) {
							$stringValue = str_replace('\\', '/', $stringValue);
							$paths = explode('/', $stringValue);
							// Обрабатываем пути к файлам
							$newPaths = [];
							if (umiCount($paths) > 1) {
								// Запоминаем имя файла
								$fileName = $paths[umiCount($paths) - 1];
								unset($paths[umiCount($paths) - 1]);
								// Обрабатываем каждую часть пути на случай, если там тоже русские буквы
								foreach ($paths as $part) {
									if (preg_match('/[а-яА-Я]/', $part)) {
										$newPaths[] = translit::convert($part);
									} else {
										$newPaths[] = $part;
									}
								}
							} else {
								// Было только имя файла
								$fileName = $paths[0];
							}

							// Обрабатываем имя файла
							$partsFileName = explode('.', $fileName);
							// Последяя часть - расширение
							$ext = $partsFileName[umiCount($partsFileName) - 1];
							unset($partsFileName[umiCount($partsFileName) - 1]);

							$mainPartName = implode('.', $partsFileName);

							if (preg_match('/[а-яА-Я]/', $mainPartName)) {
								$mainPartName = translit::convert($mainPartName);
							}

							$mainPartName .= '.' . $ext;

							$newPaths[] = $mainPartName;

							$stringValue = implode('/', $newPaths);

							if (!(file_exists(dirname(CURRENT_WORKING_DIR . $stringValue)) &&
								is_dir(dirname(CURRENT_WORKING_DIR . $stringValue)))) {
								mkdir(dirname(CURRENT_WORKING_DIR . $stringValue), 0777, true);
							}

							rename(CURRENT_WORKING_DIR . $file, CURRENT_WORKING_DIR . $stringValue);
						}
					}

					if ($stringValue && mb_substr($stringValue, 0, 1) == '/') {
						$stringValue = '.' . $stringValue;
					}

					return $stringValue;
				}

				case 'swf_file': {
					if ($stringValue && mb_substr($stringValue, 0, 1) == '/') {
						$stringValue = '.' . $stringValue;
					}
					return $stringValue;
				}

				default: {
					return $stringValue;
				}
			}
		}

		protected function openFile() {
			$this->fileHandler = fopen($this->csvFile->getFilePath(), 'r');
		}

		protected function getFileHandler() {
			return $this->fileHandler;
		}

		protected function closeFile() {
			if ($handler = $this->getFileHandler()) {
				fclose($handler);
			}
			if ($this->csvFile instanceof umiFile) {
				$this->csvFile->delete();
			}
		}

		protected function analyzeColumns($headers, $cols) {
			$result = [];

			$fieldNames = array_keys($headers);

			for ($i = 0; $i < umiCount($fieldNames); $i++) {
				$result[$fieldNames[$i]] = isset($cols[$i]) ? $cols[$i] : null;
			}
			return $result;
		}

		protected function analyzeHeaders(umiObjectType $objectType, $headers) {
			$result = [];
			$fieldCollection = umiFieldsCollection::getInstance();

			$i = 0;
			foreach ($headers as $title) {
				switch (mb_strtolower($title)) {
					case 'id': {
						$result['id'] = $title;
						break;
					}
					case mb_strtolower(getLabel('label-name')): {
						$result['name'] = $title;
						break;
					}
					case mb_strtolower(getLabel('label-alt-name')): {
						if ($this->mode == 'element') {
							$result['alt-name'] = $title;
						}
						break;
					}
					case mb_strtolower(getLabel('field-is_active')): {
						if ($this->mode == 'element') {
							$result['is-active'] = $title;
						}
						break;
					}
					default: {
						$fieldId = $this->getFieldId($objectType, $title);
						$field = $fieldCollection->getField($fieldId);
						if ($field instanceof umiField) {
							$result[$field->getName()] = $title;
						} else {
							$result['unkonwn-field-' . (++$i)] = $title;
						}
					}
				}
			}
			return $result;
		}
	}
