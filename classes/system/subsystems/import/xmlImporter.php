<?php

	use UmiCms\Service;
	use UmiCms\System\Trade\Offer\iExchange;
	use UmiCms\System\Import\UmiDump\Entity\iBaseImporter;

	/**
	 * Класс для импорта xml-файлов в формате UMIDUMP 2.0
	 * @todo: сделать импорт иерархических типов и типов полей таким же способов, как и остальные сущности.
	 * @todo: после реализации импорта не забыть про функционал удаления
	 * @link http://api.docs.umi-cms.ru/razrabotka_nestandartnogo_funkcionala/format_umidump_20/opisanie_formata/
	 */
	class xmlImporter implements iXmlImporter {

		/** @const string Версия формата UMIDUMP, с которой работает класс */
		const VERSION = '2.0';

		/** @const int Идентификатор корневого типа "Раздел сайта" */
		const ROOT_PAGE_TYPE_ID = 3;

		/** @var DomDocument документ в формате UMIDUMP, который нужно импортировать */
		protected $doc;

		/** @var DOMXPath объект для осуществления XPath-запросов по документу $doc */
		protected $parser;

		/**
		 * @var umiImportRelations экземпляр класса для проверки связей импортируемых объектов
		 * с уже существующими в системе объектами
		 */
		protected $relations;

		/** @var int идентификатор ресурса (каждому сценарию импорта соответствует свой ресурс) */
		protected $source_id;

		/** @var int Элемент, в который будут попадать элементы, у которых в дампе не существует родителя */
		protected $destination_element_id = 0;

		/** @var bool Режим, в котором объекты системы только создаются, но не обновляются */
		protected $update_ignore = false;

		/** @var bool Режим работы импортера в демо-режиме */
		protected $demosite_mode = false;

		/** @var array Общая информация об импорте */
		protected $meta = [];

		/**
		 * Счетчики манипуляций с импортируемыми объектами.
		 * Учитывается количество созданных, обновленных и удаленных объектов, а также число ошибок импорта.
		 */
		public
			$updated_types = 0, $created_types = 0,
			$updated_languages = 0, $created_languages = 0,
			$updated_domains = 0, $created_domains = 0,
			$updated_domain_mirrors = 0, $created_domain_mirrors = 0,
			$updated_templates = 0, $created_templates = 0,
			$updated_objects = 0, $created_objects = 0, $deleted_objects = 0,
			$updated_elements = 0, $created_elements = 0, $deleted_elements = 0,
			$updated_entities = 0, $created_entities = 0,
			$copied_files = 0,
			$updated_relations = 0,
			$created_restrictions = 0,
			$created_registry_items = 0,
			$created_permissions = 0,
			$created_field_types = 0,
			$created_dirs = 0,
			$import_errors = 0;

		/** @var bool Режим, при котором языковые константы будут переводиться */
		public $useI18n = true;

		/** @var bool|string Корневая директория для импортируемых файлов */
		public $filesSource = false;

		/**
		 * @var bool Режим, при котором новые создаваемые типы данных
		 * не будут наследовать группы и поля родительского типа данных
		 */
		public $ignoreParentGroups = true;

		/**
		 * @var bool Если включена эта настройка - объекты-значения полей создаются заново по названию,
		 * иначе значения берутся из узла <relations>, @see xmlImporter::importEntityRelation()
		 */
		public $auto_guide_creation = false;

		/**
		 * @var bool Режим, при котором файлы, указанные в импортируемых полях типа "файл"
		 * будут переименовываться в более удобное название.
		 */
		public $renameFiles = false;

		/**
		 * Журнал импорта.
		 * В него записываются сообщения о создании, обновлении, удалении объектов и ошибки.
		 * @var array
		 */
		protected $import_log = [];

		/** @var array идентификаторы созданных во время импорта страниц */
		protected $imported_elements = [];

		/** @var string $rootDirPath абсолютный путь до корневой директории */
		private $rootDirPath;

		/** @var bool $eventsEnabled включена ли отправка событий */
		private $eventsEnabled = true;

		/** @var int|null $forcedDomainId принудительный идентификатор домена @see setForcedDomainId() */
		private $forcedDomainId;

		/**
		 * Конструктор
		 * @param bool $sourceName Имя ресурса, указанного в сценарии импорта
		 */
		public function __construct($sourceName = false) {
			if ($sourceName) {
				$this->setSourceName($sourceName);
			}

			$this->doc = new DomDocument('1.0', 'utf-8');
			$this->relations = umiImportRelations::getInstance();
		}

		/**
		 * Устанавливает путь до директории, относительно которой требуется импортировать файлы
		 * @param string $path путь до директории
		 * @throws coreException
		 */
		public function setRootDirPath($path) {
			if (!is_string($path) || !is_dir($path)) {
				throw new coreException('Incorrect root directory path given');
			}

			$this->rootDirPath = $path;
		}

		/** @inheritdoc */
		public function getCreatedEntityCount() {
			return
				$this->created_types +
				$this->created_languages +
				$this->created_domains +
				$this->created_domain_mirrors +
				$this->created_templates +
				$this->created_objects +
				$this->created_elements +
				$this->created_restrictions +
				$this->created_registry_items +
				$this->created_permissions +
				$this->created_field_types +
				$this->created_dirs +
				$this->created_entities;
		}

		/** @inheritdoc */
		public function getUpdatedEntityCount() {
			return
				$this->updated_types +
				$this->updated_languages +
				$this->updated_domains +
				$this->updated_domain_mirrors +
				$this->updated_templates +
				$this->updated_objects +
				$this->updated_elements +
				$this->updated_relations +
				$this->updated_entities;
		}

		/** @inheritdoc */
		public function getDeletedEntityCount() {
			return $this->deleted_elements;
		}

		/** @inheritdoc */
		public function getErrorCount() {
			return $this->import_errors;
		}

		/**
		 * Установить/отключить такой режим работы импортера,
		 * при котором обновление существующих объектов системы будет игнорироваться.
		 * Объекты системы только создаются.
		 * @param boolean $updateIgnore
		 */
		public function setUpdateIgnoreMode($updateIgnore = true) {
			$this->update_ignore = (bool) $updateIgnore;
		}

		/** @inheritdoc **/
		public function setAutoGuideCreation($autoGuideCreation = false) {
			$this->auto_guide_creation = (bool) $autoGuideCreation;
			return $this;
		}

		/**
		 * Устанавливает режим, при котором файлы, указанные в импортируемых полях типа "файл"
		 * будут переименовываться в более удобное название.
		 * @param bool $renameFiles
		 */
		public function setRenameFiles($renameFiles = false) {
			$this->renameFiles = (bool) $renameFiles;
		}

		/**
		 * Устанавливает работу импорта в демо-режиме
		 * @param bool $demositeMode
		 * @return iXmlImporter
		 */
		public function setDemositeMode($demositeMode = true) {
			$this->demosite_mode = $demositeMode;
			return $this;
		}

		/**
		 * Генерирует DOM-документ для последующего импорта из переданной xml-строки
		 * @param string $xmlString xml-строка
		 * @return bool
		 */
		public function loadXmlString($xmlString) {
			return secure_load_dom_document($xmlString, $this->doc);
		}

		/**
		 * Трансформирует языковую константу, если необходимо - переводит ее.
		 * @param string $i18n языковая константа
		 * @param string|bool $path строка с указанием загружаемых файлов констант
		 * @return bool|mixed|string
		 */
		protected function getLabel($i18n, $path = false) {
			$label = false;

			if ($this->useI18n) {
				$args = func_get_args();
				$label = call_user_func_array('getLabel', $args);
			}

			if (!$label || $label == $i18n) {
				$label = str_replace('label-', '', $i18n);
				$label = preg_replace('/(.*?)-[m,f,n]+$/', '$1', $label);
				$label = str_replace('-', ' ', $label);
			}

			return $label;
		}

		/**
		 * Генерирует DOM-документ для последующего импорта из переданного пути до xml-файла
		 * @param string $path путь до файла
		 * @return bool
		 * @throws publicException
		 */
		public function loadXmlFile($path) {
			if (!is_file($path)) {
				throw new publicException($this->getLabel('label-cannot-read-file') . ' ' . $path);
			}

			return secure_load_dom_document(file_get_contents($path), $this->doc);
		}

		/**
		 * Получает DOM-документ, который будет импортироваться
		 * @param DOMDocument $doc
		 */
		public function loadXmlDocument(DOMDocument $doc) {
			$this->doc = $doc;
		}

		/**
		 * Устанавливает элемент, в который будут попадать элементы, у которых в дампе не существует родителя.
		 * По умолчанию такие элементы попадают в корень сайта
		 * @param int|iUmiHierarchyElement id элемента, либо сам элемент
		 * @return bool true, если удалось установить значение
		 */
		public function setDestinationElement($element) {
			if ($element instanceof iUmiHierarchyElement) {
				$this->destination_element_id = $element->getId();
				return true;
			}

			if (umiHierarchy::getInstance()->getElement($element, true, true) instanceof iUmiHierarchyElement) {
				$this->destination_element_id = $element;
				return true;
			}

			return false;
		}

		/**
		 * Запускает импорт
		 * @throws baseException
		 * @throws coreException
		 * @throws databaseException
		 * @throws publicException
		 * @throws wrongParamException
		 */
		public function execute() {
			$previousCacheSetting = cmsController::$IGNORE_MICROCACHE;
			$previousFilterSetting = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
			cmsController::$IGNORE_MICROCACHE = true;
			umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
			$config = mainConfiguration::getInstance();

			if (!defined('DISABLE_SEARCH_REINDEX') && !$config->get('kernel', 'import-auto-index')) {
				define('DISABLE_SEARCH_REINDEX', 1);
			}

			$previousCreationSetting = umiObjectProperty::$USE_FORCE_OBJECTS_CREATION;
			umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = false;

			if ($this->auto_guide_creation) {
				umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;
			}

			$this->init();

			$this->useI18n = false;
			$this->importDirs();

			if ($this->filesSource) {
				$this->importFiles();
			}

			$this->useI18n = true;
			$this->importRegistry();
			$this->importLangs();
			$this->importDomains();
			$this->importTemplates();
			$this->importDataTypes();
			$this->importTypes();
			$this->importObjects();
			$this->importElements();
			$this->importEntities();
			$this->importRelations();
			$this->importOptions();
			$this->importRestrictions();
			$this->importPermissions();
			$this->importHierarchy();

			umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = $previousCreationSetting;
			cmsController::$IGNORE_MICROCACHE = $previousCacheSetting;
			umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $previousFilterSetting;
		}

		/** @inheritdoc */
		public function demolish() {
			$this->init();

			$log = Service::UmiDumpDemolisherExecutor()
				->setRootPath($this->getRootDirPath())
				->setSourceId($this->source_id)
				->run($this->parser);

			foreach ($log as $message) {
				$this->writeLog($message);
			}

			return $this;
		}

		/**
		 * Устанавливает корневой путь для импортируемых файлов
		 * @param string $filesSource путь до директории
		 * @throws coreException
		 */
		public function setFilesSource($filesSource) {
			if (!is_dir($filesSource)) {
				throw new coreException($this->getLabel('label-cannot-find-files-source'));
			}

			$this->filesSource = $filesSource;
		}

		/**
		 * Устанавливает режим, при котором новые создаваемые типы данных
		 * не будут наследовать группы и поля родительского типа данных.
		 * @param bool $ignoreParentGroups
		 */
		public function setIgnoreParentGroups($ignoreParentGroups) {
			$this->ignoreParentGroups = (bool) $ignoreParentGroups;
		}

		/**
		 * Производит инициализацию обязательных свойств
		 * @throws publicException
		 */
		protected function init() {
			$this->parser = new DOMXPath($this->doc);
			$nodeList = $this->parser->evaluate('/umidump/@version');
			$version = $nodeList->length ? $nodeList->item(0)->nodeValue : '';

			if ($version != self::VERSION) {
				throw new publicException($this->getLabel('label-unknown-umidump-version'));
			}

			$this->parseMetaData();
			$this->source_id = $this->relations->getSourceId($this->getSourceName());

			if (!$this->source_id) {
				$this->source_id = $this->relations->addNewSource($this->getSourceName());
			}
		}

		/**
		 * Записывает сообщение об ошибке в журнал импорта
		 * @param string $error сообщение об ошибке
		 */
		protected function reportError($error) {
			$this->import_errors++;

			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) {
				Service::Response()
					->getCliBuffer()
					->push($error . PHP_EOL);
			} else {
				$this->import_log[] = "<font style='color:red''>" . $error . '</font>';
			}
		}

		/**
		 * Записывает сообщение в журнал импорта
		 * @param string $message сообщение
		 */
		protected function writeLog($message) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) {
				Service::Response()
					->getCliBuffer()
					->push($message . PHP_EOL);
			} else {
				$this->import_log[] = $message;
			}
		}

		/** @inheritdoc */
		public function getImportLog() {
			return $this->import_log;
		}

		/** Сохраняет общую информацию об импорте во внутренний кэш */
		protected function parseMetaData() {
			$meta = $this->meta;

			$siteName = $this->parser->evaluate('/umidump/meta/site-name');
			$meta['site-name'] = $siteName->length ? $siteName->item(0)->nodeValue : '';

			$domain = $this->parser->evaluate('/umidump/meta/domain');
			$meta['domain'] = $domain->length ? $domain->item(0)->nodeValue : '';

			$lang = $this->parser->evaluate('/umidump/meta/lang');
			$meta['lang'] = $lang->length ? $lang->item(0)->nodeValue : '';

			$timeStamp = $this->parser->evaluate('/umidump/meta/generate-time/timestamp');
			$meta['generated'] = $timeStamp->length ? $timeStamp->item(0)->nodeValue : '';

			$this->meta = $meta;

			if ($this->getSourceName() === null) {
				$sourceNameList = $this->parser->evaluate('/umidump/meta/source-name');
				$sourceName = $sourceNameList->length ? $sourceNameList->item(0)->nodeValue : md5($meta['domain']);
				$this->setSourceName($sourceName);
			}
		}

		/**
		 * Импортирует иерархический тип данных.
		 * Если типа с таким модулем/методом еще не существует, то он будет создан.
		 * @param string $baseModule модуль типа
		 * @param string $baseMethod метод типа
		 * @param string $baseTitle название типа
		 * @return iUmiHierarchyType
		 * @throws coreException
		 */
		protected function importHierarchyType($baseModule, $baseMethod, $baseTitle) {
			$collection = umiHierarchyTypesCollection::getInstance();
			$hierarchyType = $collection->getTypeByName($baseModule, $baseMethod);

			if (!$hierarchyType instanceof iUmiHierarchyType) {
				$collection->addType($baseModule, $baseTitle, $baseMethod);
				$hierarchyType = $collection->getTypeByName($baseModule, $baseMethod);
			}

			if (!$hierarchyType instanceof iUmiHierarchyType) {
				throw new coreException(
					$this->getLabel('label-cannot-create-hierarchy-type') . "{$baseModule}/{$baseMethod} ({$baseTitle})"
				);
			}

			return $hierarchyType;
		}

		/**
		 * Импортирует все объектные типы данных
		 * @throws coreException
		 * @throws databaseException
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		protected function importTypes() {
			$types = $this->parser->evaluate('/umidump/types/type');
			foreach ($types as $typeNode) {
				$this->importType($typeNode);
			}
		}

		/**
		 * Импортирует объектный тип данных
		 * @param DOMElement $typeNode узел типа данных
		 * @return bool|umiObjectType
		 * @throws coreException
		 * @throws databaseException
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		protected function importType(DOMElement $typeNode) {
			$oldId = $typeNode->getAttribute('id');
			$typeName = $typeNode->hasAttribute('title') ? $typeNode->getAttribute('title') : null;

			if (!$oldId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-type') . " \"{$typeName}\" " .
					$this->getLabel('label-with-empty-id')
				);
				return false;
			}

			$oldParentId = $typeNode->getAttribute('parent-id');
			$isGuidable = $typeNode->hasAttribute('guide') ? $typeNode->getAttribute('guide') : null;
			$isPublic = $typeNode->hasAttribute('public') ? $typeNode->getAttribute('public') : null;
			$isLocked = $typeNode->hasAttribute('locked') ? $typeNode->getAttribute('locked') : null;
			$guid = $typeNode->hasAttribute('guid') ? $typeNode->getAttribute('guid') : null;
			$domainId = $typeNode->hasAttribute('domain-id') ? $typeNode->getAttribute('domain-id') : null;

			$collection = umiObjectTypesCollection::getInstance();

			$created = false;
			$newTypeId = false;

			if ($guid !== null) {
				$newTypeId = $collection->getTypeIdByGUID($guid);

				if ($newTypeId && $newTypeId != $this->relations->getNewTypeIdRelation($this->source_id, $oldId)) {
					$this->relations->setTypeIdRelation($this->source_id, $oldId, $newTypeId);
				}
			}

			if (!$newTypeId) {
				$newTypeId = $this->relations->getNewTypeIdRelation($this->source_id, $oldId);
			}

			if ($newTypeId && $this->update_ignore) {
				$this->writeLog(
					$this->getLabel('label-datatype') . ' "' . $typeName . '" ' . $this->getLabel('label-already-exists')
				);
				return $collection->getType($newTypeId);
			}

			$baseTypeList = $this->parser->evaluate('base', $typeNode);
			/** @var DOMElement $baseType */
			$baseType = $baseTypeList->length ? $baseTypeList->item(0) : false;
			$baseModule = $baseType ? $baseType->getAttribute('module') : false;
			$baseMethod = $baseType ? $baseType->getAttribute('method') : false;
			$baseTitle = $baseType ? $baseType->nodeValue : false;
			$hierarchyType = false;

			if ($baseModule) {
				$hierarchyType = $this->importHierarchyType($baseModule, $baseMethod, $baseTitle);
			}

			if (!$newTypeId) {
				$newParentTypeId = (trim($oldParentId, '{}') == 'root-pages-type')
					? $collection->getTypeIdByGUID('root-pages-type')
					: $this->relations->getNewTypeIdRelation($this->source_id, $oldParentId);

				if ($typeName === null) {
					$typeName = 'Type #' . $oldId;
				}

				if ($hierarchyType instanceof iUmiHierarchyType) {
					$mainObjectTypeId = $collection->getTypeIdByHierarchyTypeId($hierarchyType->getId());
					$mainObjectType = $collection->getType($mainObjectTypeId);

					if (trim($oldParentId, '{}') == 'root-pages-type' && $mainObjectType instanceof iUmiObjectType) {
						$newTypeId = $mainObjectType->getId();
					} elseif (!$newParentTypeId && $mainObjectType instanceof iUmiObjectType) {
						$newParentTypeId = $mainObjectType->getId();
					}
				}

				if (!$newTypeId) {
					$newTypeId = $collection->addType(
						(int) $newParentTypeId,
						trim($typeName),
						false,
						$this->ignoreParentGroups
					);
				}

				$type = $collection->getType($newTypeId);

				if ($hierarchyType) {
					$type->setHierarchyTypeId($hierarchyType->getId());
				}

				$created = true;

				if ($guid !== null) {
					$collection->getType($newTypeId)->setGUID($guid);
				}

				$this->relations->setTypeIdRelation($this->source_id, $oldId, $newTypeId);
			}

			$type = $collection->getType($newTypeId);
			$installOnly = $typeNode->hasAttribute('install-only') ? (bool) $typeNode->getAttribute('install-only') : false;

			if (!$type instanceof iUmiObjectType) {
				$this->reportError(
					$this->getLabel('label-cannot-detect-type') . $this->getLabel('label-datatype') .
					"{$typeName} ({$oldId})"
				);
				return false;
			}

			if ($installOnly && !$created) {
				return false;
			}

			if ($typeName !== null) {
				$type->setName(trim($typeName));
			}

			if ($isPublic !== null) {
				$type->setIsPublic($isPublic == 'public' || $isPublic == '1');
			}

			if ($isGuidable !== null) {
				$type->setIsGuidable($isGuidable == 'guide' || $isGuidable == '1');
			}

			if ($isLocked !== null) {
				$type->setIsLocked($isLocked == 'locked' || $isLocked == '1');
			}

			if ($guid !== null) {
				$type->setGUID($guid);
			}

			$domainId = $this->getForcedDomainId() ?: $domainId;

			if ($domainId !== null && !$type->getIsLocked()) {
				$externalId = $domainId;
				$internalId = $this->relations->getNewDomainIdRelation($this->source_id, $externalId);

				if (is_int($internalId)) {
					$domainId = $internalId;
				} elseif (Service::DomainCollection()->isExists($externalId)) {
					$this->relations->setDomainIdRelation($this->source_id, $externalId, $externalId);
					$domainId = $externalId;
				} else {
					$domainId = null;
				}

				$type->setDomainId($domainId);
			}

			$type->commit();

			if ($created) {
				$this->created_types++;
				$this->writeLog(
					$this->getLabel('label-datatype') . ' "' . $typeName . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-m')
				);
			} else {
				$this->updated_types++;
				$this->writeLog(
					$this->getLabel('label-datatype') . ' "' . $typeName . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-updated-m')
				);
			}

			$this->importTypeGroups($type, $typeNode);
			return $type;
		}

		/**
		 * Импортирует все группы типа данных
		 * @param iUmiObjectType $type объектный тип данных
		 * @param DOMElement $typeNode узел типа данных
		 * @throws coreException
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		protected function importTypeGroups(iUmiObjectType $type, DOMElement $typeNode) {
			$groups = $this->parser->evaluate('fieldgroups/group', $typeNode);
			foreach ($groups as $groupNode) {
				$this->importTypeGroup($type, $groupNode);
			}
		}

		/**
		 * Импортирует группу типа данных
		 * @param iUmiObjectType $type объектный тип данных
		 * @param DOMElement $groupNode узел группы
		 * @param bool $importFields
		 * @return bool|null|umiFieldsGroup
		 * @throws coreException
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		protected function importTypeGroup(iUmiObjectType $type, DOMElement $groupNode, $importFields = true) {
			$oldGroupName = $groupNode->getAttribute('name');
			if (!$oldGroupName) {
				return false;
			}

			$newGroupName = self::translateName($oldGroupName);

			$title = $groupNode->hasAttribute('title') ? $groupNode->getAttribute('title') : null;
			$isVisible = $groupNode->hasAttribute('visible') ? $groupNode->getAttribute('visible') : null;
			$isLocked = $groupNode->hasAttribute('locked') ? $groupNode->getAttribute('locked') : null;
			$isActive = $groupNode->hasAttribute('active') ? $groupNode->getAttribute('active') : null;
			$tipNodes = $groupNode->getElementsByTagName('tip');
			$tipText = ($tipNodes->length > 0 ? $tipNodes->item(0)->nodeValue : null);

			$group = null;

			$groupId = $this->relations->getNewGroupId($this->source_id, $type->getId(), $oldGroupName);
			if ($groupId) {
				$group = $type->getFieldsGroup($groupId, true);
			}

			if (!$groupId) {
				$group = $type->getFieldsGroupByName($newGroupName, true);
				if ($group) {
					$this->relations->setGroupIdRelation($this->source_id, $type->getId(), $oldGroupName, $group->getId());
				}
			}

			if ($group instanceof iUmiFieldsGroup) {
				$childrenTypeGroupIdList = $this->getChildrenTypeGroupIdList($type->getId(), $newGroupName);

				foreach ($childrenTypeGroupIdList as $childrenTypeId => $childrenTypeGroupId) {
					if (!$this->relations->getNewGroupId($this->source_id, $childrenTypeId, $childrenTypeGroupId)) {
						unset($childrenTypeGroupIdList[$childrenTypeId]);
					}
				}
			} else {
				if ($title === null) {
					$title = 'Group #' . $oldGroupName;
				}
				$groupId = $type->addFieldsGroup($newGroupName, trim($title), true, false, $tipText);

				$this->relations->setGroupIdRelation($this->source_id, $type->getId(), $oldGroupName, $groupId);
				$group = $type->getFieldsGroup($groupId, true);
				$childrenTypeGroupIdList = $this->getChildrenTypeGroupIdList($type->getId(), $newGroupName);

				foreach ($childrenTypeGroupIdList as $childrenTypeId => $childrenGroupId) {
					$this->relations->setTypeIdRelation($this->source_id, $childrenTypeId, $childrenTypeId);
					$this->relations->setGroupIdRelation(
						$this->source_id,
						$childrenTypeId,
						$oldGroupName,
						$childrenGroupId
					);
				}
			}

			if ($group instanceof iUmiFieldsGroup) {
				$childrenTypeGroupIdList[$type->getId()] = $group->getId();
			}

			if (empty($childrenTypeGroupIdList)) {
				$this->reportError(
					$this->getLabel('label-cannot-import-group') . "{$oldGroupName}:" .
					$this->getLabel('label-cannot-detect-group')
				);
				return false;
			}

			$objectTypeCollection = umiObjectTypesCollection::getInstance();

			foreach ($childrenTypeGroupIdList as $typeId => $groupIdForUpdate) {
				$childrenType = $objectTypeCollection->getType($typeId);

				if (!$childrenType instanceof iUmiObjectType) {
					continue;
				}

				$includeInactiveGroups = true;
				$groupForUpdate = $childrenType->getFieldsGroup($groupIdForUpdate, $includeInactiveGroups);

				if (!$groupForUpdate instanceof iUmiFieldsGroup) {
					$objectTypeCollection->unloadType($typeId);
					continue;
				}

				if ($title !== null) {
					$groupForUpdate->setTitle(trim($title));
				}
				if ($isVisible !== null) {
					$groupForUpdate->setIsVisible($isVisible == 'visible' || $isVisible == '1');
				}
				if ($isActive !== null) {
					$groupForUpdate->setIsActive($isActive == 'active' || $isActive == '1');
				}
				if ($isLocked !== null) {
					$groupForUpdate->setIsLocked($isLocked == 'locked' || $isLocked == '1');
				}
				if ($tipText !== null) {
					$groupForUpdate->setTip($tipText);
				}

				$groupForUpdate->commit();

				if ($importFields) {
					$this->importGroupFields($groupForUpdate, $groupNode, $childrenType);
				}

				$objectTypeCollection->unloadType($typeId);
			}

			return $childrenTypeGroupIdList;
		}

		/**
		 * Возвращает список групп полей с заданными именем среди дочерних типов данных
		 * @param int $parentTypeId идентификатор родительского типа данных
		 * @param string $groupName название искомой группы
		 * @return array
		 *
		 * [
		 *        iUmiObjectType->getId() => iUmiFieldsGroup->getId()
		 * ]
		 *
		 * @throws coreException
		 */
		protected function getChildrenTypeGroupIdList($parentTypeId, $groupName) {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$childrenTypeIdList = $umiObjectTypes->getSubTypesList($parentTypeId);
			$childrenTypeGroupList = [];

			foreach ($childrenTypeIdList as $childrenTypeId) {
				$childrenType = $umiObjectTypes->getType($childrenTypeId);

				if (!$childrenType instanceof iUmiObjectType) {
					continue;
				}

				$includeInactiveGroups = true;
				$childrenGroup = $childrenType->getFieldsGroupByName($groupName, $includeInactiveGroups);
				$umiObjectTypes->unloadType($childrenType->getId());

				if (!$childrenGroup instanceof iUmiFieldsGroup) {
					continue;
				}

				$childrenTypeGroupList[$childrenTypeId] = $childrenGroup->getId();
				unset($childrenGroup);
			}

			return $childrenTypeGroupList;
		}

		/**
		 * Импортирует все поля группы
		 * @param iUmiFieldsGroup $group группа
		 * @param DOMElement $groupNode сущность группы
		 * @param iUmiObjectType $type тип, к которому принадлежит группа
		 * @throws coreException
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		protected function importGroupFields(iUmiFieldsGroup $group, DOMElement $groupNode, iUmiObjectType $type) {
			$fields = $this->parser->evaluate('field', $groupNode);
			foreach ($fields as $fieldNode) {
				$this->importField($group, $fieldNode, $type);
			}
		}

		/**
		 * Возвращает поле по его названию
		 * @param iUmiFieldsGroup $group группа
		 * @param string $name название поля
		 * @return bool|iUmiField
		 */
		protected function getFieldByName(iUmiFieldsGroup $group, $name) {
			$fieldList = $group->getFields();
			foreach ($fieldList as $field) {
				if ($field->getName() === $name) {
					return $field;
				}
			}
			return false;
		}

		/**
		 * Импортирует все типы полей
		 * @throws coreException
		 */
		protected function importDataTypes() {
			$datatypes = $this->parser->evaluate('/umidump/datatypes/datatype');
			foreach ($datatypes as $datatypeNode) {
				$this->importFieldType($datatypeNode);
			}
		}

		/**
		 * Импортирует тип поля
		 * @param DOMElement $datatypeNode сущность типа поля
		 * @return bool|umiFieldType
		 * @throws coreException
		 */
		protected function importFieldType(DOMElement $datatypeNode) {
			$name = $datatypeNode->getAttribute('name');
			$dataType = $datatypeNode->getAttribute('data-type');
			$multiple =
				$datatypeNode->getAttribute('multiple') == 1 ||
				$datatypeNode->getAttribute('multiple') == 'multiple';
			if (!$dataType) {
				return false;
			}

			$collection = umiFieldTypesCollection::getInstance();
			$fieldType = $collection->getFieldTypeByDataType($dataType, $multiple);

			$created = false;
			if (!$fieldType instanceof iUmiFieldType) {
				$fieldTypeId = $collection->addFieldType($name, $dataType);
				$fieldType = $collection->getFieldType($fieldTypeId);
				$fieldType->setIsMultiple($multiple);
				$fieldType->commit();
				$created = true;
			}

			if ($created) {
				$this->created_field_types++;
				$this->writeLog(
					$this->getLabel('label-field-type') . ' "' . $fieldType->getName() . '" ' .
					$this->getLabel('label-has-been-created-m')
				);
			}

			return $fieldType;
		}

		/**
		 * Создает справочник и возвращает его идентификатор
		 * @param string $title название
		 * @return int
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getAutoGuideId($title) {
			$guideName = getLabel('label-catalog-for-field') . " \"{$title}\"";
			$collection = umiObjectTypesCollection::getInstance();
			$parentTypeId = $collection->getTypeIdByGUID('root-guides-type');
			$childTypeIdList = $collection->getChildTypeIds($parentTypeId);

			foreach ($childTypeIdList as $childTypeId) {
				$childType = $collection->getType($childTypeId);
				$childTypeName = $childType->getName();

				if ($childTypeName == $guideName) {
					$childType->setIsGuidable(true);
					return $childTypeId;
				}
			}

			$guideId = $collection->addType($parentTypeId, $guideName);
			$guide = $collection->getType($guideId);
			$guide->setIsGuidable(true);
			$guide->setIsPublic(true);
			$guide->commit();

			return $guideId;
		}

		/** @inheritdoc */
		public function enableEvents() {
			$this->eventsEnabled = true;
			return $this;
		}

		/** @inheritdoc */
		public function disableEvents() {
			$this->eventsEnabled = false;
			return $this;
		}

		/** @inheritdoc */
		public function setForcedDomainId($id) {
			$this->forcedDomainId = (int) $id;
			return $this;
		}

		/**
		 * Возвращает идентификатор принудительного домена
		 * @return int|null
		 */
		private function getForcedDomainId() {
			return $this->forcedDomainId;
		}

		/**
		 * Импортирует поле
		 * @param iUmiFieldsGroup $group группа
		 * @param DOMElement $fieldNode сущность поля
		 * @param iUmiObjectType $type тип, к которому принадлежит группа
		 * @return bool|null|umiField
		 * @throws coreException
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		protected function importField(iUmiFieldsGroup $group, DOMElement $fieldNode, iUmiObjectType $type) {
			$oldFieldName = $fieldNode->getAttribute('name');
			if (!$oldFieldName) {
				$this->reportError($this->getLabel('label-cannot-import-field-with-empty-name'));
				return false;
			}

			$title = $fieldNode->hasAttribute('title') ? $fieldNode->getAttribute('title') : null;
			$tip = $fieldNode->hasAttribute('tip') ? $fieldNode->getAttribute('tip') : null;
			$isVisible = $fieldNode->hasAttribute('visible') ? $fieldNode->getAttribute('visible') : null;
			$isLocked = $fieldNode->hasAttribute('locked') ? $fieldNode->getAttribute('locked') : null;
			$isInheritable = $fieldNode->hasAttribute('inheritable') ? $fieldNode->getAttribute('inheritable') : null;
			$isIndexable = $fieldNode->hasAttribute('indexable') ? $fieldNode->getAttribute('indexable') : null;
			$isFilterable = $fieldNode->hasAttribute('filterable') ? $fieldNode->getAttribute('filterable') : null;
			$isRequired = $fieldNode->hasAttribute('required') ? $fieldNode->getAttribute('required') : null;
			$isSystem = $fieldNode->hasAttribute('system') ? $fieldNode->getAttribute('system') : null;
			$isImportant = $fieldNode->hasAttribute('important') ? $fieldNode->getAttribute('important') : null;

			$newFieldName = self::translateName($oldFieldName);
			$objectTypeId = $group->getTypeId();

			$collection = umiFieldsCollection::getInstance();
			$typesCollection = umiObjectTypesCollection::getInstance();
			$field = null;

			$fieldTypeNodeList = $this->parser->evaluate('type', $fieldNode);

			/** @var DOMElement $fieldTypeNode */
			$fieldTypeNode = $fieldTypeNodeList->length ? $fieldTypeNodeList->item(0) : false;
			if (!$fieldTypeNode) {
				$this->reportError(
					$this->getLabel('label-cannot-import-field') . " {$oldFieldName}: " .
					$this->getLabel('label-cannot-detect-datatype')
				);
				return false;
			}

			$fieldType = $this->importFieldType($fieldTypeNode);
			if (!$fieldType instanceof iUmiFieldType) {
				$this->reportError($this->getLabel('label-cannot-detect-field-type-for') . " {$oldFieldName}");
				return false;
			}

			$fieldTypeId = $fieldType->getId();
			$objectType = $typesCollection->getType($objectTypeId);

			$fieldId = $objectType->getFieldId($newFieldName, false);
			if ($fieldId) {
				$field = $collection->getField($fieldId);
				if ($field instanceof iUmiField &&
					$fieldId != $this->relations->getNewFieldId($this->source_id, $objectTypeId, $oldFieldName)) {
					$this->relations->setFieldIdRelation($this->source_id, $objectTypeId, $oldFieldName, $fieldId);
				}
			}

			$this->executeDetectFieldEvent($fieldNode, $objectTypeId, $field);

			if (!$field instanceof iUmiField) {
				$parentTypeId = $objectType->getParentId();

				if ($parentTypeId) {
					$parentType = $typesCollection->getType($parentTypeId);
					$parentFieldId = $parentType->getFieldId($newFieldName, false);

					if ($parentFieldId) {
						$parentField = $collection->getField($parentFieldId);
						if ($parentField->getFieldTypeId() == $fieldTypeId && $parentField->getTitle() == $title) {
							$field = $parentField;
							$group->attachField($parentFieldId);
							$this->relations->setFieldIdRelation($this->source_id, $objectTypeId, $oldFieldName, $field->getId());
						}
					}

					if (!$field instanceof iUmiField) {
						$horizontalTypes = $typesCollection->getSubTypesList($parentTypeId);

						foreach ($horizontalTypes as $horizontalTypeId) {
							if ($horizontalTypeId == $objectTypeId) {
								continue;
							}

							$horizontalType = $typesCollection->getType($horizontalTypeId);
							if (!$horizontalType instanceof iUmiObjectType) {
								continue;
							}

							$horizontalFieldId = $horizontalType->getFieldId($newFieldName, false);

							if ($horizontalFieldId) {
								$horizontalField = $collection->getField($horizontalFieldId);

								if (!$horizontalField instanceof iUmiField) {
									continue;
								}

								if ($horizontalField->getFieldTypeId() == $fieldTypeId &&
									$horizontalField->getTitle() == $title) {
									$field = $horizontalField;
									$group->attachField($horizontalFieldId);
									$this->relations->setFieldIdRelation(
										$this->source_id,
										$objectTypeId,
										$oldFieldName,
										$field->getId()
									);
									break;
								}
							}
						}
					}
				}
			}

			if (!$field instanceof iUmiField) {
				if ($title === null) {
					$title = $oldFieldName;
				}
				$fieldId = $collection->addField($newFieldName, trim($title), $fieldTypeId, false);
				$this->relations->setFieldIdRelation($this->source_id, $objectTypeId, $oldFieldName, $fieldId);

				$group->attachField($fieldId);
				$field = $collection->getField($fieldId);
				if ($isVisible === null) {
					$field->setIsVisible($isVisible);
				}
				if ($isFilterable === null) {
					$field->setIsInFilter($isFilterable);
				}
				if ($isIndexable === null) {
					$field->setIsInSearch($isIndexable);
				}
			} elseif($type->getIsLocked() && $this->getSourceName() !== 'system') {
				return $field;
			}

			$oldGuideId = $fieldNode->hasAttribute('guide-id') ? $fieldNode->getAttribute('guide-id') : null;
			$this->setGuideId($field, $oldGuideId);

			if ($field->getFieldTypeId() != $fieldTypeId) {
				$field->setFieldTypeId($fieldTypeId);
			}

			if ($title !== null) {
				$field->setTitle(trim($title));
			}
			if ($isVisible !== null) {
				$field->setIsVisible($isVisible == 'visible' || $isVisible == '1');
			}
			if ($isIndexable !== null) {
				$field->setIsInSearch($isIndexable == 'indexable' || $isIndexable == '1');
			}
			if ($isFilterable !== null) {
				$field->setIsInFilter($isFilterable == 'filterable' || $isFilterable == '1');
			}
			if ($isRequired !== null) {
				$field->setIsRequired($isRequired == 'required' || $isRequired == '1');
			}
			if ($isSystem !== null) {
				$field->setIsSystem($isSystem == 'system' || $isSystem == '1');
			}
			if ($tip !== null) {
				$field->setTip(trim($tip));
			}
			if ($isLocked !== null) {
				$field->setIsLocked($isLocked == 'locked' || $isLocked == '1');
			}
			if ($isInheritable !== null) {
				$field->setIsInheritable($isInheritable == 'inheritable' || $isInheritable == '1');
			}
			if ($isImportant !== null) {
				$field->setImportanceStatus((bool) $isImportant);
			}
			$tips = $this->parser->evaluate('tip', $fieldNode);
			$tip = $tips->length ? $tips->item(0) : false;
			if ($tip) {
				$field->setTip($tip->nodeValue);
			}

			$field->commit();

			return $field;
		}

		/**
		 * Устанавливает идентификатор справочника для поля
		 * @param iUmiField $field поле
		 * @param int|string|null $oldGuideId идентификатор справочника из источника импорта
		 * @return iUmiField
		 * @throws databaseException
		 * @throws coreException
		 */
		private function setGuideId(iUmiField $field, $oldGuideId) {
			if (!in_array($field->getDataType(), ['relation', 'optioned'])) {
				return $field;
			}

			$guideId = null;

			if ($oldGuideId && $newGuideId = $this->relations->getNewTypeIdRelation($this->source_id, $oldGuideId)) {
				$guideId = $newGuideId;
			} elseif($this->auto_guide_creation) {
				$guideId = $this->getAutoGuideId($field->getTitle());
			}

			if ($guideId) {
				$field->setGuideId($guideId);
			}

			return $field;
		}

		/**
		 * Запускает событие определения поля при его импорте
		 * @param DOMElement $fieldInfo данные импортируемого поля
		 * @param int $typeId ID типа данных, в котором содержится поле
		 * @param mixed $field объект, в который может быть записан новый объект поля
		 */
		private function executeDetectFieldEvent($fieldInfo, $typeId, &$field) {
			try {
				$event = new umiEventPoint('OnDetectFieldDuringImport');
				$event->setParam('source_id', $this->source_id);
				$event->setParam('field_info', $fieldInfo);
				$event->setParam('object_type_id', $typeId);
				$event->addRef('field', $field);
				$this->callEvent($event);
			} catch (Exception $e) {
				$this->writeLog(sprintf('Error while handling detect field event: "%s"', $e->getMessage()));
			}
		}


		/**
		 * Импортирует все страницы
		 * @throws coreException
		 * @throws baseException
		 */
		protected function importElements() {
			$pages = $this->parser->evaluate('/umidump/pages/page');
			foreach ($pages as $pageNode) {
				$this->importElement($pageNode);
			}
		}

		/**
		 * Определяет идентификатор шаблона
		 * @param string $filepath путь к шаблону
		 * @param bool|int $domainId идентификатор домена
		 * @param bool|int $langId идентификатор языка
		 * @return bool|int
		 * @throws coreException
		 */
		protected function detectTemplateId($filepath, $domainId = false, $langId = false) {
			if (!$filepath) {
				return false;
			}

			$domainId = $domainId ?: Service::DomainDetector()->detectId();
			$langId = $langId ?: Service::LanguageDetector()->detectId();
			$templateList = templatesCollection::getInstance()
				->getTemplatesList($domainId, $langId);

			foreach ($templateList as $template) {
				if ($template->getFilename() == $filepath || (is_numeric($filepath) && $filepath == $template->getId())) {
					return $template->getId();
				}
			}

			return false;
		}

		/** Импортирует все связи иерархии страниц */
		protected function importHierarchy() {
			$relationNodeList = $this->parser->evaluate('/umidump/hierarchy/relation');
			$parentIdList = [];

			foreach ($relationNodeList as $relationNode) {
				$parentId = $this->importHierarchyRelation($relationNode);

				if ($parentId !== false) {
					$parentIdList[] = $parentId;
				}
			}

			$umiHierarchy = umiHierarchy::getInstance();
			foreach ($parentIdList as $parentId) {
				$umiHierarchy->rebuildRelationNodes($parentId);
			}
		}

		/**
		 * Импортирует связь иерархии страниц
		 * @param DOMElement $relationNode
		 * @return bool|int|string
		 */
		protected function importHierarchyRelation(DOMElement $relationNode) {
			$oldId = $relationNode->getAttribute('id');
			$oldParentId = $relationNode->getAttribute('parent-id');
			$ord = $relationNode->getAttribute('ord');

			$parentChanged = false;
			$elementId = $this->relations->getNewIdRelation($this->source_id, $oldId);

			if (!$elementId) {
				return false;
			}

			$collection = umiHierarchy::getInstance();
			$element = $collection->getElement($elementId, true, true);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$parentId = false;

			if ($oldParentId) {
				$parentId = $this->relations->getNewIdRelation($this->source_id, $oldParentId);
			}

			if ($parentId === false) {
				$parentId = $this->destination_element_id;
			}

			try {
				if ($parentId != $element->getParentId()) {
					$element->setRel($parentId);
					$parentChanged = true;
				}
			} catch (coreException $e) {
				return false;
			}

			if ($ord && $ord != $element->getOrd()) {
				$element->setOrd($ord);
				$element->commit();
			}

			if ($parentChanged) {
				$element->commit();
				return $parentId;
			}

			return false;
		}

		/**
		 * Импортирует элемент в систему
		 * @param DOMElement $pageNode атрибуты импортируемого элемента
		 * @param bool|true $importProperties нужно ли импортировать свойства элемента
		 * @return false|umiHierarchyElement
		 * @throws coreException
		 * @throws baseException
		 */
		protected function importElement(DOMElement $pageNode, $importProperties = true) {
			$oldId = $pageNode->getAttribute('id');
			$oldTypeId = $pageNode->getAttribute('type-id');
			$updateOnly = $pageNode->getAttribute('update-only') == '1';

			$nameNodeList = $pageNode->getElementsByTagName('name');
			$name = $nameNodeList->length ? $nameNodeList->item(0)->nodeValue : false;

			$templateNodeList = $pageNode->getElementsByTagName('template');
			$template = $templateNodeList->length ? $templateNodeList->item(0)->nodeValue : null;

			$altName = $pageNode->getAttribute('alt-name');

			$isActive = $pageNode->hasAttribute('is-active') ? $pageNode->getAttribute('is-active') : null;
			$oldParentId = $pageNode->hasAttribute('parentId') ? $pageNode->getAttribute('parentId') : null;
			$isVisible = $pageNode->hasAttribute('is-visible') ? $pageNode->getAttribute('is-visible') : null;
			$isDeleted = $pageNode->hasAttribute('is-deleted') ? $pageNode->getAttribute('is-deleted') : null;
			$langId = $pageNode->hasAttribute('lang-id') ? $pageNode->getAttribute('lang-id') : false;
			$domainId = $pageNode->hasAttribute('domain-id') ? $pageNode->getAttribute('domain-id') : false;
			$isDefault = $pageNode->hasAttribute('is-default') ? $pageNode->getAttribute('is-default') : false;

			if ($domainId) {
				$domainId = $this->relations->getNewDomainIdRelation($this->source_id, $domainId);
			}

			if ($langId) {
				$langId = $this->relations->getNewLangIdRelation($this->source_id, $langId);
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjectTypes = umiObjectTypesCollection::getInstance();

			$created = false;
			$elementId = $oldId ? $this->relations->getNewIdRelation($this->source_id, $oldId) : false;

			if ($elementId && $this->update_ignore) {
				$this->writeLog('Element "' . $name . "\" (#{$oldId}) already exists");
				return $umiHierarchy->getElement($elementId);
			}

			if (!$elementId) {
				if ($updateOnly) {
					return false;
				}

				if (!$name) {
					$name = $oldId;
				}

				if (!$altName) {
					$altName = $name;
				}

				if (!$oldTypeId) {
					$this->reportError(
						$this->getLabel('label-cannot-create-element') . ": \"{$name}\" (#{$oldId}) ." .
						$this->getLabel('label-cannot-detect-type')
					);
					return false;
				}

				$typeId = $this->relations->getNewTypeIdRelation($this->source_id, $oldTypeId);
				$type = $umiObjectTypes->getType($typeId);

				if (!$type instanceof iUmiObjectType) {
					$this->reportError(
						$this->getLabel('label-cannot-create-element') . "\"{$name}\" ($oldId): " .
						$this->getLabel('label-cannot-detect-type') . " #{$oldTypeId}"
					);
					return false;
				}

				$hierarchyTypeId = $type->getHierarchyTypeId();
				if ($hierarchyTypeId) {
					$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
					if ($hierarchyType instanceof iUmiHierarchyType) {
						$module = $hierarchyType->getModule();
						if (!Service::Registry()->get("//modules/{$module}")) {
							$this->reportError(
								$this->getLabel('label-cannot-create-element') . "\"{$name}\" ($oldId): " .
								$this->getLabel('label-module-not-installed', false, $module)
							);
							return false;
						}
					}
				}

				$parentId = false;
				if ($oldParentId) {
					$parentId = $this->relations->getNewIdRelation($this->source_id, $oldParentId);
				}
				if ($parentId === false) {
					$parentId = $this->destination_element_id;
				}

				$domainId = $this->getForcedDomainId() ?: $domainId;

				$event = new umiEventPoint('exchangeOnAddElement');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('before');
				$event->setParam('parent_id', $parentId);
				$event->setParam('old_element_id', $oldId);
				$event->setParam('type', $type);
				$event->setParam('element_info', $pageNode);
				$event->addRef('parent_id', $parentId);
				$event->addRef('old_element_id', $oldId);
				$event->addRef('type', $type);
				$event->addRef('name', $name);
				$event->addRef('alt_name', $altName);
				$event->addRef('parent_id', $parentId);
				$event->addRef('domain_id', $domainId);
				$event->addRef('lang_id', $langId);
				$event->addRef('element_info', $pageNode);

				$this->callEvent($event);

				$elementId = $umiHierarchy->addElement(
					$parentId,
					$type->getHierarchyTypeId(),
					$name,
					$altName,
					$type->getId(),
					$domainId,
					$langId
				);
				$this->imported_elements[] = $elementId;
				permissionsCollection::getInstance()->setDefaultPermissions($elementId);
				$this->relations->setIdRelation($this->source_id, $oldId ?: $elementId, $elementId);

				if ($isActive === null) {
					$nameNodeList = $pageNode->getElementsByTagName('default-active');
					if ($nameNodeList->length) {
						$isActive = $nameNodeList->item(0)->nodeValue;
					}
				}

				if ($isVisible === null) {
					$nameNodeList = $pageNode->getElementsByTagName('default-visible');
					if ($nameNodeList->length) {
						$isVisible = $nameNodeList->item(0)->nodeValue;
					}
				}

				if ($template === null) {
					$nameNodeList = $pageNode->getElementsByTagName('default-template');
					if ($nameNodeList->length) {
						$template = $nameNodeList->item(0)->nodeValue;
					}
				}

				$created = true;
			}

			$element = $umiHierarchy->getElement($elementId, true, true);
			$installOnly = $pageNode->hasAttribute('install-only') ? (bool) $pageNode->getAttribute('install-only') : false;

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($installOnly && !$created) {
				return false;
			}

			if (!$created) {
				$event = new umiEventPoint('exchangeOnUpdateElement');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('before');
				$event->addRef('element', $element);
				$event->setParam('element_info', $pageNode);
				$event->addRef('name', $name);
				$event->addRef('is_active', $isActive);
				$event->addRef('is_visible', $isVisible);
				$event->addRef('is_default', $isDefault);
				$event->addRef('template_path', $template);
				$event->addRef('is_deleted', $isDeleted);
				$event->addRef('element_info', $pageNode);
				$this->callEvent($event);
			}

			if ($name) {
				$element->setName($name);
			}

			if ($altName) {
				$element->setAltName($altName);
			}

			if ($isActive !== null) {
				$element->setIsActive($isActive == 'active' || $isActive == '1');
			}
			if ($isVisible !== null) {
				$element->setIsVisible($isVisible == 'visible' || $isVisible == '1');
			}
			if ($isDefault) {
				$element->setIsDefault($isDefault == 'default' || $isDefault == '1');
			}

			if ($template !== null && $tpl_id = $this->detectTemplateId($template, $domainId, $langId)) {
				$element->setTplId($tpl_id);
			}

			if ($created) {
				$oldObjectId = $pageNode->hasAttribute('object-id') ? $pageNode->getAttribute('object-id') : null;

				if ($oldObjectId !== null) {
					$objectId = $element->getObjectId();
					$newObjectId = $this->relations->getNewObjectIdRelation($this->source_id, $oldObjectId);

					if ($newObjectId) {
						$object = umiObjectsCollection::getInstance()->getObject($newObjectId);

						if ($element->getObjectTypeId() == $object->getTypeId()) {
							$element->setObject($object);
							$element->commit();
							umiObjectsCollection::getInstance()->delObject($objectId);
						} else {
							$this->relations->setObjectIdRelation($this->source_id, $oldObjectId, $objectId);
						}
					} else {
						$this->relations->setObjectIdRelation($this->source_id, $oldObjectId, $objectId);
					}
				}
			}

			if ($isDeleted !== null) {
				$element->setDeleted($isDeleted == 'deleted' || $isDeleted == '1');
			}

			if ($importProperties) {
				$this->importPropValues($element, $pageNode, $created);
			}

			if ($isDeleted && ($isDeleted == 'deleted' || $isDeleted == '1')) {
				$this->deleted_elements++;
				$this->writeLog(
					$this->getLabel('label-page') . ' "' . $element->getName() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-deleted-m')
				);
			} elseif ($created) {
				$this->created_elements++;
				$this->writeLog(
					$this->getLabel('label-page') . ' "' . $element->getName() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-f')
				);
			} elseif ($element->getIsUpdated()) {
				$this->updated_elements++;
				$this->writeLog(
					$this->getLabel('label-page') . ' "' . $element->getName() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-updated-f')
				);
			}

			if ($created) {
				$event = new umiEventPoint('exchangeOnAddElement');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('after');
				$event->addRef('element', $element);
				$event->setParam('element_info', $pageNode);
				$this->callEvent($event);
			} else {
				$event = new umiEventPoint('exchangeOnUpdateElement');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('after');
				$event->addRef('element', $element);
				$event->setParam('element_info', $pageNode);
				$this->callEvent($event);
			}

			$element->commit();
			$umiHierarchy->unloadElement($elementId);
			return $element;
		}

		/**
		 * Импортирует все языки
		 * @throws coreException
		 * @throws wrongParamException
		 */
		protected function importLangs() {
			$languages = $this->parser->evaluate('/umidump/langs/lang');
			foreach ($languages as $languageNode) {
				$this->importLang($languageNode);
			}
		}

		/**
		 * Импортирует язык
		 * @param DOMElement $langNode сущность языка
		 * @return iLang|bool
		 * @throws coreException
		 * @throws wrongParamException
		 */
		protected function importLang(DOMElement $langNode) {
			$oldId = $langNode->getAttribute('id');
			$title = $langNode->nodeValue;

			if (!$oldId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-language') . " \"{$title}\" " .
					$this->getLabel('label-with-empty-id')
				);
				return false;
			}

			$isDefault = $langNode->hasAttribute('is-default') ? $langNode->getAttribute('is-default') : null;
			$prefix = $langNode->hasAttribute('prefix') ? $langNode->getAttribute('prefix') : null;

			$collection = Service::LanguageCollection();

			$created = false;
			$languageId = $this->relations->getNewLangIdRelation($this->source_id, $oldId);

			if (!$languageId && $prefix) {
				$languageId = $collection->getLangId($prefix);
				if ($languageId) {
					$this->relations->setLangIdRelation($this->source_id, $oldId, $languageId);
				}
			}

			if (!$languageId) {
				if (!$title) {
					$title = $oldId;
				}

				$languageId = $collection->addLang($prefix, $title);
				$this->relations->setLangIdRelation($this->source_id, $oldId, $languageId);
				$created = true;
			}

			$language = $collection->getLang($languageId);
			$installOnly = $langNode->hasAttribute('install-only') ? (bool) $langNode->getAttribute('install-only') : false;

			if (!$language instanceof iLang) {
				$this->reportError($this->getLabel('label-cannot-detect-language') . " \"{$title}\" ");
				return false;
			}

			if ($installOnly && !$created) {
				return false;
			}

			if ($title) {
				$language->setTitle($title);
			}
			if ($isDefault !== null && $isDefault) {
				$collection->setDefault($languageId);
			}
			if ($prefix !== null) {
				$language->setPrefix($prefix);
			}

			$language->commit();

			if ($created) {
				$this->created_languages++;
				$this->writeLog(
					$this->getLabel('label-language') . ' "' . $language->getTitle() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-updated-m')
				);
			} elseif ($language->getIsUpdated()) {
				$this->updated_languages++;
				$this->writeLog(
					$this->getLabel('label-language') . ' "' . $language->getTitle() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-m')
				);
			}

			return $language;
		}

		/**
		 * Импортирует все домены
		 * @throws coreException
		 * @throws wrongParamException
		 */
		protected function importDomains() {
			$domains = $this->parser->evaluate('/umidump/domains/domain');
			foreach ($domains as $domainNode) {
				$this->importDomain($domainNode);
			}
		}

		/**
		 * Импортирует домен
		 * @param DOMElement $domainNode сущность домена
		 * @return iDomain|bool
		 * @throws coreException
		 * @throws wrongParamException
		 */
		protected function importDomain(DOMElement $domainNode) {
			$oldId = $domainNode->getAttribute('id');
			$host = $domainNode->hasAttribute('host') ? $domainNode->getAttribute('host') : null;

			if (!$oldId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-domain') . " \"{$host}\" " . $this->getLabel('label-with-empty-id')
				);
				return false;
			}

			$oldLangId = $domainNode->hasAttribute('lang-id') ? $domainNode->getAttribute('lang-id') : null;
			$isDefault = $domainNode->hasAttribute('is-default') ? $domainNode->getAttribute('is-default') : null;
			$usingSsl = $domainNode->hasAttribute('using-ssl') ? $domainNode->getAttribute('using-ssl') : null;
			$faviconPath = $domainNode->hasAttribute('icon-path') ? $domainNode->getAttribute('icon-path') : null;
			$favicon = ($faviconPath) ? Service::ImageFactory()->create($faviconPath) : null;

			$umiDomains = Service::DomainCollection();

			$created = false;
			$domainId = $this->relations->getNewDomainIdRelation($this->source_id, $oldId);
			if (!$domainId && $host) {
				$domainId = $umiDomains->getDomainId($host);
				if ($domainId) {
					$this->relations->setDomainIdRelation($this->source_id, $oldId, $domainId);
				}
			}

			if ($domainId && $this->update_ignore) {
				$this->writeLog(
					$this->getLabel('label-domain') . ' "' . $host . "\" (#{$oldId}) " .
					$this->getLabel('label-already-exists')
				);
				return $umiDomains->getDomain($domainId);
			}

			if (!$domainId) {
				if (!$host) {
					$host = $oldId;
				}

				$langId = false;
				if ($oldLangId !== null) {
					$langId = $this->relations->getNewLangIdRelation($this->source_id, $oldLangId);
				}

				if (!$langId) {
					$langId = Service::LanguageCollection()->getDefaultLang()->getId();
				}

				$domainId = $umiDomains->addDomain($host, $langId, $isDefault, $usingSsl);
				$this->relations->setDomainIdRelation($this->source_id, $oldId, $domainId);
				$created = true;
			}

			$domain = $umiDomains->getDomain($domainId);
			$installOnly = $domainNode->hasAttribute('install-only')
				? (bool) $domainNode->getAttribute('install-only')
				: false;

			if (!$domain instanceof iDomain) {
				$this->reportError($this->getLabel('label-cannot-detect-domain') . " \"{$host}\" ");
				return false;
			}

			if ($installOnly && !$created) {
				return false;
			}

			if ($isDefault !== null && $isDefault && !$umiDomains->getDefaultDomain()) {
				$umiDomains->setDefaultDomain($domainId);
			}

			if ($usingSsl !== null) {
				$domain->setUsingSsl($usingSsl);
			}

			$domain->setFavicon($favicon);

			if ($created) {
				$this->created_domains++;
				$this->writeLog(
					$this->getLabel('label-domain') . ' "' . $host . '" (#' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-m')
				);
			} elseif ($domain->getIsUpdated()) {
				$this->updated_domains++;
				$this->writeLog(
					$this->getLabel('label-domain') . ' "' . $host . '" (#' . $oldId . ') has been ' .
					$this->getLabel('label-has-been-updated-m')
				);
			}

			$domainMirrorNodeList = $domainNode->getElementsByTagName('domain-mirror');
			foreach ($domainMirrorNodeList as $domainMirrorNode) {
				$this->importDomainMirror($domainMirrorNode, $domain);
			}

			$domain->commit();
			return $domain;
		}

		/**
		 * Импортирует зеркало домена
		 * @param DOMElement $domainMirrorNode сущность зеркала домена
		 * @param iDomain $domain домен
		 * @return bool|domainMirror
		 * @throws coreException
		 */
		protected function importDomainMirror(DOMElement $domainMirrorNode, iDomain $domain) {
			$oldId = $domainMirrorNode->getAttribute('id');
			$host = $domainMirrorNode->hasAttribute('host') ? $domainMirrorNode->getAttribute('host') : null;

			if (!$oldId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-domain-mirror') . " \"{$host}\" " .
					$this->getLabel('label-with-empty-id')
				);
				return false;
			}

			$created = false;
			$mirrorId = $this->relations->getNewDomainMirrorIdRelation($this->source_id, $oldId);

			if (!$mirrorId) {
				if ($host !== null) {
					$mirrorId = $domain->getMirrorId($host);
					if ($mirrorId) {
						$this->relations->setDomainMirrorIdRelation($this->source_id, $oldId, $mirrorId);
					}
				}
			}

			if ($mirrorId && $this->update_ignore) {
				$this->writeLog(
					$this->getLabel('label-domain') . ' "' . $host . "\" (#{$oldId}) " .
					$this->getLabel('label-already-exists')
				);
				return $domain->getMirror($mirrorId);
			}

			if (!$mirrorId) {
				if ($host === null) {
					$host = $oldId;
				}

				$mirrorId = $domain->addMirror($host);
				$this->relations->setDomainMirrorIdRelation($this->source_id, $oldId, $mirrorId);
				$created = true;
			}

			$domainMirror = $domain->getMirror($mirrorId);
			if (!$domainMirror instanceof iDomainMirror) {
				$this->reportError($this->getLabel('label-cannot-detect-domain-mirror') . " \"{$host}\"");
				return false;
			}

			if ($created) {
				$this->created_domain_mirrors++;
				$this->writeLog(
					$this->getLabel('label-domain-mirror') . ' "' . $host . '" (#' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-n')
				);
			} elseif ($domainMirror->getIsUpdated()) {
				$this->updated_domain_mirrors++;
				$this->writeLog(
					$this->getLabel('label-domain-mirror') . ' "' . $host . '" (#' . $oldId . ') ' .
					$this->getLabel('label-has-been-updated-n')
				);
			}

			return $domainMirror;
		}

		/**
		 * Импортирует все шаблоны дизайна
		 * @throws coreException
		 */
		protected function importTemplates() {
			$templates = $this->parser->evaluate('/umidump/templates/template');
			foreach ($templates as $templateNode) {
				$this->importTemplate($templateNode);
			}
		}

		/**
		 * Импортирует шаблон дизайна
		 * @param DOMElement $templateNode сущность шаблона
		 * @return iTemplate|bool
		 * @throws coreException
		 */
		protected function importTemplate(DOMElement $templateNode) {
			$oldId = $templateNode->getAttribute('id');
			$title = $templateNode->hasAttribute('title') ? $templateNode->getAttribute('title') : null;

			if (!$oldId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-template') . " \"{$title}\" " .
					$this->getLabel('label-with-empty-id')
				);
				return false;
			}

			$collection = templatesCollection::getInstance();

			$filename = $templateNode->hasAttribute('filename') ? $templateNode->getAttribute('filename') : null;
			$oldDomainId = $templateNode->hasAttribute('domain-id') ? $templateNode->getAttribute('domain-id') : null;
			$oldLangId = $templateNode->hasAttribute('lang-id') ? $templateNode->getAttribute('lang-id') : null;
			$isDefault = $templateNode->hasAttribute('is-default') ? $templateNode->getAttribute('is-default') : null;
			$name = $templateNode->hasAttribute('name') ? $templateNode->getAttribute('name') : null;
			$type = $templateNode->hasAttribute('type') ? $templateNode->getAttribute('type') : null;

			$langId = false;
			$domainId = false;

			if ($oldLangId !== null) {
				$langId = $this->relations->getNewLangIdRelation($this->source_id, $oldLangId);
			}

			if ($oldDomainId !== null) {
				$domainId = $this->relations->getNewDomainIdRelation($this->source_id, $oldDomainId);
			}

			if (!$langId) {
				$langId = Service::LanguageCollection()->getDefaultLang()->getId();
			}

			if (!$domainId) {
				$domainId = Service::DomainCollection()->getDefaultDomain()->getId();
			}

			$domainId = $this->getForcedDomainId() ?: $domainId;

			$created = false;
			$templateId = $this->relations->getNewTemplateIdRelation($this->source_id, $oldId);

			if ($templateId && $this->update_ignore) {
				$this->writeLog(
					$this->getLabel('label-template') . ' "' . $title . "\" (#{$oldId}) " .
					$this->getLabel('label-already-exists')
				);
				return $collection->getTemplate($templateId);
			}

			if (!$templateId) {
				if (!$title) {
					$title = $oldId;
				}

				$templateId = $this->detectTemplateId($filename, $domainId, $langId);

				if (!$templateId) {
					$templateId = $collection->addTemplate($filename, $title, $domainId, $langId, $isDefault);
				}

				$this->relations->setTemplateIdRelation($this->source_id, $oldId, $templateId);
				$created = true;
			}

			$template = $collection->getTemplate($templateId);
			$installOnly = $templateNode->hasAttribute('install-only')
				? (bool) $templateNode->getAttribute('install-only')
				: false;

			if (!$template instanceof iTemplate) {
				$this->reportError($this->getLabel('label-cannot-detect-template') . "\"{$title}\"");
				return false;
			}

			if ($installOnly && !$created) {
				return false;
			}

			if ($isDefault !== null) {
				$template->setIsDefault($isDefault);
			}
			if ($name !== null) {
				$template->setName($this->getSolutionNameWithPostfix($name, $domainId));
			}
			if ($type !== null) {
				$template->setType($type);
			}

			$template->setLangId($langId);
			$template->setDomainId($domainId);

			if ($created) {
				$this->created_templates++;
				$this->writeLog(
					$this->getLabel('label-template') . ' "' . $title . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-m')
				);
			} elseif ($template->getIsUpdated()) {
				$this->updated_templates++;
				$this->writeLog(
					$this->getLabel('label-template') . ' "' . $title . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-updated-m')
				);
			}

			$template->commit();

			if ($isDefault) {
				$collection->setDefaultTemplate($template->getId(), $template->getDomainId(), $template->getLangId());
			}

			return $template;
		}

		/**
		 * Импортирует все ограничения на значение полей
		 * @throws coreException
		 */
		protected function importRestrictions() {
			$restrictions = $this->parser->evaluate('/umidump/restrictions/restriction');
			foreach ($restrictions as $restrictionNode) {
				$this->importRestriction($restrictionNode);
			}
		}

		/**
		 * Импортирует ограничение поля
		 * @param DOMElement $restrictionNode узел ограничения
		 * @return baseRestriction|bool
		 * @throws coreException
		 */
		protected function importRestriction(DOMElement $restrictionNode) {
			$oldId = $restrictionNode->getAttribute('id');
			$title = $restrictionNode->hasAttribute('title') ? $restrictionNode->getAttribute('title') : null;
			$prefix = $restrictionNode->hasAttribute('prefix') ? $restrictionNode->getAttribute('prefix') : null;
			$dataType = $restrictionNode->hasAttribute('field-type') ? $restrictionNode->getAttribute('field-type') : null;
			$multiple =
				$restrictionNode->getAttribute('is-multiple') == 1 ||
				$restrictionNode->getAttribute('is-multiple') == 'multiple';

			if (!$oldId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-restriction') . " \"{$title}\" " .
					$this->getLabel('label-with-empty-id')
				);
				return false;
			}

			$collection = umiFieldTypesCollection::getInstance();
			$fieldType = $collection->getFieldTypeByDataType($dataType, $multiple);

			$typeId = $fieldType->getId();
			$created = false;
			$restrictionId = false;
			if (!$title) {
				$title = $oldId;
			}

			if (baseRestriction::find($prefix, $typeId)) {
				$restrictionId = baseRestriction::find($prefix, $typeId)->getId();
				if ($restrictionId != $this->relations->getNewRestrictionIdRelation($this->source_id, $oldId)) {
					$this->relations->setRestrictionIdRelation($this->source_id, $oldId, $restrictionId);
				}
			}

			if (!$restrictionId) {
				$restrictionId = $this->relations->getNewRestrictionIdRelation($this->source_id, $oldId);
			}

			if (!$restrictionId) {
				$restrictionId = baseRestriction::add($prefix, $title, $typeId);
				$this->relations->setRestrictionIdRelation($this->source_id, $oldId, $restrictionId);
				$created = true;
			}

			$restriction = baseRestriction::get($restrictionId);
			$installOnly =
				$restrictionNode->hasAttribute('install-only') ? (bool) $restrictionNode->getAttribute('install-only') : false;

			if (!$restriction instanceof baseRestriction) {
				$this->reportError($this->getLabel('label-cannot-detect-restriction') . " \"{$title}\"");
				return false;
			}

			if ($installOnly && !$created) {
				return false;
			}

			if ($created) {
				$this->created_restrictions++;
				$this->writeLog(
					$this->getLabel('label-restriction') . ' "' . $restriction->getTitle() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-n')
				);
			}

			$fieldNodeList = $restrictionNode->getElementsByTagName('field');

			/** @var DOMElement $fieldNode */
			foreach ($fieldNodeList as $fieldNode) {
				$oldFieldName = $fieldNode->getAttribute('field-name');
				$oldObjectTypeId = $fieldNode->getAttribute('type-id');
				$objectTypeId = $this->relations->getNewTypeIdRelation($this->source_id, $oldObjectTypeId);

				if (!$objectTypeId) {
					continue;
				}

				$type = umiObjectTypesCollection::getInstance()
					->getType($objectTypeId);
				$fieldId = $type->getFieldId(self::translateName($oldFieldName), false);

				if (!$fieldId) {
					$fieldId = $type->getFieldId($oldFieldName, false);
				}

				$field = umiFieldsCollection::getInstance()->getField($fieldId);
				if (!$field instanceof iUmiField) {
					$this->reportError(
						$this->getLabel('label-cannot-set-restriction-for-field') . " \"{$oldFieldName}\": " .
						$this->getLabel('label-cannot-detect-field')
					);
					continue;
				} elseif($type->getIsLocked() && $this->getSourceName() !== 'system') {
					continue;
				}

				$field->setRestrictionId($restrictionId);
				$field->setIsUpdated();
				$this->writeLog(
					$this->getLabel('label-restriction') . ' "' . $restriction->getTitle() . '" ' .
					$this->getLabel('label-has-been-set-for-field') . " \"{$oldFieldName}\""
				);
			}

			return $restriction;
		}

		/** Импортирует все ключи реестра */
		protected function importRegistry() {
			$keys = $this->parser->evaluate('/umidump/registry/key');
			foreach ($keys as $keyNode) {
				$this->importReg($keyNode);
			}
		}

		/**
		 * Импортирует ключ реестра
		 * @param DOMElement $keyNode узел ключа реестра
		 * @return bool
		 */
		protected function importReg(DOMElement $keyNode) {
			$path = $keyNode->hasAttribute('path') ? $keyNode->getAttribute('path') : null;
			if (!$path) {
				$this->reportError($this->getLabel('label-cannot-create-registry-item-with-empty-path'));
				return false;
			}

			$value = $keyNode->hasAttribute('val') ? $keyNode->getAttribute('val') : null;
			$needUpdate = $keyNode->hasAttribute('update');

			$created = false;
			$updated = false;
			$umiRegistry = Service::Registry();

			if (!$umiRegistry->contains($path)) {
				$umiRegistry->set($path, $value);
				$created = true;
			} elseif ($needUpdate) {
				$umiRegistry->set($path, $value);
				$updated = true;
			}

			if ($created) {
				$this->created_registry_items++;
				$this->writeLog(
					$this->getLabel('label-registry-item') . ' "' . $path . '" (' . $value . ') ' .
					$this->getLabel('label-has-been-created-f')
				);
			} elseif ($updated) {
				$this->writeLog('Registry item "' . $path . '" (' . $value . ') has been updated');
			}
		}

		/**
		 * Импортирует ссылку на справочник
		 * @param iUmiField $field поле
		 * @param DOMElement $relationNode узел связи
		 * @param iUmiObjectType $type тип данных
		 * @return bool
		 */
		protected function importTypeRelation(iUmiField $field, DOMElement $relationNode, iUmiObjectType $type) {
			$oldGuideIds = $relationNode->getElementsByTagName('guide');
			$oldGuideId = $oldGuideIds->length ? $oldGuideIds->item(0)->getAttribute('id') : false;

			$guideId = $this->relations->getNewTypeIdRelation($this->source_id, $oldGuideId);
			if (!$guideId) {
				return false;
			}

			if ($field->getGuideId() != $guideId) {
				$field->setGuideId($guideId);
				$this->updated_relations++;
				$this->writeLog(
					$this->getLabel('label-relation') . ': ' . $this->getLabel('label-datatype') .
					' (' . $type->getName() . ') - ' . $this->getLabel('label-field') . ' (' .
					$field->getName() . ') - ' . $this->getLabel('label-guide') .
					" ({$guideId}) " . $this->getLabel('label-has-been-created-f')
				);
				$field->commit();
			}

			return true;
		}

		/**
		 * Импортирует ссылку на элемент (из поля 'symlink')
		 * @param iUmiField $field поле
		 * @param DOMElement $relationNode узел связи
		 * @param iUmiObject|iUmiHierarchyElement $entity сущность
		 * @return bool
		 */
		protected function importEntityRelation(iUmiField $field, DOMElement $relationNode, $entity) {
			$fieldName = $field->getName();
			$objectIdList = [];

			/** @var DOMElement $objectNode */
			foreach ($relationNode->getElementsByTagName('object') as $objectNode) {
				$extObjectId = $objectNode->getAttribute('id');
				$objectId = (int) $this->relations->getNewObjectIdRelation($this->source_id, $extObjectId);
				if ($objectId) {
					$objectIdList[] = $objectId;
				}
			}

			$pageIdList = [];

			/** @var DOMElement $pageNode */
			foreach ($relationNode->getElementsByTagName('page') as $pageNode) {
				$extPageId = $pageNode->getAttribute('id');
				$pageId = (int) $this->relations->getNewIdRelation($this->source_id, $extPageId);
				if ($pageId) {
					$pageIdList[] = $pageId;
				}
			}

			$offerExternalIdList = [];

			/** @var DOMElement $offerNode */
			foreach ($relationNode->getElementsByTagName('offer') as $offerNode) {
				$offerExternalIdList[] = $offerNode->getAttribute('id');
			}

			$offerInternalIdList = [];

			try {
				/** @var iExchange $exchangeService */
				$exchangeService = Service::get('TradeOfferExchange');
				$offerInternalIdList = $exchangeService->getInternalIdList($offerExternalIdList, $this->createEntityImporter());
			} catch (Exception $exception) {
				$this->reportError($exception->getMessage());
			}

			$updated = false;
			$entityId = $entity->getId();
			/** @var iUmiObject $object */
			$object = ($entity instanceof iUmiHierarchyElement) ? $entity->getObject() : $entity;
			$property = $object->getPropByName($fieldName);
			$multiple = $property->getIsMultiple();

			$idList = [];

			switch ($field->getDataType()) {
				case 'relation' : {
					$idList = $objectIdList;
					break;
				}
				case 'symlink' : {
					$idList = $pageIdList;
					break;
				}
				case 'offer_id':
				case 'offer_id_list' : {
					$idList = $offerInternalIdList;
					break;
				}
			}

			if (count($idList) > 0) {
				$value = $multiple ? $idList : getFirstValue($idList);
				$entity->setValue($fieldName, $value);
				$this->updated_relations++;
				$updated = true;
			} else {
				$emptyValue = $multiple ? [] : null;
				$entity->setValue($fieldName, $emptyValue);
			}

			if ($updated) {
				if ($entity instanceof iUmiObject) {
					$this->writeLog(
						$this->getLabel('label-values-for-field') . " ({$fieldName}) " .
						$this->getLabel('label-of-object') .
						" ({$entityId}) " . $this->getLabel('label-have-been-updated')
					);
				} else {
					$this->writeLog(
						$this->getLabel('label-values-for-field') . " ({$fieldName}) " . $this->getLabel('label-of-page') .
						" ({$entityId}) " . $this->getLabel('label-have-been-updated')
					);
				}
			}

			$entity->commit();

			if ($entity instanceof iUmiObject) {
				umiObjectsCollection::getInstance()->unloadObject($entityId);
			} else {
				umiHierarchy::getInstance()->unloadElement($entityId);
			}

			return true;
		}

		/**
		 * Импортирует все элементы справочников или ссылки на дерево
		 * @throws coreException
		 */
		protected function importRelations() {
			$relations = $this->parser->evaluate('/umidump/relations/relation');
			foreach ($relations as $relationNode) {
				$this->importRelation($relationNode);
			}
		}

		/**
		 * Импортирует элемент справочника или ссылку на дерево
		 * @param DOMElement $relationNode сущность связи
		 * @return bool
		 * @throws coreException
		 */
		protected function importRelation(DOMElement $relationNode) {
			$oldTypeId = $relationNode->hasAttribute('type-id') ? $relationNode->getAttribute('type-id') : null;
			$oldPageId = $relationNode->hasAttribute('page-id') ? $relationNode->getAttribute('page-id') : null;
			$oldObjectId = $relationNode->hasAttribute('object-id') ? $relationNode->getAttribute('object-id') : null;
			$oldFieldName = $relationNode->hasAttribute('field-name') ? $relationNode->getAttribute('field-name') : null;

			if (!$oldTypeId && !$oldPageId && !$oldObjectId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-relation-for-field') . " \"{$oldFieldName}\":" .
					$this->getLabel('label-cannot-detect-entity')
				);
				return false;
			}

			$typeId = null;
			$entity = null;

			if ($oldTypeId !== null) {
				$typeId = $this->relations->getNewTypeIdRelation($this->source_id, $oldTypeId);
				$entity = umiObjectTypesCollection::getInstance()->getType($typeId);
			} elseif ($oldPageId !== null) {
				$pageId = $this->relations->getNewIdRelation($this->source_id, $oldPageId);
				$entity = umiHierarchy::getInstance()->getElement($pageId, true, true);
			} elseif ($oldObjectId !== null) {
				$objectId = $this->relations->getNewObjectIdRelation($this->source_id, $oldObjectId);
				$entity = umiObjectsCollection::getInstance()->getObject($objectId);
			}

			if (!$entity) {
				$oldEntityId = ($oldTypeId !== null) ? $oldTypeId : (($oldPageId !== null) ? $oldPageId : $oldObjectId);
				$this->reportError(
					$this->getLabel('label-cannot-create-relation-for-field') . " \"{$oldFieldName}\": " .
					$this->getLabel('label-cannot-detect-entity') . " ({$oldEntityId})"
				);
				return false;
			}

			if ($entity instanceof iUmiHierarchyElement) {
				$typeId = $entity->getObjectTypeId();
			} elseif ($entity instanceof iUmiObject) {
				$typeId = $entity->getTypeId();
			}

			$type = umiObjectTypesCollection::getInstance()->getType($typeId);

			$fieldId = $type->getFieldId(self::translateName($oldFieldName), false);
			if (!$fieldId) {
				$fieldId = $type->getFieldId($oldFieldName, false);
			}

			if (!$fieldId) {
				return false;
			}

			$field = umiFieldsCollection::getInstance()->getField($fieldId);

			if ($entity instanceof iUmiObjectType) {
				return $this->importTypeRelation($field, $relationNode, $entity);
			}

			return $this->importEntityRelation($field, $relationNode, $entity);
		}

		/** Импортирует все права пользователей */
		protected function importPermissions() {
			$permissions = $this->parser->evaluate('/umidump/permissions/permission');
			foreach ($permissions as $permissionNode) {
				$this->importPermission($permissionNode);
			}
		}

		/**
		 * Импортирует право
		 * @param DOMElement $permissionNode сущность права
		 * @return bool
		 */
		protected function importPermission(DOMElement $permissionNode) {
			$oldPageId = $permissionNode->hasAttribute('page-id') ? $permissionNode->getAttribute('page-id') : null;
			$oldObjectId = $permissionNode->hasAttribute('object-id') ? $permissionNode->getAttribute('object-id') : null;

			if (!$oldPageId && !$oldObjectId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-permission') . ': ' . $this->getLabel('label-cannot-detect-entity')
				);
				return false;
			}

			$permissions = permissionsCollection::getInstance();
			$entity = null;

			if ($oldPageId !== null) {
				$pageId = $this->relations->getNewIdRelation($this->source_id, $oldPageId);
				$entity = umiHierarchy::getInstance()->getElement($pageId, true, true);
			} elseif ($oldObjectId !== null) {
				$objectId = $this->relations->getNewObjectIdRelation($this->source_id, $oldObjectId);
				$entity = umiObjectsCollection::getInstance()->getObject($objectId);
			}

			if (!$entity) {
				$oldEntityId = ($oldPageId !== null) ? $oldPageId : $oldObjectId;
				$this->reportError(
					$this->getLabel('label-cannot-create-permission') . ": " . $this->getLabel('label-cannot-detect-entity') . " ({$oldEntityId})"
				);
				return false;
			}

			$entityId = $entity->getId();
			$ownerNodeList = $permissionNode->getElementsByTagName('owner');

			/** @var DOMElement $ownerNode */
			foreach ($ownerNodeList as $ownerNode) {
				$oldOwnerId = $ownerNode->getAttribute('id');
				$ownerId = (int) $this->relations->getNewObjectIdRelation($this->source_id, $oldOwnerId);
				$level = $ownerNode->hasAttribute('level') ? $ownerNode->getAttribute('level') : null;

				if ($level !== null) {
					$permissions->setElementPermissions($ownerId, $entityId, $level);
					$this->created_permissions++;
				} else {
					$entity->setOwnerId($ownerId);
					$entity->setIsUpdated();
					$entity->commit();
					$this->writeLog(
						$this->getLabel('label-owner-for-entity') . ' (' . $entityId . ') ' .
						$this->getLabel('label-has-been-updated-m')
					);
					$this->created_permissions++;
				}
			}

			$moduleNodeList = $permissionNode->getElementsByTagName('module');
			if ($moduleNodeList->length == 0) {
				return;
			}

			$hasUserModulesPermission = $permissions->hasUserModulesPermissions($entityId);

			/** @var DOMElement $module */
			foreach ($moduleNodeList as $module) {
				if ($module->hasAttribute('install-only') && $hasUserModulesPermission) {
					continue;
				}

				$moduleName = $module->getAttribute('name');
				$method = $module->getAttribute('method');

				if ($method) {
					if (!$permissions->isAllowedMethod($entityId, $moduleName, $method)) {
						$permissions->setModulesPermissions($entityId, $moduleName, $method);
						$this->writeLog(
							$this->getLabel('label-permissions-for') . ' ' . $this->getLabel('label-module') .
							" \"{$moduleName}\" - " . $this->getLabel('label-method') . " \"{$method}\" " .
							$this->getLabel('label-of-object') . ' (' . $entityId . ') ' .
							$this->getLabel('label-have-been-updated')
						);
						$this->created_permissions++;
					}
				} else {
					if (!$permissions->isAllowedModule($entityId, $moduleName)) {
						$permissions->setModulesPermissions($entityId, $moduleName);
						$this->writeLog(
							$this->getLabel('label-permissions-for') . ' ' . $this->getLabel('label-module') .
							" \"{$moduleName}\" " .
							$this->getLabel('label-of-object') . ' (' . $entityId . ') ' .
							$this->getLabel('label-have-been-updated')
						);
						$this->created_permissions++;
					}
				}
			}
		}

		/**
		 * Импортирует все опционные свойства
		 * @throws coreException
		 */
		protected function importOptions() {
			$options = $this->parser->evaluate('/umidump/options/entity');
			foreach ($options as $entityNode) {
				$this->importOption($entityNode);
			}
		}

		/**
		 * Импортирует опционное свойство
		 * @param DOMElement $entityNode сущность опционного свойства
		 * @return bool
		 * @throws coreException
		 */
		protected function importOption(DOMElement $entityNode) {
			$oldPageId = $entityNode->hasAttribute('page-id') ? $entityNode->getAttribute('page-id') : null;
			$oldObjectId = $entityNode->hasAttribute('object-id') ? $entityNode->getAttribute('object-id') : null;
			$oldFieldName = $entityNode->hasAttribute('field-name') ? $entityNode->getAttribute('field-name') : null;

			if (!$oldPageId && !$oldObjectId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-options-for-field') . " {$oldFieldName} " .
					$this->getLabel('label-cannot-detect-entity')
				);
				return false;
			}

			$entity = null;

			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjects = umiObjectsCollection::getInstance();

			if ($oldPageId !== null) {
				$pageId = $this->relations->getNewIdRelation($this->source_id, $oldPageId);
				$entity = $umiHierarchy->getElement($pageId, true, true);
			} elseif ($oldObjectId !== null) {
				$objectId = $this->relations->getNewObjectIdRelation($this->source_id, $oldObjectId);
				$entity = $umiObjects->getObject($objectId);
			}

			if (!$entity) {
				$this->reportError(
					$this->getLabel('label-cannot-create-options-for-field') . " {$oldFieldName} " .
					$this->getLabel('label-cannot-detect-entity')
				);
				return false;
			}

			if ($entity instanceof iUmiHierarchyElement) {
				$typeId = $entity->getObjectTypeId();
			} elseif ($entity instanceof iUmiObject) {
				$typeId = $entity->getTypeId();
			}

			$type = umiObjectTypesCollection::getInstance()->getType($typeId);

			$fieldId = $type->getFieldId(self::translateName($oldFieldName), false);
			if (!$fieldId) {
				$fieldId = $type->getFieldId($oldFieldName, false);
			}

			if (!$fieldId) {
				$this->reportError(
					$this->getLabel('label-cannot-create-options-for-field')
					. " {$oldFieldName} "
					. $this->getLabel('label-cannot-detect-field')
					. ". "
					. sprintf($this->getLabel('page-string-format'), $entity->getName())
					. "({$entity->getId()})"
				);
				return false;
			}

			$field = umiFieldsCollection::getInstance()->getField($fieldId);
			$fieldName = $field->getName();
			$entity->setValue($fieldName, $this->getOptionValue($entityNode));
			$entity->commit();
		}

		/**
		 * Возвращает значение опционного свойства
		 * @param DOMElement $entityNode сущность опционного свойства
		 * @return array
		 */
		private function getOptionValue(DOMElement $entityNode) {
			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjects = umiObjectsCollection::getInstance();
			$valueList = [];

			/** @var DOMElement $optionNode */
			foreach ($this->parser->evaluate('option', $entityNode) as $optionNode) {
				if (!$optionNode->hasAttributes()) {
					continue;
				}

				$value = [];
				foreach ($optionNode->attributes as $attribute) {
					if ($attribute->name == 'object-id') {
						$object = $umiObjects->getObject($attribute->value);

						if (!$object instanceof iUmiObject) {
							$objectId = $this->relations->getNewObjectIdRelation($this->source_id, $attribute->value);
							$object = $umiObjects->getObject($objectId);
						}

						if (!$object instanceof iUmiObject) {
							continue;
						}

						$value['rel'] = $object;
					} elseif ($attribute->name == 'page-id') {
						$page = $umiHierarchy->getElement($attribute->value, true, true);

						if (!$page instanceof iUmiHierarchyElement) {
							$pageId = $this->relations->getNewIdRelation($this->source_id, $attribute->value);
							$page = $umiHierarchy->getElement($pageId, true, true);
						}

						if (!$page instanceof iUmiHierarchyElement) {
							continue;
						}

						$value['tree'] = $page->getId();
					} else {
						$value[$attribute->name] = $attribute->value;
					}
				}

				$valueList[] = $value;
			}

			return $valueList;
		}

		/** Импортирует все файлы */
		protected function importFiles() {
			$files = $this->parser->evaluate('/umidump/files/file');
			foreach ($files as $fileNode) {
				$this->importFile($fileNode);
			}
		}

		/**
		 * Импортирует файл
		 * @param DOMElement $fileNode узел файла
		 * @return bool
		 */
		protected function importFile(DOMElement $fileNode) {
			$filename = $fileNode->hasAttribute('name') ? $fileNode->getAttribute('name') : null;
			$oldHash = $fileNode->hasAttribute('hash') ? $fileNode->getAttribute('hash') : null;

			$localPath = $fileNode->nodeValue;
			if (!$localPath) {
				$this->reportError($this->getLabel('label-cannot-create-file-with-empty-path'));
				return false;
			}

			$destinationPath = $this->getDestinationPath($localPath);
			$destinationPathFolder = dirname($destinationPath);

			if (!file_exists($destinationPathFolder)) {
				mkdir($destinationPathFolder, 0777, true);
			}

			$sourcePath = $this->filesSource . $fileNode->nodeValue;

			if (!file_exists($sourcePath)) {
				$this->reportError(
					$this->getLabel('label-file') . " {$filename} " . $this->getLabel('label-does-not-exist')
				);
				return false;
			}

			if (copy($sourcePath, $destinationPath)) {
				$newHash = md5_file($destinationPath);

				if ($oldHash != $newHash) {
					$this->reportError(
						$this->getLabel('label-file') . " {$filename} " . $this->getLabel('label-is-broken')
					);
				} else {
					if (defined('PHP_FILES_ACCESS_MODE') && mb_strtolower(mb_substr($destinationPath, -4, 4)) === '.php') {
						chmod($destinationPath, PHP_FILES_ACCESS_MODE);
					}

					$this->copied_files++;
					$this->writeLog(
						$this->getLabel('label-file') . ' "' . $localPath . '" ' .
						$this->getLabel('label-has-been-copied-m')
					);
				}
			} else {
				$this->reportError($this->getLabel('label-cannot-copy-file') . " \"{$filename}\"");
			}
		}

		/** Импортирует все директории */
		protected function importDirs() {
			$directories = $this->parser->evaluate('/umidump/directories/directory');
			foreach ($directories as $directoryNode) {
				$this->importDir($directoryNode);
			}
		}

		/**
		 * Импортирует директорию
		 * @param DOMElement $directoryNode узел директории
		 * @return bool
		 */
		protected function importDir(DOMElement $directoryNode) {
			$name = $directoryNode->hasAttribute('name') ? $directoryNode->getAttribute('name') : null;
			$path = $directoryNode->hasAttribute('path') ? $directoryNode->getAttribute('path') : null;

			if ($path === null) {
				$this->reportError($this->getLabel('label-cannot-create-folder-with-empty-path'));
				return false;
			}

			$localPath = $directoryNode->nodeValue;
			$destinationPath = $this->getDestinationPath($localPath);

			if (!file_exists($destinationPath)) {
				mkdir($destinationPath, 0777, true);
				$this->created_dirs++;
				$this->writeLog(
					$this->getLabel('label-folder') . ' "' . $localPath . '" ' .
					$this->getLabel('label-has-been-created-f')
				);
			}
		}

		/**
		 * Импортирует все объекты
		 * @throws baseException
		 * @throws coreException
		 */
		protected function importObjects() {
			$objects = $this->parser->evaluate('/umidump/objects/object');

			foreach ($objects as $objectNode) {
				$this->importObject($objectNode);
			}

			try {
				Service::CurrencyFacade()->reload();
			} catch (Exception $exception) {
				umiExceptionHandler::report($exception);
			}
		}

		/**
		 * Импортирует объект
		 * @param DOMElement $objectNode сущность объекта
		 * @return bool|umiObject
		 * @throws baseException
		 * @throws coreException
		 */
		protected function importObject(DOMElement $objectNode) {
			$oldId = $objectNode->getAttribute('id');
			$guid = $objectNode->hasAttribute('guid') ? $objectNode->getAttribute('guid') : null;
			$name = $objectNode->hasAttribute('name') ? $objectNode->getAttribute('name') : null;

			$oldTypeId = $objectNode->getAttribute('type-id');
			$updateOnly = $objectNode->getAttribute('update-only') == '1';
			$isLocked = $objectNode->getAttribute('locked');

			$umiObjects = umiObjectsCollection::getInstance();
			$umiObjectTypes = umiObjectTypesCollection::getInstance();

			$created = false;
			$objectId = false;

			if ($guid !== null) {
				$objectId = $umiObjects->getObjectIdByGUID($guid);
				if ($objectId && $objectId != $this->relations->getNewObjectIdRelation($this->source_id, $oldId)) {
					$this->relations->setObjectIdRelation($this->source_id, $oldId, $objectId);
				}
			}

			if (!$objectId) {
				$objectId = $oldId ? $this->relations->getNewObjectIdRelation($this->source_id, $oldId) : false;
			}

			if ($objectId && $this->update_ignore) {
				$this->writeLog(
					$this->getLabel('label-object') . ' "' . $name . "\" (#{$oldId}) " .
					$this->getLabel('label-already-exists')
				);
				return $umiObjects->getObject($objectId);
			}

			if (!$objectId) {
				if ($updateOnly) {
					return false;
				}

				if (!$name) {
					$name = $oldId;
				}

				if (!$oldTypeId) {
					$this->reportError(
						$this->getLabel('label-cannot-create-object') . " \"{$name}\" (#{$oldId}): " .
						$this->getLabel('label-cannot-detect-type')
					);
					return false;
				}

				$typeId = $this->relations->getNewTypeIdRelation($this->source_id, $oldTypeId);
				$type = $umiObjectTypes->getType($typeId);
				if (!$type instanceof iUmiObjectType) {
					$this->reportError(
						$this->getLabel('label-cannot-create-object') . " \"{$name}\" (#{$oldId}): " .
						$this->getLabel('label-cannot-detect-type') . " #{$oldTypeId}"
					);
					return false;
				}

				if ($this->isDemoSiteMode()) {
					$hierarchyTypeId = $type->getHierarchyTypeId();
					if ($hierarchyTypeId) {
						$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
						if ($hierarchyType instanceof iUmiHierarchyType) {
							$module = $hierarchyType->getModule();
							if (!Service::Registry()->get("//modules/{$module}")) {
								return false;
							}
						}
					}
				}

				$event = new umiEventPoint('exchangeOnAddObject');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('before');
				$event->setParam('old_object_id', $oldId);
				$event->setParam('object_info', $objectNode);
				$event->setParam('type', $type);
				$event->addRef('old_object_id', $oldId);
				$event->addRef('name', $name);
				$event->addRef('type_id', $typeId);
				$event->addRef('is_locked', $isLocked);
				$event->addRef('object_info', $objectNode);
				$this->callEvent($event);

				$objectId = $umiObjects->addObject($name, $typeId, $isLocked == 'locked' || $isLocked == '1');
				$this->relations->setObjectIdRelation($this->source_id, $oldId ?: $objectId, $objectId);
				$created = true;
			}

			$object = $umiObjects->getObject($objectId);
			$installOnly = $objectNode->hasAttribute('install-only')
				? (bool) $objectNode->getAttribute('install-only')
				: false;

			if (!$object instanceof iUmiObject) {
				return false;
			}

			if ($installOnly && !$created) {
				return false;
			}

			if (!$created) {
				$event = new umiEventPoint('exchangeOnUpdateObject');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('before');
				$event->addRef('object', $object);
				$event->setParam('object_info', $objectNode);
				$event->addRef('guid', $guid);
				$event->addRef('name', $name);
				$event->addRef('object_info', $objectNode);
				$this->callEvent($event);
			}

			if ($guid !== null) {
				$object->setGUID($guid);
			}

			if ($name !== null) {
				$object->setName($name);
			}

			$this->importPropValues($object, $objectNode, $created);

			if ($created) {
				$this->created_objects++;
				$this->writeLog(
					$this->getLabel('label-object') . ' "' . $object->getName() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-created-m')
				);
			} elseif ($object->getIsUpdated()) {
				$this->updated_objects++;
				$this->writeLog(
					$this->getLabel('label-object') . ' "' . $object->getName() . '" (' . $oldId . ') ' .
					$this->getLabel('label-has-been-updated-m')
				);
			}

			if ($created) {
				$event = new umiEventPoint('exchangeOnAddObject');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('after');
				$event->addRef('object', $object);
				$event->setParam('object_info', $objectNode);
				$this->callEvent($event);
			} else {
				$event = new umiEventPoint('exchangeOnUpdateObject');
				$event->setParam('source_id', $this->source_id);
				$event->setMode('after');
				$event->addRef('object', $object);
				$event->setParam('object_info', $objectNode);
				$this->callEvent($event);
			}

			$object->commit();
			$umiObjects->unloadObject($objectId);
			return $object;
		}

		/**
		 * Вызывает событие
		 * @param iUmiEventPoint $event событие
		 * @throws baseException
		 */
		private function callEvent(iUmiEventPoint $event) {
			if ($this->isEventsEnabled()) {
				umiEventsController::getInstance()
					->callEvent($event);
			}
		}

		/**
		 * Определяет включена ли отправка событий
		 * @return bool
		 */
		private function isEventsEnabled() {
			return $this->eventsEnabled;
		}

		/**
		 * Приводит название к нормальному виду
		 * @param string $name название
		 * @return string
		 */
		protected static function translateName($name) {
			$name = umiHierarchy::convertAltName($name, '_');
			$name = umiObjectProperty::filterInputString($name);
			if (!$name) {
				$name = '_';
			}
			$name = mb_substr($name, 0, 64);
			return $name;
		}

		/**
		 * Импортирует свойства сущности (страницы или объекта)
		 * @param iUmiEntinty $entity сущность
		 * @param DOMElement $entityNode узел сущности
		 * @param bool $isNewEntity флаг того, что это новая сущность
		 */
		protected function importPropValues(iUmiEntinty $entity, DOMElement $entityNode, $isNewEntity = false) {
			$properties = $this->parser->evaluate('properties/group/property', $entityNode);
			foreach ($properties as $propertyNode) {
				try {
					$this->importPropValue($entity, $propertyNode, $isNewEntity);
				} catch (Exception $e) {
					$this->writeLog(
						"Can't set value #" . $entity->getId() . '.' . $entityNode->getAttribute('name') . ': ' .
						$e->getMessage()
					);
				}
			}
		}

		/**
		 * Импортирует свойство сущности (страницы или объекта).
		 * Поддерживает все типы полей, кроме 'optioned' и 'symlink'
		 * (эти типы импортируются в методе @see xmlImporter::importRelations() )
		 *
		 * @param iUmiHierarchyElement|iUmiObject сущность
		 * @param DOMElement $propertyNode узел свойства
		 * @param bool $isNewEntity флаг того, что это новая сущность
		 * @return bool
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws publicException
		 * @throws wrongParamException
		 * @throws baseException
		 */
		protected function importPropValue($entity, DOMElement $propertyNode, $isNewEntity = false) {
			$oldName = $propertyNode->getAttribute('name');
			$name = self::translateName($oldName);
			if ($entity instanceof iUmiHierarchyElement) {
				$typeId = $entity->getObjectTypeId();
			} else {
				/** @var iUmiObject $entity */
				$typeId = $entity->getTypeId();
			}

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$type = $umiObjectTypes->getType($typeId);
			$fieldId = $type->getFieldId($name, false);
			$field = umiFieldsCollection::getInstance()->getField($fieldId);

			/** @var DOMNodeList $valueNodeList */
			$valueNodeList = $this->parser->evaluate('value', $propertyNode);
			if (!$valueNodeList->length && $isNewEntity) {
				$valueNodeList = $this->parser->evaluate('default-value', $propertyNode);
			}

			if (!$valueNodeList->length) {
				if ($isNewEntity && ($field instanceof iUmiField && !in_array($field->getDataType(), ['optioned', 'symlink']))) {
					$this->reportError(
						$this->getLabel('label-property') . " \"{$name}\" " . $this->getLabel('label-has-no-values')
					);
				}
				return false;
			}

			/** @var DOMElement $valueNode */
			$valueNode = $valueNodeList->item(0);
			$this->executeDetectFieldEvent($propertyNode, $typeId, $field);

			$event = new umiEventPoint('importPropertyValue');
			$event->setParam('propertyNode', $propertyNode);
			$event->setParam('propertyType', $type);
			$event->setParam('field', $field);
			$event->addRef('name', $name);
			$this->callEvent($event);

			if (!$field instanceof iUmiField && $propertyNode->getAttribute('allow-runtime-add') == '1') {
				$groupInfo = $propertyNode->parentNode;
				$groupIdList = $this->importTypeGroup($type, $groupInfo, false);

				if (empty($groupIdList)) {
					return false;
				}

				foreach ($groupIdList as $groupTypeId => $groupId) {
					$groupType = $umiObjectTypes->getType($groupTypeId);
					if (!$groupType instanceof iUmiObjectType) {
						continue;
					}

					$includeInactiveGroups = true;
					$group = $groupType->getFieldsGroup($groupId, $includeInactiveGroups);

					if (!$group instanceof iUmiFieldsGroup) {
						$umiObjectTypes->unloadType($groupTypeId);
						continue;
					}

					$field = $this->importField($group, $propertyNode, $groupType);
					$umiObjectTypes->unloadType($groupTypeId);
				}

				if ($entity->getIsUpdated()) {
					$entity->commit();
				}

				if ($entity instanceof iUmiHierarchyElement) {
					$entity->getObject()->update();
				} else {
					$entity->update();
				}
			}

			if (!$field instanceof iUmiField) {
				return false;
			}

			switch ($field->getDataType()) {
				case 'offer_id':
				case 'offer_id_list':
				case 'optioned':
				case 'symlink': {
					return false;
				}

				case 'date': {
					$timestamp = (int) $valueNode->getAttribute('unix-timestamp');
					$date = new umiDate();

					if ($timestamp) {
						$date->setDateByTimeStamp($timestamp);
					} elseif ($valueNode->nodeValue) {
						$date->setDateByString($valueNode->nodeValue);
					} else {
						$date = null;
					}

					$entity->setValue($name, $date);
					break;
				}

				case 'price': {
					$this->importPriceValue($entity, $name, $valueNode);
					break;
				}

				case 'file':
				case 'img_file':
				case 'video_file':
				case 'swf_file': {
					if ($this->renameFiles) {
						$oldFileName = false;
						$oldFile = $entity->getValue($name);
						if ($oldFile instanceof umiFile) {
							$oldFileName = $oldFile->getFilePath();
						}

						$origFilePath = ltrim(trim($valueNode->nodeValue, "\r\n"), '.');

						$filename = basename($origFilePath);
						$dir = dirname($origFilePath);

						$event = new umiEventPoint('renameFilesForImportImageProperty');
						$event->setParam('directory', $dir);
						$event->setParam('originalFilePath', $origFilePath);
						$event->addRef('oldFileName', $oldFileName);
						$event->setParam('parser', $this->parser);
						$event->setParam('propertyNode', $propertyNode);
						$this->callEvent($event);

						$ext = explode('.', $filename);
						$ext = end($ext);

						$filename_translit = translit::convert(trim($entity->getName(), "\r\n"));
						$filename = $filename_translit;
						$isFileNameChanged = false;

						$count = 0;
						$oldErrorReporting = error_reporting(0);
						$isOriginalFileExists = file_exists($this->getRootDirPath() . '/' . $origFilePath);

						while (true) {

							if (!$isOriginalFileExists && !$isFileNameChanged) {
								break 2;
							}

							if ($oldFileName) {
								$oldFilePath = $this->getRootDirPath() . ltrim($oldFileName, '.');

								if (file_exists($oldFilePath)) {
									unlink($oldFilePath);
								}
							}

							if (!file_exists($this->getRootDirPath() . '/' . $dir . '/' . $filename . '.' . $ext)) {
								break;
							}

							$count++;
							$filename = $filename_translit . '_' . $fieldId .  '_' . $count;
							$isFileNameChanged = true;
						}

						$filename .= '.' . $ext;
						$origFilePath = $this->getRootDirPath() . '/' . $origFilePath;
						$newFilePath = $this->getRootDirPath() . '/' . $dir . '/' . $filename;

						if (file_exists($origFilePath)) {
							rename($origFilePath, $newFilePath);
						} else {
							file_put_contents($newFilePath, $oldFile->getContent());
						}

						error_reporting($oldErrorReporting);

						$filePath = '.' . $dir . '/' . $filename;
					} else {
						$filePath = trim($valueNode->nodeValue, "\r\n");
					}

					$isBreak = false;
					$event = new umiEventPoint('importImageProperty');
					$event->setParam('filePath', $filePath);
					$event->setParam('name', $name);
					$event->setParam('entity', $entity);
					$event->setParam('renameFiles', $this->renameFiles);
					$event->setParam('parser', $this->parser);
					$event->setParam('propertyNode', $propertyNode);
					$event->addRef('isBreak', $isBreak);
					$event->setMode('before');
					$this->callEvent($event);

					if ($isBreak) {
						break;
					}

					$filePath = ltrim($filePath, '.');
					$filePath = $this->getDestinationPath($filePath);

					if ($field->getDataType() === 'img_file') {
						$file = new umiImageFile($filePath);

						if ($valueNode->hasAttribute('alt')) {
							$file->setAlt($valueNode->getAttribute('alt'));
						}

						if ($valueNode->hasAttribute('title')) {
							$file->setTitle($valueNode->getAttribute('title'));
						}

					} else {
						$file = Service::FileFactory()
							->createSecure($filePath);
						$file = '.' . $file->getFilePath(true);
					}

					$entity->setValue($name, $file);
					break;
				}

				case 'relation': {
					if ($this->auto_guide_creation) {
						if ($name == 'payment_status_id' && $type->getMethod() == 'order') {
							$emarket = cmsController::getInstance()->getModule('emarket');

							if ($emarket instanceof emarket) {
								umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = false;
								$codename = $valueNode->nodeValue;
								$order = order::get($entity->getId());

								if ($order instanceof order) {
									$order->setPaymentStatus($codename);
									$order->commit();
									umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;
								}
							}
						} elseif ($name == 'status_id' && $type->getMethod() == 'order') {
							$emarket = cmsController::getInstance()->getModule('emarket');

							if ($emarket instanceof emarket) {
								$codename = $valueNode->nodeValue;
								$order = order::get($entity->getId());

								if ($order instanceof order) {
									$oldStatusId = $order->getOrderStatus();
									$oldStatusCode = $order->getCodeByStatus($oldStatusId);

									if (!in_array($oldStatusCode, ['ready', 'canceled', 'rejected'])) {
										umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = false;
										$order->setOrderStatus($codename);
										$order->commit();
										umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;
									}
								}
							}
						} else {
							$items = [];
							$itemNodeList = $valueNode->getElementsByTagName('item');

							/** @var DOMElement $itemNode */
							foreach ($itemNodeList as $itemNode) {
								$itemName = $itemNode->getAttribute('name');
								$items[] = $itemName;

								$event = new umiEventPoint('importRelationProperty');
								$event->setParam('guideId', $field->getGuideId());
								$event->setParam('itemName', $itemName);
								$event->setParam('sourceId', $this->source_id);
								$event->setParam('itemId', $itemNode->getAttribute('id'));
								$event->setParam('relations', $this->relations);
								$this->callEvent($event);
							}

							$entity->setValue($name, $items);
						}
					}

					break;
				}

				case 'tags': {
					$combinedNode = $this->parser->evaluate('combined', $propertyNode);
					$valueNode = $combinedNode->item(0);

					if ($valueNode) {
						$entity->setValue($name, trim($valueNode->nodeValue, "\r\n"));
					}

					break;
				}

				case 'link_to_object_type': {
					$newValue = $this->relations->getNewTypeIdRelation($this->source_id, $valueNode->nodeValue);
					if (is_numeric($newValue)) {
						$entity->setValue($name, (int) $newValue);
					} else {
						$entity->setValue($name, (int) $valueNode->nodeValue);
					}

					break;
				}

				case 'domain_id': {
					$domainNodeList = $valueNode->getElementsByTagName('domain');

					if ($domainNodeList->length == 0) {
						$entity->setValue($name, $this->getForcedDomainId());
						break;
					}

					$externalId = $domainNodeList->item(0)->getAttribute('id');
					$internalId = $this->relations->getNewDomainIdRelation($this->source_id, $externalId);
					$internalId = $this->getForcedDomainId() ?: $internalId;

					if (is_int($internalId)) {
						$entity->setValue($name, $internalId);
					} elseif (Service::DomainCollection()->isExists($externalId)) {
						$this->relations->setDomainIdRelation($this->source_id, $externalId, $externalId);
						$entity->setValue($name, $externalId);
					} else {
						$entity->setValue($name, $this->getForcedDomainId());
					}

					break;
				}

				case 'domain_id_list': {
					$valueList = [];

					/** @var DOMElement $domainNode */
					foreach ($valueNode->getElementsByTagName('domain') as $domainNode) {
						$externalId = $domainNode->getAttribute('id');

						if (!$externalId) {
							continue;
						}

						$internalId = $this->relations->getNewDomainIdRelation($this->source_id, $externalId);

						if (is_int($internalId)) {
							$valueList[] = $internalId;
						} elseif (Service::DomainCollection()->isExists($externalId)) {
							$this->relations->setDomainIdRelation($this->source_id, $externalId, $externalId);
							$valueList[] = $externalId;
						}
					}

					$valueList = $this->getForcedDomainId() ? (array) $this->getForcedDomainId() : $valueList;
					$entity->setValue($name, $valueList);
					break;
				}

				case 'multiple_image': {
					$valueList = [];

					/* @var DOMElement $valuePart */
					foreach ($valueNodeList as $valuePart) {

						if (!$valuePart->hasAttribute('path')) {
							$this->reportError(
								$this->getLabel('label-property') . " \"{$name}\" " . $this->getLabel('label-has-no-values')
							);
							continue;
						}

						$filePath = $valuePart->getAttribute('path');
						$filePath = ltrim($filePath, '.');
						$filePath = $this->getDestinationPath($filePath);
						$image = new umiImageFile($filePath);

						if ($valuePart->hasAttribute('alt')) {
							$image->setAlt($valuePart->getAttribute('alt'));
						}

						if ($valuePart->hasAttribute('title')) {
							$image->setTitle($valuePart->getAttribute('title'));
						}

						if ($valuePart->hasAttribute('ord')) {
							$image->setOrder($valuePart->getAttribute('ord'));
						}

						$valueList[$image->getFilePath()] = $image;
					}

					$entity->setValue($name, $valueList);
					break;
				}

				case 'text':
				case 'wysiwyg':
				case 'string': {
					$value = $this->changeImagesPathInHtmlContent($valueNode->nodeValue);
					$entity->setValue($name, trim($value, "\r\n"));
					break;
				}
				case 'boolean':
				case 'counter':
				case 'float':
				case 'color':
				case 'password':
				case 'int':
				default: {
					$entity->setValue($name, trim($valueNode->nodeValue, "\r\n"));
					break;
				}
			}
		}

		/**
		 * Импортирует значения поля типа "Цена", принадлежащего сущности
		 * @param iUmiEntinty|iUmiObject|iUmiHierarchyElement $entity сущность
		 * @param string $name имя поля
		 * @param DOMElement $valueNode значение поля
		 * @throws selectorException
		 * @throws coreException
		 * @throws Exception
		 */
		private function importPriceValue(iUmiEntinty $entity, $name, DOMElement $valueNode) {
			/** @var emarket|EmarketMacros $emarket */
			$emarket = cmsController::getInstance()
				->getModule('emarket');
			$needConvert = $emarket instanceof emarket ? $emarket->isNeedToConvertCurrencyOnImport() : false;
			$price = $valueNode->nodeValue;
			$price = str_replace(',', '.', $price);
			$price = (float) preg_replace('/[^0-9.,]/', '', $price);
			$code = $valueNode->getAttribute($valueNode->hasAttribute('currency-code') ? 'currency-code' : 'currency_code');

			if ($needConvert) {
				$currencies = $emarket->getCurrencyFacade();

				try {
					$currency = $currencies->getByCode($code);
					$defaultCurrency = $currencies->getDefault();
					$result = $emarket->formatCurrencyPrice([$price], $defaultCurrency, $currency);
					$price = isset($result[0]) ? $result[0] : 0;
				} catch (privateException $exception) {
					//nothing
				}
			}

			$entity->setValue($name, $price);
		}

		/**
		 * Возвращает путь до корневой директории.
		 * Если путь не задан - полагает, что он содержится в глобальной константе CURRENT_WORKING_DIR
		 * @return string
		 */
		private function getRootDirPath() {
			if ($this->rootDirPath !== null) {
				return $this->rootDirPath;
			}

			return $this->rootDirPath = CURRENT_WORKING_DIR;
		}

		/** Импортирует все кастомные сущности */
		private function importEntities() {
			$result = $this->createEntityImporter()
				->import();
			$this->created_entities += $result['created'];
			$this->updated_entities += $result['updated'];

			foreach ($result['log'] as $message) {
				$this->writeLog($message);
			}

			foreach ($result['errors'] as $errorMessage) {
				$this->reportError($errorMessage);
			}
		}

		/**
		 * Создает импортер сущностей
		 * @return iBaseImporter
		 */
		private function createEntityImporter() {
			$entityRelations = Service::ImportEntitySourceIdBinderFactory()
				->create($this->source_id);
			$entityImporter = Service::UmiDumpEntityBaseImporterFactory()
				->create($this->parser, $entityRelations);
			$entityImporter->setForcedDomainId($this->getForcedDomainId());
			$entityImporter->setChangeFilePathCallback([$this, 'getDestinationPath']);
			return $entityImporter;
		}

		/**
		 * Определяет включен ли режим установки решения
		 * @return bool
		 */
		private function isDemoSiteMode() {
			return $this->demosite_mode;
		}

		/**
		 * @internal
		 * Возвращает путь до импортируемого объекта файловой системы
		 * @param string $originalPath оригинальный относительный путь до импортируемого объекта
		 * @return string
		 */
		public function getDestinationPath($originalPath) {
			$absolutePath = (contains($originalPath, $this->getRootDirPath())) ? $originalPath : $this->buildAbsolutePath($originalPath);

			if ($this->isNeedToAppendSolutionSuffix()) {
				$absolutePath = Service::UmiDumpFilePathConverter()
					->setSuffix($this->getSourceName())
					->convert($absolutePath);
			}

			return $absolutePath;
		}

		/**
		 * Формирует абсолютный путь
		 * @param string $localPath относительный путь
		 * @return string
		 */
		private function buildAbsolutePath($localPath) {
			return $this->getRootDirPath() . $localPath;
		}

		/**
		 * Определяет нужно ли добавлять к файловому пути суффикс устанавливаемого решения
		 * @return bool
		 */
		private function isNeedToAppendSolutionSuffix() {
			$solutionName = Service::UmiDumpSolutionPostfixFilter()
				->run($this->getSourceName());
			$isSameSolutionAppended = Service::SolutionRegistry()
				->isAppended($solutionName);
			$template = templatesCollection::getInstance()
				->getFirstByName($solutionName);
			$isSameNameTemplateExists = false;

			if ($template instanceof iTemplate) {
				$templateSourceId = $this->relations->getSourceIdByTemplateId($template->getId());
				$sourceName = $this->relations->getSourceName($templateSourceId);
				$isSameNameTemplateExists = $sourceName != $this->getSourceName();
			}

			$isNeedToChangePath = $isSameSolutionAppended || $isSameNameTemplateExists;
			return ($this->isDemoSiteMode() && $isNeedToChangePath);
		}

		/**
		 * Возвращает имя решения с постфиксом, если это необходимо
		 * @param string $name оригинальное имя решения
		 * @param int $domainId идентификатор домена
		 * @return string
		 */
		private function getSolutionNameWithPostfix($name, $domainId) {
			if ($this->isNeedToAppendSolutionSuffix()) {
				return Service::UmiDumpSolutionPostfixBuilder()
					->run($name, $domainId);
			}

			return $name;
		}

		/**
		 * Возвращает имя источника импорта
		 * @return string|null
		 */
		private function getSourceName() {
			return isset($this->meta['source-name']) ? $this->meta['source-name'] : null;
		}

		/**
		 * Устанавливает имя источника импорта
		 * @param string $name имя источника
		 * @return $this
		 */
		private function setSourceName($name) {
			$this->meta['source-name'] = (string) $name;
			return $this;
		}

		/**
		 * Заменяет пути до изображений в html контенте на пути с префиксами решения
		 * @param string $content html контент
		 * @return string
		 */
		private function changeImagesPathInHtmlContent($content) {
			if (!$this->isNeedToAppendSolutionSuffix()) {
				return $content;
			}

			$pattern = '|<img[\s]{1,}src[\s]{0,}[\s]{0,}=[\s]{0,}[\'"]([a-zA-Z0-9\._\-\/?=&]{1,})[\'"]|';
			return preg_replace_callback($pattern, function($matches)  {
				$path = $this->getDestinationPath($matches[1]);
				$path = str_replace($this->getRootDirPath(), '', $path);
				return '<img src="' . $path . '"';
			}, $content);
		}
	}
