<?php

	use UmiCms\Service;

	/**
	 * Конвертер структуры базы данных
	 * Умеет:
	 * 1) Сохранять текущую структуру
	 * 2) Конвертировать бд в новую структуру
	 *
	 * TODO рефакторинг, убрать дублирование кода
	 */
	class dbSchemeConverter {

		/** @var int ограничение на количество строк таблицы, обрабатываемых за одну итерацию по умолчанию */
		const DEFAULT_ITERATION_LIMIT = 10000;

		/** @var IConnection $connection подключение к конвертируемой бд */
		private $connection;

		/**
		 * @var string $destinationFile путь до файла, в котором содержится xml дамп имеющейся структуры,
		 * либо куда требуется сохранить дамп текущей структуры
		 */
		private $destinationFile = false;

		/** @var string $sourceFile путь до файла, в котором содержится xml дамп желаемой структуры бд */
		private $sourceFile = false;

		/** @var bool|string $mode режим работы (save|restore) */
		private $mode = false;

		/** @var bool $completed завершена ли работа */
		private $completed = false;

		/** @var array $state состояние работы */
		private $state = [];

		/** @var bool $inParts включена ли работа по частям */
		private $inParts = false;

		/** @var int $limit ограничение на количество строк таблицы, обрабатываемых за одну итерацию */
		private $limit;

		/** @var array $converterLog журнал работы */
		private $converterLog = [];

		/** @var string $stateDirectoryPath путь до директории, в которой хранится состояние */
		private $stateDirectoryPath;

		/**
		 * Конструктор
		 * @param IConnection $connection подключение к бд
		 */
		public function __construct(IConnection $connection) {
			$this->connection = $connection;
		}

		/**
		 * Устанавливает путь до директории, в которую класс сохраняется свое состояние
		 * @param string $path путь до директории
		 * @throws publicAdminException
		 */
		public function setStateDirectoryPath($path) {
			if (!is_string($path) || !is_dir($path)) {
				throw new publicAdminException('Incorrect state directory path given');
			}

			$this->stateDirectoryPath = $path;
		}

		/**
		 * Устанавливает путь до файла, в котором содержится xml дамп имеющейся структуры,
		 * либо куда требуется сохранить дамп текущей структуры
		 * @param string $path путь до файла
		 */
		public function setDestinationFile($path) {
			$this->destinationFile = $path;
		}

		/**
		 * Устанавливает путь до файла, в котором содержится xml дамп желаемой структуры бд
		 * @param string $path путь до файла
		 */
		public function setSourceFile($path) {
			$this->sourceFile = $path;
		}

		/**
		 * Устанавливает режим работы
		 * @param bool|string $mode режим работы
		 * @param bool $inParts включена ли работа по частям
		 * @param int $limit ограничение на количество строк таблицы, обрабатываемых за одну итерацию
		 */
		public function setMode($mode = false, $inParts = false, $limit = 1000) {
			$this->mode = $mode;
			$this->inParts = $inParts;
			$this->setIterationLimit((int) $limit);
		}

		/**
		 * Устанавливает ограничение на количество строк таблицы, обрабатываемых за одну итерацию
		 * @param int $limit
		 */
		private function setIterationLimit($limit) {
			$umiConfig = mainConfiguration::getInstance();

			$configLimit = (int) $umiConfig->get('updates', 'db-scheme-converter-iteration-limit');
			if ($configLimit > 0) {
				$this->limit = $configLimit;
				return;
			}

			if ($limit > 0) {
				$this->limit = $limit;
				return;
			}

			$this->limit = self::DEFAULT_ITERATION_LIMIT;
		}

		/**
		 * Запускает работу
		 * @return bool
		 * @throws coreException
		 */
		public function run() {
			$this->converterLog = [];
			switch ($this->mode) {
				case 'save': {
					if (!$this->destinationFile) {
						throw new coreException('Please set destination file name');
					}
					$this->saveXmlToFile();
					return true;
				}
				case 'restore': {
					$this->getState();
					$this->restoreDataBase();
					$this->saveState();
					return $this->completed;
				}
				default: {
					throw new coreException("Don't know what to do. Please set any appropriate mode.");
				}
			}
		}

		/**
		 * Возвращает журнал работы
		 * @return array
		 */
		public function getConverterLog() {
			return $this->converterLog;
		}

		/**
		 * Помещает сообщение в лог, в cli режиме распечатывает сообщение
		 * @param string $message сообщение
		 */
		protected function writeLog($message) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) {
				Service::Response()
					->getCliBuffer()
					->push($message . PHP_EOL);
			} else {
				$this->converterLog[] = $message;
			}
		}

		/**
		 * Помещает сообщение об ошибке в лог, в cli режиме распечатывает сообщение
		 * @param string $error сообщение об ошибке
		 */
		protected function reportError($error) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) {
				Service::Response()
					->getCliBuffer()
					->push($error . PHP_EOL);
			} else {
				$this->converterLog[] = $error;
			}
		}

		/**
		 * Возвращает состояние работы (для работы по частям)
		 * @throws coreException
		 */
		protected function getState() {
			if (!$this->inParts) {
				return;
			}

			if (!$this->sourceFile || !$this->destinationFile) {
				throw new coreException('Please set destination and source file name');
			}

			$stateDirectoryPath = $this->getStateDirectoryPath();

			if (file_exists($stateDirectoryPath . '/updates/' . md5($this->destinationFile))) {
				$this->state = unserialize(
					file_get_contents($stateDirectoryPath . '/updates/' . md5($this->destinationFile))
				);
				return;
			}

			$docNew = new DOMDocument();
			if (!$docNew->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			$this->state = [];
			$newTableList = $docNew->getElementsByTagName('table');

			/** @var DOMElement $newTable */
			foreach ($newTableList as $newTable) {
				$this->state[$newTable->getAttribute('name')] = [];
			}
		}

		/** Сохраняет состояние работы (для работы по частям) */
		protected function saveState() {
			if (!$this->inParts) {
				return;
			}
			$stateDirectoryPath = $this->getStateDirectoryPath();

			if ($this->completed && file_exists($stateDirectoryPath . '/updates/' . md5($this->destinationFile))) {
				$stateWasDeleted = unlink($stateDirectoryPath . '/updates/' . md5($this->destinationFile));

				if (!$stateWasDeleted) {
					throw new coreException('Cannot delete state');
				}
			} else {
				$stateWasSaved = file_put_contents(
					$stateDirectoryPath . '/updates/' . md5($this->destinationFile),
					serialize($this->state)
				);

				if (!$stateWasSaved) {
					throw new coreException('Cannot save state');
				}
			}
		}

		/**
		 * Преобразовывает структуру бд в xml документ
		 * и возвращает результат преобразований
		 * @return string
		 */
		private function dumpToXml() {
			$dom = new DOMDocument('1.0', 'utf-8');
			/** @noinspection PhpUndefinedConstantInspection */
			$dom->formatOutput = XML_FORMAT_OUTPUT;

			$tablesElement = $dom->createElement('tables', '');
			$dom->appendChild($tablesElement);

			$tables = $this->connection->queryResult('SHOW TABLES');
			foreach ($tables as $table) {
				$result = $this->connection->queryResult("SHOW CREATE TABLE `{$table[0]}`");
				foreach ($result as $row) {

					$tableElement = $dom->createElement('table', '');
					$tablesElement->appendChild($tableElement);

					$nameAttribute = $dom->createAttribute('name');
					$tableElement->appendChild($nameAttribute);
					$nameText = $dom->createTextNode("{$table[0]}");
					$nameAttribute->appendChild($nameText);

					if (preg_match('/DEFAULT\s+CHARSET=([^\s]+)/is', $row[1], $charset)) {
						if (isset($charset[1])) {
							$charsetAttribute = $dom->createAttribute('charset');
							$tableElement->appendChild($charsetAttribute);
							$charsetText = $dom->createTextNode("{$charset[1]}");
							$charsetAttribute->appendChild($charsetText);
						}
					}

					if (preg_match('/ENGINE=([^\s]+)/is', $row[1], $engine)) {
						if (isset($engine[1])) {
							$engineAttribute = $dom->createAttribute('engine');
							$tableElement->appendChild($engineAttribute);
							$engineText = $dom->createTextNode("{$engine[1]}");
							$engineAttribute->appendChild($engineText);
						}
					}

					$row[1] = preg_replace("/\)\s+ENGINE=(.*)/is", '', $row[1]);
					$row[1] = preg_replace(
						"/CREATE\s+TABLE\s+`(.*)`\s+\(/i",
						'',
						$row[1]
					); // оставляем только внутренности таблицы

					preg_match("/(CONSTRAINT\s.*)/is", $row[1], $constraints); // находит constraints

					if (isset($constraints[1])) {

						$constraintsElement = $dom->createElement('constraints', '');
						$tableElement->appendChild($constraintsElement);

						$constraints = explode(',', $constraints[1]);
						foreach ($constraints as $constraint) {

							$constraintElement = $dom->createElement('constraint', '');
							$constraintsElement->appendChild($constraintElement);

							preg_match("/`([a-zA-Z0-9?_\s+])+`/", $constraint, $matches);
							preg_match('/`(.*)`/', $matches[0], $name);
							$name[1] = str_replace('?', ' ', $name[1]);
							preg_match("/FOREIGN\s+KEY\s+\(`([a-zA-Z0-9_])+`\)/i", $constraint, $matches);
							preg_match('/`(.*)`/', $matches[0], $foreignKey);
							preg_match("/REFERENCES\s+`(.*)`\s+\(/i", $constraint, $refTable);
							preg_match("/REFERENCES\s+`.*`\s+\(`(.*)`/i", $constraint, $refField);

							$nameAttribute = $dom->createAttribute('name');
							$constraintElement->appendChild($nameAttribute);
							$nameText = $dom->createTextNode("{$name[1]}");
							$nameAttribute->appendChild($nameText);

							$fieldAttribute = $dom->createAttribute('field');
							$constraintElement->appendChild($fieldAttribute);
							$fieldText = $dom->createTextNode("{$foreignKey[1]}");
							$fieldAttribute->appendChild($fieldText);

							$refTableAttribute = $dom->createAttribute('ref-table');
							$constraintElement->appendChild($refTableAttribute);
							$refTableText = $dom->createTextNode("{$refTable[1]}");
							$refTableAttribute->appendChild($refTableText);

							$refFieldAttribute = $dom->createAttribute('ref-field');
							$constraintElement->appendChild($refFieldAttribute);
							$refFieldText = $dom->createTextNode("{$refField[1]}");
							$refFieldAttribute->appendChild($refFieldText);

							preg_match("/ON\s+DELETE\s+(CASCADE|SET\s+NULL)/i", $constraint, $onDelete);
							if (isset($onDelete[1])) {
								$deleteAttribute = $dom->createAttribute('on-delete');
								$constraintElement->appendChild($deleteAttribute);
								$deleteText = $dom->createTextNode("{$onDelete[1]}");
								$deleteAttribute->appendChild($deleteText);
							}

							preg_match("/ON\s+UPDATE\s+(CASCADE|SET\s+NULL)/i", $constraint, $onUpdate);
							if (isset($onUpdate[1])) {
								$updateAttribute = $dom->createAttribute('on-update');
								$constraintElement->appendChild($updateAttribute);
								$updateText = $dom->createTextNode("{$onUpdate[1]}");
								$updateAttribute->appendChild($updateText);
							}
						}
						$row[1] = preg_replace("/(CONSTRAINT\s.*)/is", '', $row[1]); // отсекает constraints
					}

					preg_match("/PRIMARY\s+KEY\s+\(`(.*)`\)/i", $row[1], $primaryKey); // находит primary key
					if (isset($primaryKey[1])) {
						$row[1] = preg_replace("/PRIMARY\s+KEY\s+\(`.*`\)/i", '', $row[1]); // отсекает primary key
					}

					preg_match("/UNIQUE\s+KEY\s+(.*)\)/i", $row[1], $uniqueKey); // находит unique key
					if (isset($uniqueKey[1])) {
						$row[1] = preg_replace("/UNIQUE\s+KEY\s+.*\)/i", '', $row[1]); // отсекает unique key
					}

					preg_match("/(KEY\s.*)/is", $row[1], $keys); // находит key

					if (isset($primaryKey[1]) || isset($keys[1]) || isset($uniqueKey[1])) {
						$indexesElement = $dom->createElement('indexes', '');
						$tableElement->appendChild($indexesElement);
					}

					if (isset($primaryKey[1])) {

						$indexElement = $dom->createElement('index', '');
						$indexesElement->appendChild($indexElement);

						$typeAttribute = $dom->createAttribute('type');
						$indexElement->appendChild($typeAttribute);
						$typeText = $dom->createTextNode('PRIMARY');
						$typeAttribute->appendChild($typeText);

						$fieldElement = $dom->createElement('field', "{$primaryKey[1]}");
						$indexElement->appendChild($fieldElement);
					}

					if (isset($uniqueKey[1])) {

						$indexElement = $dom->createElement('index', '');
						$indexesElement->appendChild($indexElement);

						$typeAttribute = $dom->createAttribute('type');
						$indexElement->appendChild($typeAttribute);
						$typeText = $dom->createTextNode('UNIQUE');
						$typeAttribute->appendChild($typeText);

						preg_match("/`(.*)`\s+\(/", $uniqueKey[1], $name);

						$nameAttribute = $dom->createAttribute('name');
						$indexElement->appendChild($nameAttribute);
						$nameText = $dom->createTextNode("{$name[1]}");
						$nameAttribute->appendChild($nameText);

						preg_match("/\((.*)/", $uniqueKey[1], $matches);
						$fields = explode(',', $matches[1]);
						foreach ($fields as $field) {
							preg_match("/\((\d+)\)/", $field, $length);
							preg_match('/`(.*)`/', $field, $fieldName);
							$fieldElement = $dom->createElement('field', "{$fieldName[1]}");
							$indexElement->appendChild($fieldElement);
							if (isset($length[1])) {
								$lengthAttribute = $dom->createAttribute('length');
								$fieldElement->appendChild($lengthAttribute);
								$lengthText = $dom->createTextNode("{$length[1]}");
								$lengthAttribute->appendChild($lengthText);
							}
						}
					}

					if (isset($keys[1])) {

						$keys[1] = preg_replace("/`\),/", '` ),', $keys[1]);
						$keys[1] = preg_replace("/\)\),/", ') ),', $keys[1]);
						$keys = preg_split("/\s+\),/", $keys[1]);
						foreach ($keys as $key) {
							$key = trim($key);
							if ($key !== '') {
								preg_match("/`(.*)`\s+\(/", $key, $name);

								$indexElement = $dom->createElement('index', '');
								$indexesElement->appendChild($indexElement);

								$nameAttribute = $dom->createAttribute('name');
								$indexElement->appendChild($nameAttribute);
								$nameText = $dom->createTextNode("{$name[1]}");
								$nameAttribute->appendChild($nameText);

								preg_match("/\((.*)/", $key, $matches);
								$fields = explode(',', $matches[1]);
								foreach ($fields as $field) {
									preg_match("/\((\d+)\)/", $field, $length);
									preg_match('/`(.*)`/', $field, $fieldName);
									$fieldElement = $dom->createElement('field', "{$fieldName[1]}");
									$indexElement->appendChild($fieldElement);

									if (isset($length[1])) {
										$lengthAttribute = $dom->createAttribute('length');
										$fieldElement->appendChild($lengthAttribute);
										$lengthText = $dom->createTextNode("{$length[1]}");
										$lengthAttribute->appendChild($lengthText);
									}
								}
							}
						}
						$row[1] = preg_replace("/(KEY\s.*)/is", '', $row[1]); // отсекает key
					}

					$tableFields = preg_split('/\n/', $row[1]);

					$fieldsElement = $dom->createElement('fields', '');
					$tableElement->appendChild($fieldsElement);

					foreach ($tableFields as $field) {
						$field = preg_replace('/,$/s', '', $field);
						$field = trim($field);
						if (mb_strlen($field) > 1) {
							$fieldElement = $dom->createElement('field');
							$fieldsElement->appendChild($fieldElement);

							preg_match("/COMMENT\s+'(.*)'/i", $field, $comment);
							if (isset($comment[1])) {
								$commentAttribute = $dom->createAttribute('comment');
								$fieldElement->appendChild($commentAttribute);
								$commentText = $dom->createTextNode("{$comment[1]}");
								$commentAttribute->appendChild($commentText);
								$field = preg_replace("/COMMENT\s+'.*'/i", '', $field);
							}

							preg_match(
								"/\s+(BINARY|UNSIGNED\s+ZEROFILL|UNSIGNED|on\s+update\s+CURRENT_TIMESTAMP)/i",
								$field,
								$attribute
							);
							if (isset($attribute[1])) {
								$attributeAttribute = $dom->createAttribute('attributes');
								$fieldElement->appendChild($attributeAttribute);
								$attributeText = $dom->createTextNode("{$attribute[1]}");
								$attributeAttribute->appendChild($attributeText);
								$field = preg_replace(
									"/\s+BINARY|\s+UNSIGNED\s+ZEROFILL|\s+UNSIGNED|\s+on\s+update\s+CURRENT_TIMESTAMP/i",
									'',
									$field
								);
							}

							preg_match("/\s+(NOT\s+NULL)/i", $field, $notNull);
							if (isset($notNull[1])) {
								$nullAttribute = $dom->createAttribute('not-null');
								$fieldElement->appendChild($nullAttribute);
								$nullText = $dom->createTextNode('1');
								$nullAttribute->appendChild($nullText);
								$field = preg_replace("/\s+NOT\s+NULL/i", '', $field);
							}

							preg_match("/\s+(AUTO_INCREMENT)/i", $field, $increment);
							if (isset($increment[1])) {
								$incAttribute = $dom->createAttribute('increment');
								$fieldElement->appendChild($incAttribute);
								$incText = $dom->createTextNode('1');
								$incAttribute->appendChild($incText);
								$field = preg_replace("/\s+AUTO_INCREMENT/i", '', $field);
							}

							preg_match("/\s+DEFAULT\s+(.*)/i", $field, $default);
							if (isset($default[1])) {
								preg_match("/'(.*)'/", $default[1], $match);
								if (isset($match[1])) {
									$default[1] = $match[1];
								}
								$defaultAttribute = $dom->createAttribute('default');
								$fieldElement->appendChild($defaultAttribute);
								$defaultText = $dom->createTextNode("{$default[1]}");
								$defaultAttribute->appendChild($defaultText);
								$field = preg_replace("/\s+DEFAULT\s+.*/i", '', $field);
							}

							preg_match('/`(.*)`/', $field, $name);
							if (isset($name[1])) {
								$nameAttribute = $dom->createAttribute('name');
								$fieldElement->appendChild($nameAttribute);
								$nameText = $dom->createTextNode("{$name[1]}");
								$nameAttribute->appendChild($nameText);
								$field = preg_replace('/`.*`/', '', $field);
							}

							preg_match("/\((\d+|\d+,\d+)\)/", $field, $size);
							if (isset($size[1])) {
								$sizeAttribute = $dom->createAttribute('size');
								$fieldElement->appendChild($sizeAttribute);
								$sizeText = $dom->createTextNode("{$size[1]}");
								$sizeAttribute->appendChild($sizeText);
								$field = preg_replace("/\({$size[1]}\)/", '', $field);
							}

							preg_match("/\((.*)\)/", $field, $size);
							if (isset($size[1])) {

								$options = preg_replace("/'/", '', $size[1]);
								$options = explode(',', $options);
								foreach ($options as $option) {
									$optionElement = $dom->createElement('option', $option);
									$fieldElement->appendChild($optionElement);
								}
								$field = preg_replace("/\((.*)\)/", '', $field);
							}

							$field = preg_replace('/,/', '', $field);
							$field = trim($field);

							if ($field) {
								$typeAttribute = $dom->createAttribute('type');
								$fieldElement->appendChild($typeAttribute);
								$typeText = $dom->createTextNode("{$field}");
								$typeAttribute->appendChild($typeText);
							}
						}
					}
				}
			}

			return $dom->saveXML($dom->documentElement, DOM_LOAD_OPTIONS);
		}

		/**
		 * Сохраняет xml документ с дампом структуры бд в файл
		 * @throws coreException
		 */
		public function saveXmlToFile() {
			if (!file_put_contents($this->destinationFile, $this->dumpToXml())) {
				throw new coreException('Cannot create new xml file ' . $this->destinationFile);
			}
		}

		/**
		 * Возвращает sql запрос на создание таблицы на основе xml дампа желаемой структуры бд
		 * @param string $tableName имя таблицы
		 * @return string
		 * @throws coreException
		 */
		public function restoreShowCreateTable($tableName) {
			$doc = new DOMDocument();
			if (!$doc->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			$xpath = new DOMXPath($doc);

			/** @var DOMElement $table */
			$table = $xpath->query("//table[@name='{$tableName}']")->item(0);
			$createTable = "CREATE TABLE `{$tableName}` (\n";

			$fieldsRoot = $table->getElementsByTagName('fields');
			if (is_object($fieldsRoot->item(0))) {
				$fieldList = $fieldsRoot->item(0)->getElementsByTagName('field');

				/** @var DOMElement $field */
				foreach ($fieldList as $field) {
					$createTable .= "\t";

					$fieldName = $field->getAttribute('name');
					if ($fieldName) {
						$createTable .= "`{$fieldName}`";
					}

					$fieldType = $field->getAttribute('type');
					if ($fieldType) {
						$createTable .= " {$fieldType}";
					}

					$fieldSize = $field->getAttribute('size');
					if ($fieldSize) {
						$createTable .= "({$fieldSize})";
					}

					if ($field->getElementsByTagName('option')->length) {
						$options = $field->getElementsByTagName('option');
						$optionsValue = '';
						$i = 1;

						foreach ($options as $option) {
							if ($i == $options->length) {
								$optionsValue .= "'{$option->nodeValue}'";
							} else {
								$optionsValue .= "'{$option->nodeValue}',";
							}
							$i++;
						}
						$createTable .= "({$optionsValue})";
					}

					$fieldAttributes = $field->getAttribute('attributes');
					if ($fieldAttributes) {
						$createTable .= " {$fieldAttributes}";
					}

					$fieldNull = $field->getAttribute('not-null');
					if ($fieldNull) {
						$createTable .= ' NOT NULL';
					}

					if ($field->hasAttribute('default')) {
						$fieldDefault = $field->getAttribute('default');
						if ($fieldDefault != 'NULL') {
							$fieldDefault = "'{$fieldDefault}'";
						}
						$createTable .= " DEFAULT {$fieldDefault}";
					}

					if ($field->getAttribute('increment')) {
						$createTable .= ' AUTO_INCREMENT';
					}

					$fieldComment = $field->getAttribute('comment');
					if ($fieldComment) {
						$createTable .= " COMMENT '{$fieldComment}'";
					}

					$createTable .= ",\n";
				}
			}

			$indexesRoot = $table->getElementsByTagName('indexes');
			if (is_object($indexesRoot->item(0))) {
				$indexList = $indexesRoot->item(0)->getElementsByTagName('index');

				/** @var DOMElement $index */
				foreach ($indexList as $index) {
					$createTable .= "\t";

					$indexType = $index->getAttribute('type');
					if ($indexType) {
						$createTable .= "{$indexType} ";
					}

					$createTable .= 'KEY';
					$indexName = $index->getAttribute('name');
					if ($indexName) {
						$createTable .= " `{$indexName}`";
					}

					if ($index->getElementsByTagName('field')->length) {
						$fieldList = $index->getElementsByTagName('field');
						$fieldsValue = '';
						$i = 1;

						foreach ($fieldList as $field) {
							$fieldValue = "`{$field->nodeValue}`";
							$fieldLength = $field->getAttribute('length');
							if ($fieldLength) {
								$fieldValue .= "({$fieldLength})";
							}

							if ($i == $fieldList->length) {
								$fieldsValue .= $fieldValue;
							} else {
								$fieldsValue .= "{$fieldValue},";
							}
							$i++;
						}

						$createTable .= " ({$fieldsValue})";
					}

					$createTable .= ",\n";
				}
			}

			$constraintsRoot = $table->getElementsByTagName('constraints');
			if (is_object($constraintsRoot->item(0))) {
				$constraintList = $constraintsRoot->item(0)->getElementsByTagName('constraint');

				/** @var DOMElement $constraint */
				foreach ($constraintList as $constraint) {
					$createTable .= "\tCONSTRAINT";

					$constraintName = $constraint->getAttribute('name');
					if ($constraintName) {
						$createTable .= " `{$constraintName}`";
					}

					$constraintField = $constraint->getAttribute('field');
					if ($constraintField) {
						$createTable .= " FOREIGN KEY (`{$constraintField}`)";
					}

					$constraintRefTable = $constraint->getAttribute('ref-table');
					if ($constraintRefTable) {
						$createTable .= " REFERENCES `{$constraintRefTable}`";
					}

					$constraintRefField = $constraint->getAttribute('ref-field');
					if ($constraintRefField) {
						$createTable .= " (`{$constraintRefField}`)";
					}

					$constraintOnDelete = $constraint->getAttribute('on-delete');
					if ($constraintOnDelete) {
						$createTable .= " ON DELETE {$constraintOnDelete}";
					}

					$constraintOnUpdate = $constraint->getAttribute('on-update');
					if ($constraintOnUpdate) {
						$createTable .= " ON UPDATE {$constraintOnUpdate}";
					}

					$createTable .= ",\n";
				}
			}

			$createTable = preg_replace('/,$/s', '', $createTable);

			$tableEngine = $table->getAttribute('engine');
			if ($tableEngine) {
				$createTable .= ") ENGINE={$tableEngine}";
			}

			$tableCharset = $table->getAttribute('charset');
			if ($tableCharset) {
				$createTable .= " DEFAULT CHARSET={$tableCharset}";
			}

			$createTable .= "\n";
			return $createTable;
		}

		/**
		 * Создает таблицу из xml дампа новой структуры бд
		 * @param string $tableName имя таблицы
		 * @throws coreException
		 */
		private function createDataBaseTable($tableName) {
			$createTable = $this->restoreShowCreateTable($tableName);
			$success = true;
			try {
				$this->connection->queryResult($createTable);
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) {
				$this->writeLog("Data base table {$tableName} has been created");
			}
		}

		/**
		 * Меняет у таблицы подсистему хранения данных
		 * @param string $tableName имя таблицы
		 * @param string $tableEngine имя подсистемы
		 */
		private function changeTableEngine($tableName, $tableEngine) {
			$success = true;
			try {
				$this->connection->queryResult("ALTER TABLE `{$tableName}` ENGINE={$tableEngine}");
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) {
				$this->writeLog("Data base table ({$tableName}) engine has been changed");
			}
		}

		/**
		 * Меняет кодировку таблицы по умолчанию
		 * @param string $tableName имя таблицы
		 * @param string $tableCharset имя кодировки
		 * @throws coreException
		 */
		private function changeTableCharset($tableName, $tableCharset) {
			$success = true;
			try {
				$this->connection->queryResult("ALTER TABLE `{$tableName}` DEFAULT CHARACTER SET {$tableCharset}");
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) {
				$this->writeLog("Data base table ({$tableName}) character set has been changed");
			}

			$doc = new DOMDocument();
			if (!$doc->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			$xpath = new DOMXPath($doc);
			$table = $xpath->query("//table[@name='{$tableName}']")->item(0);
			$fieldsRoot = $table->getElementsByTagName('fields');

			if (is_object($fieldsRoot->item(0))) {
				$fieldList = $fieldsRoot->item(0)->getElementsByTagName('field');

				/** @var DOMElement $field */
				foreach ($fieldList as $field) {
					$fieldName = $field->getAttribute('name');
					if ($fieldName) {
						$this->createTableField($tableName, $fieldName, 'modify');
					}
				}
			}
		}

		/**
		 * Создает или изменяет поле таблицы
		 * @param string $tableName имя таблицы
		 * @param string $fieldName имя поля
		 * @param string $param режим работы (add|modify)
		 * @throws coreException
		 */
		private function createTableField($tableName, $fieldName, $param) {
			$doc = new DOMDocument();
			if (!$doc->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			$xpath = new DOMXPath($doc);
			if (!$field = $xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}']")->item(0)) {
				throw new coreException("Cannot change {$tableName}.{$fieldName}");
			}

			if ($param == 'add') {
				$createField = "ALTER TABLE `{$tableName}` ADD ";
			} else {
				$createField = "ALTER TABLE `{$tableName}` MODIFY ";
			}

			$createField .= "`{$fieldName}`";

			$fieldType = $field->getAttribute('type');
			if ($fieldType) {
				$createField .= " {$fieldType}";
			}

			$fieldSize = $field->getAttribute('size');
			if ($fieldSize) {
				$createField .= "({$fieldSize})";
			}

			if ($field->getElementsByTagName('option')->length) {
				$options = $field->getElementsByTagName('option');
				$optionsValue = '';
				$i = 1;
				foreach ($options as $option) {
					if ($i == $options->length) {
						$optionsValue .= "'{$option->nodeValue}'";
					} else {
						$optionsValue .= "'{$option->nodeValue}',";
					}
					$i++;
				}
				$createField .= "({$optionsValue})";
			}

			$fieldAttributes = $field->getAttribute('attributes');
			if ($fieldAttributes) {
				$createField .= " {$fieldAttributes}";
			}

			$fieldNull = $field->getAttribute('not-null');
			if ($fieldNull) {
				$createField .= ' NOT NULL';
			}

			if ($field->hasAttribute('default')) {
				$fieldDefault = $field->getAttribute('default');
				if ($fieldDefault != 'NULL') {
					$fieldDefault = "'{$fieldDefault}'";
				}
				$createField .= " DEFAULT {$fieldDefault}";
			}

			if ($field->getAttribute('increment')) {
				$createField .= ' AUTO_INCREMENT';
			}

			$fieldComment = $field->getAttribute('comment');
			if ($fieldComment) {
				$createField .= " COMMENT '{$fieldComment}'";
			}

			$success = true;
			try {
				$this->connection->queryResult($createField);
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) {
				if ($param == 'add') {
					$this->writeLog("Data base table ({$tableName}) field ({$fieldName}) has been created");
				} else {
					$this->writeLog("Data base table ({$tableName}) field ({$fieldName}) has been changed");
				}
			}
		}

		/**
		 * Удаляет индекс таблицы
		 * @param string $tableName имя таблицы
		 * @param string $indexName имя индекса
		 */
		private function dropIndex($tableName, $indexName) {
			$success = true;

			try {
				$this->connection->queryResult("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) {
				$this->writeLog("Data base table ({$tableName}) index ({$indexName}) has been dropped");
			}
		}

		/**
		 * Создает или изменяет индекс таблицы
		 * @param string $tableName имя таблицы
		 * @param string $indexName имя индекса
		 * @param string $param режим работы (add|modify)
		 * @throws coreException
		 */
		private function createTableIndex($tableName, $indexName, $param) {

			$doc = new DOMDocument();
			if (!$doc->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}
			$xpath = new DOMXPath($doc);

			if (!$index = $xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}']")->item(0)) {
				throw new coreException("Cannot change index {$indexName} in table {$tableName}");
			}

			$createIndex = "ALTER TABLE `{$tableName}` ADD ";

			$indexType = $index->getAttribute('type');
			if ($indexType) {
				if ($indexType == 'UNIQUE') {
					$createIndex .= 'UNIQUE';
				}
			} else {
				$createIndex .= 'INDEX';
			}

			if ($indexName) {
				$createIndex .= " `{$indexName}`";
			}

			if ($index->getElementsByTagName('field')->length) {
				$fieldList = $index->getElementsByTagName('field');
				$fieldsValue = '';
				$i = 1;

				/** @var DOMElement $field */
				foreach ($fieldList as $field) {
					$fieldValue = "`{$field->nodeValue}`";

					$fieldLength = $field->getAttribute('length');
					if ($fieldLength) {
						$fieldValue .= "({$fieldLength})";
					}

					if ($i == $fieldList->length) {
						$fieldsValue .= $fieldValue;
					} else {
						$fieldsValue .= "{$fieldValue},";
					}

					$i++;
				}

				$createIndex .= " ({$fieldsValue})";
			}

			if ($param == 'modify') {
				if ($index->getElementsByTagName('field')->length) {
					$fieldList = $index->getElementsByTagName('field');

					foreach ($fieldList as $field) {
						$fieldValue = $field->nodeValue;
						$constraint =
							$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@field='{$fieldValue}']")->item(0);

						if ($constraint) {
							$constraintName = $constraint->getAttribute('name');
							if ($constraintName) {
								try {
									$this->connection->queryResult("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
								} catch (databaseException $exception) {
									//nothing
								}
							}
						}
					}
				}

				$this->connection->queryResult("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
				$success = true;

				try {
					$this->connection->queryResult($createIndex);
				} catch (Exception $e) {
					$this->reportError($e->getMessage());
					$success = false;
				}

				if ($success) {
					$this->writeLog("Data base table ({$tableName}) index ({$indexName}) has been changed");
				}

				if ($index->getElementsByTagName('field')->length) {
					$fieldList = $index->getElementsByTagName('field');

					foreach ($fieldList as $field) {
						$fieldValue = $field->nodeValue;
						$constraint =
							$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@field='{$fieldValue}']")->item(0);

						if ($constraint) {
							$constraintName = $constraint->getAttribute('name');
							if ($constraintName) {
								$this->createTableConstraint($tableName, $constraintName, 'add');
							}
						}
					}
				}
			} else {
				$success = true;
				try {
					$this->connection->queryResult($createIndex);
				} catch (Exception $e) {
					$this->reportError($e->getMessage());
					$success = false;
				}

				if ($success) {
					$this->writeLog("Data base table ({$tableName}) index ({$indexName}) has been created");
				}
			}
		}

		/**
		 * Создает или изменяет внешний ключ таблицы
		 * @param string $tableName имя таблицы
		 * @param string $constraintName имя внешнего ключа
		 * @param string $param режим работы (add|modify)
		 * @throws coreException
		 */
		private function createTableConstraint($tableName, $constraintName, $param) {
			$doc = new DOMDocument();
			if (!$doc->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			$xpath = new DOMXPath($doc);
			if (!$constraint =
				$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}']")->item(0)) {
				throw new coreException("Cannot change constraint {$constraintName} in table {$tableName}");
			}

			if ($param == 'modify') {
				try {
					$this->connection->queryResult("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
				} catch (databaseException $exception) {
					//nothing
				}
			}

			$createConstraint = "ALTER TABLE `{$tableName}` ADD CONSTRAINT";
			$createConstraint .= " `{$constraintName}`";

			$constraintField = $constraint->getAttribute('field');
			if ($constraintField) {
				$createConstraint .= " FOREIGN KEY (`{$constraintField}`)";
			}

			$constraintRefTable = $constraint->getAttribute('ref-table');
			if ($constraintRefTable) {
				$createConstraint .= " REFERENCES `{$constraintRefTable}`";
			}

			$constraintRefField = $constraint->getAttribute('ref-field');
			if ($constraintRefField) {
				$createConstraint .= " (`{$constraintRefField}`)";
			}

			$constraintOnDelete = $constraint->getAttribute('on-delete');
			if ($constraintOnDelete) {
				$createConstraint .= " ON DELETE {$constraintOnDelete}";
			}

			$constraintOnUpdate = $constraint->getAttribute('on-update');
			if ($constraintOnUpdate) {
				$createConstraint .= " ON UPDATE {$constraintOnUpdate}";
			}

			$success = true;
			try {
				$this->connection->queryResult($createConstraint);
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) {
				if ($param == 'add') {
					$this->writeLog("Data base table ({$tableName}) constraint ({$constraintName}) has been created");
				} else {
					$this->writeLog("Data base table ({$tableName}) constraint ({$constraintName}) has been changed");
				}
			}

			if ($constraintOnDelete) {
				if ($constraintOnDelete == 'SET NULL') {
					$sql = <<<END
					UPDATE `{$tableName}`
					SET {$constraintField} = null
					WHERE {$constraintField} NOT IN (
						SELECT {$constraintRefField}
						FROM {$constraintRefTable}
					)
END;
				}

				if ($constraintOnDelete == 'CASCADE') {
					$sql = <<<END
					DELETE FROM `{$tableName}`
					WHERE {$constraintField} NOT IN (
						SELECT {$constraintRefField}
						FROM {$constraintRefTable}
					)
END;
				}

				$this->connection->queryResult($sql);
			}
		}

		/**
		 * Производит конвертацию базы данных из одной структуры в другую
		 * @throws coreException
		 */
		public function restoreDataBase() {
			//структура, которая должна быть
			$docNew = new DOMDocument();
			if (!$docNew->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			//имеющаяся структура
			$docOld = new DOMDocument();
			if (!$docOld->load($this->destinationFile)) {
				throw new coreException("Can't load xml: " . $this->destinationFile);
			}

			$oldTableListXpath = new DOMXPath($docOld);
			$newTableListXpath = new DOMXPath($docNew);
			$newTableList = $docNew->getElementsByTagName('table');

			/** @var DOMElement $newTable */
			foreach ($newTableList as $newTable) {
				$tableName = $newTable->getAttribute('name');
				if ($this->inParts && isset($this->state[$tableName]['complete']) && $this->state[$tableName]['complete']) {
					continue;
				}

				$this->connection->queryResult('SET foreign_key_checks = 0');
				$this->writeLog("Start checking table {$tableName}");

				if (!$oldTableListXpath->query("//table[@name='{$tableName}']")->length) {
					$this->createDataBaseTable($tableName);
				} else {
					$countResult = $this->connection->queryResult("SELECT count(*) FROM `{$tableName}`");
					$countResult->setFetchType(IQueryResult::FETCH_ROW);
					$countRows = 0;

					if ($countResult->length() > 0) {
						$fetchResult = $countResult->fetch();
						$countRows = array_shift($fetchResult);
					}

					if ($countRows > 10000) {
						$tableRestored = $this->checkTableRestore($tableName);

						if ($this->inParts) {
							end($this->state);
							$this->completed = ((key($this->state) == $tableName) && $tableRestored);
							return $this->completed;
						}

						continue;
					}

					$tableEngine = $newTable->getAttribute('engine');
					if (!$oldTableListXpath->query("//table[@name='{$tableName}' and @engine='{$tableEngine}']")->item(0)) {
						$this->changeTableEngine($tableName, $tableEngine);
					}

					$tableCharset = $newTable->getAttribute('charset');
					if (!$oldTableListXpath->query("//table[@name='{$tableName}' and @charset='{$tableCharset}']")->item(0)) {
						$this->changeTableCharset($tableName, $tableCharset);
					}

					$fieldsRoot = $newTable->getElementsByTagName('fields');
					if (is_object($fieldsRoot->item(0))) {
						$fieldList = $fieldsRoot->item(0)->getElementsByTagName('field');
						$newIndexesRoot = $newTable->getElementsByTagName('indexes');

						if (is_object($newIndexesRoot->item(0))) {
							$newIndexList = $newIndexesRoot->item(0)->getElementsByTagName('index');

							/** @var DOMElement $index */
							foreach ($newIndexList as $index) {
								if ($index->getAttribute('type') == 'PRIMARY') {
									$newFieldName = $index->getElementsByTagName('field')->item(0)->nodeValue;

									if ($newFieldName) {
										$oldFieldNameElement =
											$oldTableListXpath->query("//table[@name='{$tableName}']/indexes/index[@type='PRIMARY']/field")->item(0);

										if ($oldFieldNameElement) {
											$oldFieldName = $oldFieldNameElement->nodeValue;

											if ($newFieldName != $oldFieldName) {
												$oldField = $oldTableListXpath->query(
													"//table[@name='{$tableName}']/fields/field[@name = '{$oldFieldName}' and @increment='1']"
												)->item(0);

												if ($oldField) {
													$this->createTableField($tableName, $oldFieldName, 'modify');
												}

												$constraintList = $oldTableListXpath->query(
													"//table/constraints/constraint[@ref-table='{$tableName}' and @ref-field='{$oldFieldName}']"
												);
												if ($constraintList) {
													/** @var DOMElement $constraint */
													foreach ($constraintList as $constraint) {
														$constraintName = $constraint->getAttribute('name');
														$tableNameC =
															$constraint->parentNode->parentNode->getAttribute('name');
														try {
															$this->connection->queryResult(
																"ALTER TABLE `{$tableNameC}` DROP FOREIGN KEY `{$constraintName}`"
															);
														} catch (databaseException $exception) {
															//nothing
														}
													}
												}

												$this->connection->queryResult("ALTER TABLE `{$tableName}` DROP PRIMARY KEY");
												$this->connection->queryResult(
													"ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$newFieldName})"
												);
											}
										} else {
											$this->connection->queryResult(
												"ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$newFieldName})"
											);
										}
									}
								}
							}
						}

						/** @var DOMElement $field */
						foreach ($fieldList as $field) {
							$fieldName = $field->getAttribute('name');
							if (!$fieldName) {
								continue;
							}

							if (!$oldTableListXpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}']")->length) {
								$this->createTableField($tableName, $fieldName, 'add');
								continue;
							}

							$fieldType = $field->getAttribute('type');
							if ($fieldType) {
								if (!$oldTableListXpath->query(
									"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @type='{$fieldType}']"
								)->length) {
									$this->createTableField($tableName, $fieldName, 'modify');
								}
							}

							$fieldSize = $field->getAttribute('size');
							if ($fieldSize) {
								if (!$oldTableListXpath->query(
									"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @size='{$fieldSize}']"
								)->length) {
									$this->createTableField($tableName, $fieldName, 'modify');
								}
							}

							if ($field->getElementsByTagName('option')->length) {
								$options = $field->getElementsByTagName('option');
								foreach ($options as $option) {
									if (!$oldTableListXpath->query(
										"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}'][option ='{$option->nodeValue}']"
									)->length) {
										$this->createTableField($tableName, $fieldName, 'modify');
									}
								}
							}

							$fieldAttributes = $field->getAttribute('attributes');
							if ($fieldAttributes) {
								if (!$oldTableListXpath->query(
									"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @attributes='{$fieldAttributes}']"
								)->length) {
									$this->createTableField($tableName, $fieldName, 'modify');
								}
							}

							$fieldNull = $field->getAttribute('not-null');
							if ($fieldNull) {
								if (!$oldTableListXpath->query(
									"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @not-null='{$fieldNull}']"
								)->length) {
									$this->createTableField($tableName, $fieldName, 'modify');
								}
							}

							if ($field->hasAttribute('default')) {
								$fieldDefault = $field->getAttribute('default');
								if (!$oldTableListXpath->query(
									"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @default='{$fieldDefault}']"
								)->length) {
									$this->createTableField($tableName, $fieldName, 'modify');
								}
							}

							$fieldIncrement = $field->getAttribute('increment');
							if ($fieldIncrement) {
								if (!$oldTableListXpath->query(
									"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @increment='{$fieldIncrement}']"
								)->length) {
									$this->createTableField($tableName, $fieldName, 'modify');
								}
							}

							$fieldComment = $field->getAttribute('comment');
							if ($fieldComment) {
								if (!$oldTableListXpath->query(
									"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @comment='{$fieldComment}']"
								)->length) {
									$this->createTableField($tableName, $fieldName, 'modify');
								}
							}
						}
					}

					$newIndexesRoot = $newTable->getElementsByTagName('indexes');
					$oldIndexesRoot = $oldTableListXpath->query("//table[@name='{$tableName}' and @charset='{$tableCharset}']/indexes");

					if (is_object($newIndexesRoot->item(0))) {
						$newIndexList = $newIndexesRoot->item(0)->getElementsByTagName('index');

						foreach ($newIndexList as $index) {
							$indexName = $index->getAttribute('name');

							if ($indexName) {

								if (!$oldTableListXpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}']")->length) {
									$this->createTableIndex($tableName, $indexName, 'add');
									continue;
								}

								$indexType = $index->getAttribute('type');
								if ($indexType) {
									if (!$oldTableListXpath->query(
										"//table[@name='{$tableName}']/indexes/index[@name='{$indexName}' and @type='{$indexType}']"
									)->length) {
										$this->createTableIndex($tableName, $indexName, 'modify');
									}
								}

								if ($index->getElementsByTagName('field')->length) {
									$fieldList = $index->getElementsByTagName('field');

									foreach ($fieldList as $field) {
										if (!$oldTableListXpath->query(
											"//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field = '{$field->nodeValue}']"
										)->length) {
											$this->createTableIndex($tableName, $indexName, 'modify');
											continue;
										}

										$fieldLength = $field->getAttribute('length');
										if ($fieldLength) {
											if (!$oldTableListXpath->query(
												"//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field[@length='{$fieldLength}'] ='{$field->nodeValue}']"
											)->length) {
												$this->createTableIndex($tableName, $indexName, 'modify');
											}
										}
									}
								}
							}
						}
					}

					if (is_object($oldIndexesRoot->item(0))) {
						$oldIndexList = $oldIndexesRoot->item(0)->getElementsByTagName('index');

						foreach ($oldIndexList as $index) {
							$indexName = $index->getAttribute('name');

							if (!$indexName) {
								continue;
							}

							$newTableIndexList = $newTableListXpath->query("//table[@name='{$tableName}' and @charset='{$tableCharset}']/indexes/index[@name = '{$indexName}']");

							if ($newTableIndexList->length > 0) {
								continue;
							}

							$this->dropIndex($tableName, $indexName);
						}
					}

					$constraintsRoot = $newTable->getElementsByTagName('constraints');
					if (is_object($constraintsRoot->item(0))) {
						$constraintList = $constraintsRoot->item(0)->getElementsByTagName('constraint');

						foreach ($constraintList as $constraint) {
							$constraintName = $constraint->getAttribute('name');

							if ($constraintName) {
								if (!$oldTableListXpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}']")
									->item(0)) {
									$this->createTableConstraint($tableName, $constraintName, 'add');
									continue;
								}

								$constraintField = $constraint->getAttribute('field');
								if ($constraintField) {
									if (!$oldTableListXpath->query(
										"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @field='{$constraintField}']"
									)->item(0)) {
										$this->createTableConstraint($tableName, $constraintName, 'modify');
									}
								}

								$constraintRefTable = $constraint->getAttribute('ref-table');
								if ($constraintRefTable) {
									if (!$oldTableListXpath->query(
										"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-table='{$constraintRefTable}']"
									)->item(0)) {
										$this->createTableConstraint($tableName, $constraintName, 'modify');
									}
								}

								$constraintRefField = $constraint->getAttribute('ref-field');
								if ($constraintRefField) {
									if (!$oldTableListXpath->query(
										"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-field='{$constraintRefField}']"
									)->item(0)) {
										$this->createTableConstraint($tableName, $constraintName, 'modify');
									}
								}

								$constraintOnDelete = $constraint->getAttribute('on-delete');
								if ($constraintOnDelete) {
									if (!$oldTableListXpath->query(
										"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-delete='{$constraintOnDelete}']"
									)->item(0)) {
										$this->createTableConstraint($tableName, $constraintName, 'modify');
									}
								}

								$constraintOnUpdate = $constraint->getAttribute('on-update');
								if ($constraintOnUpdate) {
									if (!$oldTableListXpath->query(
										"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-update='{$constraintOnUpdate}']"
									)->item(0)) {
										$this->createTableConstraint($tableName, $constraintName, 'modify');
									}
								}
							}
						}
					}
				}

				$this->connection->queryResult('SET foreign_key_checks = 1');

				if ($this->inParts) {
					$this->state[$tableName]['complete'] = true;
					end($this->state);
					$this->completed = (key($this->state) == $tableName);
					return $this->completed;
				}
			}
		}

		/**
		 * Проводит подготовительные работы для конвертации таблицы:
		 *
		 * 1) Удаляет внешние ключи у исходной таблицы
		 * 2) Удаляет или заменяет null'ами значения исходной таблицы,
		 * которые бы отсутствовали, если бы работали внешние ключи
		 * 3) Создает новую таблицу с желаемой структурой
		 * 4) Возвращает служебную информацию ['temp_table' => имя созданной таблицы,
		 * 'count_rows' => количество записей в исходной таблице]
		 *
		 * @param string $tableName имя  таблицы
		 * @return array
		 * @throws coreException
		 */
		protected function prepareTable($tableName) {

			if ($this->inParts) {
				if (isset($this->state[$tableName]['info'])) {
					return $this->state[$tableName]['info'];
				}
			}

			$doc = new DOMDocument();
			$doc->load($this->sourceFile);

			$xpath = new DOMXPath($doc);

			$createSql = $this->restoreShowCreateTable($tableName);
			$tableNameNew = $tableName . '_temp';

			while (true) {
				$success = false;
				try {
					$this->connection->queryResult("SHOW CREATE TABLE `{$tableNameNew}`");
				} catch (Exception $e) {
					$success = true;
				}

				if (!$success) {
					$result = $this->connection->queryResult("SHOW CREATE TABLE `{$tableNameNew}`");
					foreach ($result as $row) {
						$newSql = str_replace($tableNameNew, $tableName, $row[1]);
						$newSql = preg_replace("/\s/", '', $newSql);
						$oldSql = preg_replace("/\s/", '', $createSql);
						if (stripos($oldSql, $newSql) !== false) {
							$this->connection->query("DROP TABLE `{$tableNameNew}`");
							$success = true;
						}
					}
				}

				if ($success) {
					break;
				}

				$tableNameNew .= '1';
			}

			if ($xpath->query("//table[@name='{$tableName}']/constraints/constraint")->length) {
				$constraintList = $xpath->query("//table[@name='{$tableName}']/constraints/constraint");

				/** @var DOMElement $constraint */
				foreach ($constraintList as $constraint) {
					$constraintName = $constraint->getAttribute('name');

					try {
						$this->connection->query("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
					} catch (databaseException $exception) {
						//nothing
					}

					$constraintOnDelete = $constraint->getAttribute('on-delete');
					if ($constraintOnDelete) {
						$constraintField = $constraint->getAttribute('field');
						$constraintRefTable = $constraint->getAttribute('ref-table');
						$constraintRefField = $constraint->getAttribute('ref-field');

						if ($constraintOnDelete == 'SET NULL') {
							$sql = <<<END
							UPDATE `{$tableName}`
							SET {$constraintField} = null
							WHERE {$constraintField} NOT IN (
								SELECT {$constraintRefField}
								FROM {$constraintRefTable}
							)
END;
						}

						if ($constraintOnDelete == 'CASCADE') {
							$sql = <<<END
							DELETE FROM `{$tableName}`
							WHERE {$constraintField} NOT IN (
								SELECT {$constraintRefField}
								FROM {$constraintRefTable}
							)
END;
						}

						try {
							$this->connection->query($sql);
						} catch (Exception $e) {
							//nothing
						}
					}
				}
			}

			$createSql = str_replace("CREATE TABLE `{$tableName}`", "CREATE TABLE `{$tableNameNew}`", $createSql);
			$this->connection->query($createSql);

			$countResult = $this->connection->queryResult("SELECT count(*) FROM `{$tableName}`");
			$countResult->setFetchType(IQueryResult::FETCH_ROW);
			$countRows = 0;

			if ($countResult->length() > 0) {
				$fetchResult = $countResult->fetch();
				$countRows = array_shift($fetchResult);
			}

			$info = [
				'temp_table' => $tableNameNew,
				'count_rows' => $countRows,
			];

			if ($this->inParts) {
				$this->state[$tableName]['info'] = $info;
			}

			return $info;
		}

		/**
		 * Переносит данные из таблицы со старой структурой в таблицу с новой структурой
		 * После окончания переноса старая таблица удаляется, а новая получает имя старой.
		 * @param string $tableName имя таблицы
		 * @return bool
		 * @throws coreException
		 */
		protected function restoreTableInParts($tableName) {
			$info = $this->prepareTable($tableName);
			$tableNameNew = $info['temp_table'];
			$countRows = $info['count_rows'];

			$fields = $this->getNecessaryFields($tableName);
			$fields = implode(', ', $fields);

			if ($this->inParts) {
				$offset = isset($this->state[$tableName]['info']['offset'])
					? $this->state[$tableName]['info']['offset']
					: 0;
				$this->connection->query(
					"INSERT INTO `{$tableNameNew}` ({$fields}) (SELECT {$fields} FROM {$tableName} LIMIT {$this->limit} OFFSET {$offset})"
				);
			} else {
				$step = ceil($countRows / $this->limit);
				for ($i = 0; $i < $step; $i++) {
					$offset = $i * $this->limit;
					$this->connection->query(
						"INSERT INTO `{$tableNameNew}` ({$fields}) (SELECT {$fields} FROM {$tableName} LIMIT {$this->limit} OFFSET {$offset})"
					);
				}
			}

			$insertedRowsCount = $this->connection->affectedRows();
			$countResultNew = $this->connection->queryResult("SELECT count(*) FROM `{$tableNameNew}`");
			$countResultNew->setFetchType(IQueryResult::FETCH_ROW);
			$countRowsNew = 0;

			if ($countResultNew->length() > 0) {
				$fetchResult = $countResultNew->fetch();
				$countRowsNew = array_shift($fetchResult);
			}

			if ($countRows == $countRowsNew || $insertedRowsCount === 0) {
				$this->connection->query("DROP TABLE `{$tableName}`");
				$this->connection->query("RENAME TABLE `{$tableNameNew}` TO `{$tableName}`");
				$this->writeLog("Data base table ({$tableName}) structure has been updated");
				if ($this->inParts) {
					$this->state[$tableName]['complete'] = true;
				}
				return true;
			}

			if ($this->inParts) {
				$this->state[$tableName]['info']['offset'] = $offset + $this->limit;
				$this->writeLog(
					"{$countRowsNew}({$countRows}) rows have been updated in table `({$tableName})`, {$insertedRowsCount} rows has been processed in this session"
				);
			} else {
				$this->reportError(getLabel('label-errors-13059') . $tableName . '/');
			}

			return false;
		}

		/**
		 * Проверяет должна ли измениться структура таблицы, если
		 * должна - запускает перенос данных
		 * @param string $tableName имя таблицы
		 * @return bool
		 * @throws coreException
		 */
		protected function checkTableRestore($tableName) {
			if ($this->inParts && isset($this->state[$tableName]['info'])) {
				return $this->restoreTableInParts($tableName);
			}

			//структура, которая должна быть
			$docNew = new DOMDocument();
			if (!$docNew->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			//имеющаяся структура
			$docOld = new DOMDocument();
			if (!$docOld->load($this->destinationFile)) {
				throw new coreException("Can't load xml: " . $this->destinationFile);
			}

			$xpath = new DOMXPath($docOld);
			$newTableList = $docNew->getElementsByTagName('table');

			/** @var DOMElement $newTable */
			foreach ($newTableList as $newTable) {
				if ($newTable->getAttribute('name') == $tableName) {
					$table = $newTable;
					break;
				}
			}

			$fieldsRoot = $table->getElementsByTagName('fields');
			if (is_object($fieldsRoot->item(0))) {
				$fieldList = $fieldsRoot->item(0)->getElementsByTagName('field');

				/** @var DOMElement $field */
				foreach ($fieldList as $field) {
					$fieldName = $field->getAttribute('name');
					if (!$fieldName) {
						continue;
					}

					if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}']")->length) {
						$this->createTableField($tableName, $fieldName, 'add');
						continue;
					}

					$fieldType = $field->getAttribute('type');
					if ($fieldType) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @type='{$fieldType}']"
						)->length) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$fieldSize = $field->getAttribute('size');
					if ($fieldSize) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @size='{$fieldSize}']"
						)->length) {
							return $this->restoreTableInParts($tableName);
						}
					}

					if ($field->getElementsByTagName('option')->length) {
						$options = $field->getElementsByTagName('option');
						foreach ($options as $option) {
							if (!$xpath->query(
								"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}'][option ='{$option->nodeValue}']"
							)->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
					}

					$fieldAttributes = $field->getAttribute('attributes');
					if ($fieldAttributes) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @attributes='{$fieldAttributes}']"
						)->length) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$fieldNull = $field->getAttribute('not-null');
					if ($fieldNull) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @not-null='{$fieldNull}']"
						)->length) {
							return $this->restoreTableInParts($tableName);
						}
					}

					if ($field->hasAttribute('default')) {
						$fieldDefault = $field->getAttribute('default');
						if (!$xpath->query(
							"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @default='{$fieldDefault}']"
						)->length) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$fieldIncrement = $field->getAttribute('increment');
					if ($fieldIncrement) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @increment='{$fieldIncrement}']"
						)->length) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$fieldComment = $field->getAttribute('comment');
					if ($fieldComment) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @comment='{$fieldComment}']"
						)->length) {
							return $this->restoreTableInParts($tableName);
						}
					}
				}
			}

			$indexesRoot = $table->getElementsByTagName('indexes');
			if (is_object($indexesRoot->item(0))) {
				$indexList = $indexesRoot->item(0)->getElementsByTagName('index');

				/** @var DOMElement $index */
				foreach ($indexList as $index) {
					$indexName = $index->getAttribute('name');

					if ($indexName) {
						if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}']")->length) {
							return $this->restoreTableInParts($tableName);
						}

						$indexType = $index->getAttribute('type');
						if ($indexType) {
							if (!$xpath->query(
								"//table[@name='{$tableName}']/indexes/index[@name='{$indexName}' and @type='{$indexType}']"
							)->length) {
								return $this->restoreTableInParts($tableName);
							}
						}

						if ($index->getElementsByTagName('field')->length) {
							$fieldList = $index->getElementsByTagName('field');
							foreach ($fieldList as $field) {
								if (!$xpath->query(
									"//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field = '{$field->nodeValue}']"
								)->length) {
									return $this->restoreTableInParts($tableName);
								}

								$fieldLength = $field->getAttribute('length');
								if ($fieldLength) {
									if (!$xpath->query(
										"//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field[@length='{$fieldLength}'] ='{$field->nodeValue}']"
									)->length) {
										return $this->restoreTableInParts($tableName);
									}
								}
							}
						}
					}

					if ($index->getAttribute('type') == 'PRIMARY') {
						$newFieldName = $index->getElementsByTagName('field')->item(0)->nodeValue;

						if ($newFieldName) {
							if ($xpath->query("//table[@name='{$tableName}']/indexes/index[@type='PRIMARY']/field")->item(0)) {
								$oldFieldName =
									$xpath->query("//table[@name='{$tableName}']/indexes/index[@type='PRIMARY']/field")->item(
										0
									)->nodeValue;

								if ($newFieldName != $oldFieldName) {
									return $this->restoreTableInParts($tableName);
								}
							} else {
								return $this->restoreTableInParts($tableName);
							}
						}
					}
				}
			}

			$constraintsRoot = $table->getElementsByTagName('constraints');
			if (is_object($constraintsRoot->item(0))) {
				$constraintList = $constraintsRoot->item(0)->getElementsByTagName('constraint');

				/** @var DOMElement $constraint */
				foreach ($constraintList as $constraint) {
					$constraintName = $constraint->getAttribute('name');
					if (!$constraintName) {
						continue;
					}

					if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}']")->item(0)) {
						return $this->restoreTableInParts($tableName);
					}

					$constraintField = $constraint->getAttribute('field');
					if ($constraintField) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @field='{$constraintField}']"
						)->item(0)) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$constraintRefTable = $constraint->getAttribute('ref-table');
					if ($constraintRefTable) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-table='{$constraintRefTable}']"
						)->item(0)) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$constraintRefField = $constraint->getAttribute('ref-field');
					if ($constraintRefField) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-field='{$constraintRefField}']"
						)->item(0)) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$constraintOnDelete = $constraint->getAttribute('on-delete');
					if ($constraintOnDelete) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-delete='{$constraintOnDelete}']"
						)->item(0)) {
							return $this->restoreTableInParts($tableName);
						}
					}

					$constraintOnUpdate = $constraint->getAttribute('on-update');
					if ($constraintOnUpdate) {
						if (!$xpath->query(
							"//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-update='{$constraintOnUpdate}']"
						)->item(0)) {
							return $this->restoreTableInParts($tableName);
						}
					}
				}
			}

			$tableEngine = $table->getAttribute('engine');
			if (!$xpath->query("//table[@name='{$tableName}' and @engine='{$tableEngine}']")->item(0)) {
				$this->changeTableEngine($tableName, $tableEngine);
			}

			$tableCharset = $table->getAttribute('charset');
			if (!$xpath->query("//table[@name='{$tableName}' and @charset='{$tableCharset}']")->item(0)) {
				$this->changeTableCharset($tableName, $tableCharset);
			}

			if ($this->inParts) {
				$this->state[$tableName]['complete'] = true;
			}
			return true;
		}

		/**
		 * Возвращает список имен полей таблицы из xml дампа старой структуры бд
		 * @param string $tableName имя таблицы
		 * @return array
		 * @throws coreException
		 */
		protected function getNecessaryFields($tableName) {
			$fields = [];

			$doc = new DOMDocument();
			if (!$doc->load($this->destinationFile)) {
				throw new coreException("Can't load xml: " . $this->destinationFile);
			}

			$xpath = new DOMXPath($doc);
			$tableFields = $xpath->query("//table[@name='{$tableName}']/fields/field");

			if ($tableFields->length) {
				/** @var DOMElement $field */
				foreach ($tableFields as $field) {
					$fields[] = $field->getAttribute('name');
				}
			}

			return $fields;
		}

		/**
		 * Возвращает путь до директории, в которой хранится состояние и кеш
		 * Если путь не задан - полагает, что он содержится в глобальной константе SYS_TEMP_PATH
		 * @return string
		 */
		private function getStateDirectoryPath() {
			if ($this->stateDirectoryPath !== null) {
				return $this->stateDirectoryPath;
			}

			/** @noinspection PhpUndefinedConstantInspection */
			return $this->stateDirectoryPath = SYS_TEMP_PATH;
		}
	}
