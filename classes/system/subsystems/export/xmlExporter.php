<?php

	use UmiCms\Service;
	use UmiCms\System\Orm\Entity\iExchange;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Класс экспорта сущностей системы в формате UMIDUMP
	 * @link http://api.docs.umi-cms.ru/razrabotka_nestandartnogo_funkcionala/format_umidump_20/opisanie_formata/
	 */
	class xmlExporter implements iXmlExporter {

		/** @const string Версия формата UMIDUMP, с которой работает класс */
		const VERSION = '2.0';

		/** @const string Идентификатор связанного формата импорта */
		const RELATED_IMPORT_FORMAT = 'commerceML2';

		/** @var int Идентификатор корневого типа "Раздел сайта" */
		protected static $ROOT_PAGE_TYPE_ID;

		/**
		 * @var umiImportRelations экземпляр класса для проверки связей экспортируемых объектов
		 * с ранее импортированными объектами
		 */
		private $relations;

		/**
		 * @var iSourceIdBinder $entityRelations
		 * экземпляр класса для проверки связей экспортируемых сущностей
		 * с ранее импортированными сущностями
		 */
		private $entityRelations;

		/** @var int идентификатор ресурса (каждому сценарию импорта соответствует свой ресурс) */
		protected $source_id;

		/** @var string название ресурса */
		protected $source_name;

		/** @var int[] идентификаторы экспортируемых объектных типов данных */
		protected $types;

		/** @var int[] идентификаторы экспортируемых языков */
		protected $langs;

		/** @var int[] идентификаторы экспортируемых шаблонов дизайна */
		protected $templates;

		/** @var int[] идентификаторы экспортируемых страниц */
		protected $elements;

		/** @var int[] идентификаторы экспортируемых веток (корневых страниц) */
		protected $branches;

		/** @var int[] идентификаторы экспортируемых объектов */
		protected $objects = [];

		/** @var int[] идентификаторы экспортируемых ограничений */
		protected $restrictions;

		/** @var int[] экспортируемые типы полей */
		protected $data_types;

		/** @var string[] экспортируемые настройки регистра */
		protected $registry;

		/** @var domain[] экспортируемые домены */
		protected $domains;

		/** @var umiFile[] экспортируемые файлы */
		protected $files;

		/** @var umiDirectory[] экспортируемые директории */
		protected $directories;

		/** @var array экспортируемые сущности, сгруппированные по сервисам */
		private $entities = [];

		/**
		 * @var array $serializeOptionList опции сериализации
		 *
		 * [
		 *     'name' => 'value'
		 * ]
		 */
		private $serializeOptionList = [
			xmlTranslator::DEEP_OPTION => true
		];

		/**
		 * Списки уже экспортированных объектов системы, чтобы не пришлось экспортировать их дважды.
		 * Хранятся в кеш-файле на время экспорта.
		 */
		protected
			$exported_files = [],
			$exported_types = [],
			$exported_langs = [],
			$exported_domains = [],
			$exported_templates = [],
			$exported_elements = [],
			$exported_objects = [],
			$exported_restrictions = [],
			$exported_registry_items = [],
			$restricted_fields = [],
			$exported_data_types = [],
			$exported_dirs = [],
			$exported_entities = [];

		/** @var int[] идентификаторы исключенных из экспорта страниц */
		protected $excludedElements = [];

		/** @var bool|int лимит на количество экспортируемых объектов за одну итерацию экспорта */
		protected $limit;

		/** @var int счетчик экспортированных объектов за текущую итерацию экспорта */
		protected $position = 0;

		/** @var bool нужно ли прервать итерацию экспорта на текущем шаге? */
		protected $break = false;

		/** @var xmlTranslator транслятор сущностей в XML-формат */
		protected $translator;

		/** @var string путь до директории, в которую будут скопированы экспортируемые файлы и директории */
		protected $destination;

		/** @var bool флаг завершенности экспорта */
		protected $completed = false;

		/** @var DOMDocument экспортируемый документ */
		protected $doc;

		/** @var DOMElement корень экспортируемого документа */
		protected $root;

		/** Узлы первого уровня в экспортируемом документе: */

		/** @var DOMElement */
		protected $meta_container;

		/** @var DOMElement */
		protected $files_container;

		/** @var DOMElement */
		protected $types_container;

		/** @var DOMElement */
		protected $data_types_container;

		/** @var DOMElement */
		protected $pages_container;

		/** @var DOMElement */
		protected $objects_container;

		/** @var DOMElement */
		protected $relations_container;

		/** @var DOMElement */
		protected $restrictions_container;

		/** @var DOMElement */
		protected $registry_container;

		/** @var DOMElement */
		protected $dirs_container;

		/** @var DOMElement */
		protected $hierarchy_container;

		/** @var DOMElement */
		private $domains_container;

		/** @var DOMElement */
		private $permissions_container;

		/** @var DOMElement */
		private $options_container;

		/** @var DOMElement */
		private $templates_container;

		/** @var DOMElement */
		private $langs_container;

		/** @var DOMElement */
		private $entities_container;

		/** @var string[] лог экспорта */
		protected $export_log = [];

		/**
		 * @var bool Нужно ли экспортировать _все_ поля объектов,
		 * даже скрытые и потенциально небезопасные?
		 */
		protected $showAllFields = false;

		/**
		 * @var bool Режим, при котором не экспортируются "связи" экспортируемых сущностей.
		 * Например, если экспортируется страница, в файл экспорта попадет только элемент <page>,
		 * Но не попадут ее язык <lang>, шаблон дизайна <template> и т.д.
		 */
		protected $ignoreRelations = false;

		/**
		 * @var array $saveRelations список обозначений данных, которые так же нужно экспортировать при
		 * определенных действия:
		 *
		 *     *) files - значения полей типов 'file', 'swf_file' и 'img_file' в виде файлов при экспорте страниц;
		 *     *) langs - языки при экспорте страниц;
		 *     *) domains - домены при экспорте страниц;
		 *     *) templates - шаблоны при экспорте страниц;
		 *     *) objects - объекты при экспорте страниц;
		 *     *) fields_relations - значения полей типа 'relation', 'optioned' и 'symlink' при экспорте страниц/объектов;
		 *     *) relation - значения полей типа 'relation' при экспорте страниц/объектов;
		 *     *) optioned - значения полей типа 'optioned' при экспорте страниц/объектов;
		 *     *) symlink - значения полей типа 'symlink' при экспорте страниц/объектов;
		 *     *) restrictions - ограничения значений полей при экспорте объектных типов данных;
		 *     *) permissions - права на страницы и модули при экспорте страниц и объектов, соответственно;
		 *     *) hierarchy - иерархические связи при экспорте страниц;
		 *     *) guides - объектные типы данных, их содержимое и связи справочника с полем
		 * при экспорте объектного типа с полям типа "relation",
		 */
		protected $saveRelations = [];

		/**
		 * @var array Список сущностей, для которых нужно
		 * проставить атрибут "install-only" в файле экспорта.
		 * При повторном импорте такие сущности будут игнорироваться.
		 * Возможные значения:
		 * langs, domains, templates, types, restrictions, pages, objects, modules_permissions
		 */
		protected $installOnly = [];

		/** @var bool Режим, при котором не экспортируются права на страницы и модули/методы */
		protected $ignorePermissions = false;

		/** @var bool Режим, при котором на время экспорта будет удален get-параметр "links" */
		protected $oldGetLinks;

		/** @var bool нужно ли экспортировать данные справочников */
		protected $includeGuidesEnabled = false;

		/** @var bool Режим выгрузки прав на модули и методы для всех пользователей и групп */
		private $exportAllModuleMethodPermissions = false;

		/** @var bool выгружаемые поля поддерживают добавление "на лету" */
		private $fieldAllowRuntimeAdd = false;

		/**
		 * Конструктор
		 * @param string $sourceName имя ресурса
		 * @param bool|int $entitiesLimit лимит на количество экспортируемых за раз объектов.
		 * Если лимит не указан или равен нулю, будут выгружены все объекты за раз.
		 */
		public function __construct($sourceName, $entitiesLimit = false) {
			$this->relations = umiImportRelations::getInstance();
			$this->source_name = $sourceName;
			$this->source_id = $this->relations->addNewSource($sourceName);
			$this->entityRelations = Service::ImportEntitySourceIdBinderFactory()
				->create($this->source_id);
			$this->limit = is_numeric($entitiesLimit) ? $entitiesLimit : false;

			self::$ROOT_PAGE_TYPE_ID = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('root-pages-type');
		}

		/** Включает опцию экспорта данных справочников */
		public function includeGuides() {
			$this->includeGuidesEnabled = true;
		}

		/**
		 * Устанавливает типы сущностей, для которых нужно
		 * проставить атрибут "install-only" в файле экспорта.
		 * @param array $entities типы сущностей
		 */
		public function setInstallOnlyEntities(array $entities) {
			$this->installOnly = $entities;
		}

		/**
		 * Устанавливает режим, при котором не экспортируются права на страницы и модули/методы.
		 * @param bool $status значение опции
		 */
		public function setIgnorePermissions($status) {
			$this->ignorePermissions = (bool) $status;
		}

		/**
		 * Алиас @see xmlExporter::setIgnoreRelations()
		 * @param array|string $saveRelations
		 */
		public function ignoreRelationsExcept($saveRelations) {
			$this->setIgnoreRelations($saveRelations);
		}

		/**
		 * Устанавливает режим, при котором не экспортируются "связи" экспортируемых сущностей
		 * @param array|string $saveRelations типы связей - исключения, которые все равно нужно экспортировать
		 */
		public function setIgnoreRelations($saveRelations = []) {
			$this->ignoreRelations = true;
			$this->saveRelations = (array) $saveRelations;
		}

		/**
		 * Устанавливает опцию "экспортировать все поля объектов"
		 * @param bool $showAllFields значение опции
		 */
		public function setShowAllFields($showAllFields = false) {
			$this->showAllFields = $showAllFields;
		}

		/**
		 * @inheritdoc
		 * @throws InvalidArgumentException
		 */
		public function setSerializeOption($name, $value) {
			if (!is_string($name) || $name === '') {
				throw new InvalidArgumentException('Incorrect serialize option name given');
			}
			$this->serializeOptionList[$name] = $value;
			return $this;
		}

		/**
		 * Возвращает лог экспорта
		 * @return array
		 */
		public function getExportLog() {
			return $this->export_log;
		}

		/**
		 * Записывает сообщение в лог экспорта
		 * @param string $message сообщение
		 */
		protected function writeLog($message) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) {
				Service::Response()
					->getCliBuffer()
					->push($message . PHP_EOL);
			}

			$this->export_log[] = $message;
		}

		/**
		 * Записывает сообщение об ошибке в лог ошибок
		 * @param string $error сообщение об ошибке
		 */
		protected function reportError($error) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) {
				Service::Response()
					->getCliBuffer()
					->push($error . PHP_EOL);
			}

			$this->export_log[] = "<font style='color:red''>" . $error . '</font>';
		}

		/**
		 * Сохраняет информацию об экспортированных объектах в кэш-файл,
		 * если экспорт еще в процессе. Если экспорт уже завершен, кэш-файл удаляется.
		 */
		protected function saveState() {
			$cacheFilePath = $this->getCacheFilePath();

			if (!$this->break) {
				if (file_exists($cacheFilePath)) {
					unlink($cacheFilePath);
				}

				return;
			}

			$keyList = array_keys($this->exported_types, 'found');
			foreach ($keyList as $key => $value) {
				unset($this->exported_types[$value]);
			}

			$keyList = array_keys($this->exported_elements, 'found');
			foreach ($keyList as $key => $value) {
				unset($this->exported_elements[$value]);
			}

			$keyList = array_keys($this->exported_objects, 'found');
			foreach ($keyList as $key => $value) {
				unset($this->exported_objects[$value]);
			}

			$array = [
				'exported_files' => $this->exported_files,
				'exported_types' => $this->exported_types,
				'exported_langs' => $this->exported_langs,
				'exported_domains' => $this->exported_domains,
				'exported_templates' => $this->exported_templates,
				'exported_elements' => $this->exported_elements,
				'exported_objects' => $this->exported_objects,
				'restricted_fields' => $this->restricted_fields,
				'restrictions' => $this->restrictions,
				'exported_restrictions' => $this->exported_restrictions,
				'exported_registry_items' => $this->exported_registry_items,
				'exported_data_types' => $this->exported_data_types,
				'exported_dirs' => $this->exported_dirs,
				'exported_entities' => $this->exported_entities,
			];

			file_put_contents($cacheFilePath, serialize($array));
		}

		/** Восстанавливает информацию об экспортированных объектах из кэш-файла */
		private function restoreState() {
			$cacheFilePath = $this->getCacheFilePath();
			if (!file_exists($cacheFilePath)) {
				return;
			}

			$array = unserialize(file_get_contents($cacheFilePath));
			$this->exported_files = $array['exported_files'];
			$this->exported_types = $array['exported_types'];
			$this->exported_langs = $array['exported_langs'];
			$this->exported_domains = $array['exported_domains'];
			$this->exported_templates = $array['exported_templates'];
			$this->exported_elements = $array['exported_elements'];
			$this->exported_objects = $array['exported_objects'];
			$this->exported_restrictions = $array['exported_restrictions'];
			$this->restricted_fields = $array['restricted_fields'];
			$this->restrictions = $array['restrictions'];
			$this->exported_registry_items = $array['exported_registry_items'];
			$this->exported_data_types = $array['exported_data_types'];
			$this->exported_dirs = $array['exported_dirs'];
			$this->exported_entities = $array['exported_entities'];
		}

		/**
		 * Возвращает путь до кеш-файла с временными результатами экспорта
		 * @return string
		 */
		private function getCacheFilePath() {
			return SYS_TEMP_PATH . '/runtime-cache/' . md5($this->source_name);
		}

		/**
		 * Возвращает список идентификаторов уже экспортированных элементов
		 * @return array
		 */
		public function getExportedElements() {
			return $this->exported_elements;
		}

		/**
		 * Возвращает список идентификаторов уже экспортированных объектов
		 * @return array
		 */
		public function getExportedObject() {
			return $this->exported_objects;
		}

		/**
		 * Добавляет страницы для экспорта
		 * @param array $elementList список страниц или их идентификаторов
		 */
		public function addElements($elementList) {
			foreach ($elementList as $elementId) {
				if ($elementId instanceof iUmiHierarchyElement) {
					$elementId = $elementId->getId();
				}
				$this->elements[] = $elementId;
			}
		}

		/**
		 * Добавляет ветки (корневые страницы) для экспорта
		 * @param array $branchList список страниц или их идентификаторов
		 */
		public function addBranches($branchList) {
			foreach ($branchList as $elementId) {
				if ($elementId instanceof iUmiHierarchyElement) {
					$elementId = $elementId->getId();
				}
				$this->branches[] = $elementId;
			}
		}

		/**
		 * Исключает ветки (корневые страницы) из экспорта
		 * @param array $branchList список страниц или их идентификаторов
		 */
		public function excludeBranches(array $branchList) {
			$umiHierarchy = umiHierarchy::getInstance();

			foreach ($branchList as $elementId) {
				if ($elementId instanceof iUmiHierarchyElement) {
					$elementId = $elementId->getId();
				}

				$this->excludedElements[$elementId] = $elementId;
				$childList = $umiHierarchy->getChildrenList($elementId);

				foreach ($childList as $childId) {
					$this->excludedElements[$childId] = $childId;
				}
			}
		}

		/** @inheritdoc */
		public function addObjects($objectList) {
			foreach ($objectList as $objectId) {
				if ($objectId instanceof iUmiObject) {
					$objectId = $objectId->getId();
				}

				if (is_numeric($objectId)) {
					$objectId = (int) $objectId;
					$this->objects[$objectId] = $objectId;
				}
			}
		}

		/** @inheritdoc */
		public function addTypes($typeList) {
			foreach ($typeList as $typeId) {
				if ($typeId instanceof iUmiObjectType) {
					$typeId = $typeId->getId();
				}
				$this->types[] = $typeId;
			}
		}

		/**
		 * Добавляет ограничения для экспорта
		 * @param array $restrictionList список ограничений или их идентификаторов
		 */
		public function addRestrictions($restrictionList) {
			foreach ($restrictionList as $restrictionId) {
				if ($restrictionId instanceof baseRestriction) {
					$restrictionId = $restrictionId->getId();
				}
				$this->restrictions[] = $restrictionId;
			}
		}

		/**
		 * Добавляет настройки регистра для экспорта
		 * @param string[] $pathList пути настроек регистра
		 */
		public function addRegistry($pathList = []) {
			foreach ($pathList as $path) {
				$this->registry[] = $path;
			}
		}

		/**
		 * Устанавливает путь до директории, в которую будут
		 * скопированы экспортируемые файлы и директории
		 * @param string $destination путь
		 * @return bool|void
		 */
		public function setDestination($destination) {
			if (!is_dir($destination)) {
				$this->reportError('Destination folder does not exist');
				return false;
			}
			$this->destination = $destination;
		}

		/**
		 * Добавляет файлы для экспорта
		 * @param string[] $pathList пути до файлов
		 */
		public function addFiles($pathList = []) {
			foreach ($pathList as $path) {
				if (is_file($path)) {
					$this->files[$path] = new umiFile($path);
				} else {
					$this->reportError("File {$path} doesn't exist");
				}
			}
		}

		/**
		 * Добавляет директории для экспорта
		 * @param string[] $pathList пути до директорий
		 */
		public function addDirs($pathList = []) {
			foreach ($pathList as $path) {
				if (is_dir($path)) {
					$this->directories[] = new umiDirectory($path);
				} else {
					$this->reportError("Folder {$path} doesn't exist");
				}
			}
		}

		/**
		 * Добавляет домены для экспорта
		 * @param iDomain[] $domains домены
		 */
		public function addDomains($domains = []) {
			foreach ($domains as $domain) {
				if (!$domain instanceof iDomain) {
					continue;
				}

				$this->domains[] = $domain;
				$favicon = $domain->getFavicon();

				if (!$favicon instanceof iUmiImageFile) {
					continue;
				}

				$this->addFiles([$favicon->getFilePath()]);
			}
		}

		/**
		 * Добавляет языки для экспорта
		 * @param array $langList список языков или их идентификаторов
		 */
		public function addLangs($langList = []) {
			foreach ($langList as $langId) {
				if ($langId instanceof iLang) {
					$langId = $langId->getId();
				}
				$this->langs[] = $langId;
			}
		}

		/**
		 * Добавляет шаблоны дизайна для экспорта
		 * @param array $templateList список шаблонов дизайна или их идентификаторов
		 */
		public function addTemplates($templateList = []) {
			foreach ($templateList as $templateId) {
				if ($templateId instanceof iTemplate) {
					$templateId = $templateId->getId();
				}
				$this->templates[] = $templateId;
			}
		}

		/**
		 * Добавляет типы полей для экспорта
		 * @param iUmiFieldType[] $fieldTypeList типы полей
		 */
		public function addDataTypes($fieldTypeList = []) {
			foreach ($fieldTypeList as $fieldType) {
				if ($fieldType instanceof iUmiFieldType) {
					$this->data_types[] = $fieldType->getId();
				}
			}
		}

		/** @inheritdoc */
		public function isCompleted() {
			return $this->completed;
		}

		/**
		 * Включить выгрузку прав на модули и методы для всех пользователей и групп.
		 */
		public function needToExportAllModuleMethodPermissions() {
			$this->exportAllModuleMethodPermissions = true;
		}

		/** @inheritdoc */
		public function addEntities(array $serviceList) {

			$dependencySetList = [];
			$cmsController = cmsController::getInstance();

			foreach ($serviceList as $serviceName => $entityIdList) {
				if (isset($this->entities[$serviceName])) {
					$existEntityId = $this->entities[$serviceName];
					$diff = array_diff($existEntityId, $entityIdList);

					if (count($diff) === 0) {
						continue;
					}

					$entityIdList = array_merge($existEntityId, $entityIdList);
				}

				$this->entities[$serviceName] = $entityIdList;

				if ($serviceName == 'modules_to_load') {
					foreach ($entityIdList as $moduleName) {
						$cmsController->getModule($moduleName);
					}
					continue;
				}

				$service = $this->getService($serviceName);

				if (!$service instanceof iExchange) {
					continue;
				}

				$dependencySetList[] = $service->getDependenciesForExportMany($entityIdList);
			}

			foreach ($dependencySetList as $dependencySet) {
				foreach ($dependencySet as  $dependServiceName => $dependIdList) {
					switch ($dependServiceName) {
						case 'TradeOfferCharacteristicExchange' : {
							break;
						}
						case 'TradeOfferPriceCurrencyExchange' :
						case 'TradeStockExchange' :
						case 'umiObjectExchange' : {
							$this->addObjects($dependIdList);
							break;
						}
						case 'umiObjectTypeExchange' : {
							$this->addTypes($dependIdList);
							break;
						}
						default : {
							$this->addEntities([
								$dependServiceName => $dependIdList
							]);
						}
					}
				}
			}
		}

		/**
		 * Устанавливает поддерживают ли поля добавление "на лету"
		 * @param bool $flag поддерживают или нет
		 * @return $this
		 */
		public function setFieldsAllowRuntimeAdd($flag = true) {
			$this->fieldAllowRuntimeAdd = (bool) $flag;
			return $this;
		}

		/** Определяют поддерживают ли поля добавление "на лету" */
		public function isAllowFieldsRuntimeAdd() {
			return $this->fieldAllowRuntimeAdd;
		}

		/** Экспортирует все сущности */
		private function exportEntities() {
			$modulesToLoad = isset($this->entities['modules_to_load']) ? $this->entities['modules_to_load'] : [];
			$modulesList = array_values($modulesToLoad);
			$modulesList = array_unique($modulesList);
			$cmsController = cmsController::getInstance();

			foreach ($modulesList as $module) {
				$cmsController->getModule($module);
			}

			$entityList = $this->sortEntities($this->entities);

			foreach ($entityList as $serviceName => $idList) {
				if ($serviceName == 'modules_to_load') {
					continue;
				}

				try {
					/** @var iUmiCollection|iUmiConstantMapInjector|iExchange $service */
					$service = $this->getService($serviceName);

					if (!is_object($service)) {
						$this->reportError(getLabel('error-no-collection-for-service', false, $service));
						continue;
					}

					if ($service instanceof iExchange) {
						$entityList = $service->exportMany($idList, $this);
					} else {
						$params = (umiCount($idList) > 0) ? ['id' => $idList] : [];
						$entityList = $service->export($params);
					}

					$module = isset($modulesToLoad[$serviceName]) ? $modulesToLoad[$serviceName] : null;

					foreach ($entityList as $entity) {
						$this->exportEntity($entity, $serviceName, $module);
					}
				} catch (Exception $e) {
					$this->reportError($e->getMessage());
				}
			}
		}

		/**
		 * Приводит список сущностей к правильному порядку импорта.
		 * Некоторые сущности зависят друг от друга и должны быть импортированы
		 * в строго определенном порядке.
		 * @todo: придумать, как обойтись без хардкода порядка
		 * @param array $entityList
		 * @return array
		 */
		private function sortEntities(array $entityList) {
			$correctOrderList = [
				'MailNotifications',
				'MailTemplates',
				'MailVariables',
				'SlidersCollection',
				'SlidesCollection',
				'AppointmentServiceGroups',
				'AppointmentServices',
				'AppointmentEmployees',
				'AppointmentEmployeesServices',
				'AppointmentEmployeesSchedules',
				'AppointmentOrders',
				'TradeOfferPriceTypeExchange',
				'TradeOfferExchange',
				'TradeOfferPriceExchange',
				'TradeStockBalanceExchange'
			];

			uksort($entityList, function($firstServiceName, $secondServiceName) use ($correctOrderList) {
				$firstIndex = array_search($firstServiceName, $correctOrderList);
				$secondIndex = array_search($secondServiceName, $correctOrderList);

				if ($firstIndex === false || $secondIndex === false) {
					return 0;
				}

				if ($firstIndex === $secondIndex) {
					return 0;
				}

				return ($firstIndex > $secondIndex) ? 1 : -1;
			});

			return $entityList;
		}

		/**
		 * Возвращает сервис для экспорта сущностей по его названию
		 * @param string $name название сервиса
		 * @return iUmiCollection|iUmiConstantMapInjector|iExchange
		 * @throws Exception
		 */
		private function getService($name) {
			return Service::get($name);
		}

		/**
		 * Экспортирует отдельную сущность
		 * @param array $entity массив свойств сущности
		 * @param string $service название сервиса
		 * @param string|null $module название модуля, который отвечает за сервисы
		 */
		private function exportEntity(array $entity, $service, $module) {
			if (isset($this->exported_entities[$service][$entity['id']])) {
				return;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return;
			}

			$entityNode = null;

			try {
				$entityNode = $this->generateEntityNode($entity, $service, $module);
			} catch (Exception $exception) {
				$this->reportError($exception->getMessage());
			}

			if (!$entityNode instanceof DOMNode) {
				return;
			}

			$this->entities_container->appendChild($entityNode);
			$this->exported_entities[$service][$entity['id']] = $entity['id'];
			$this->position += 1;
		}

		/**
		 * Генерирует и возвращает узел DOMElement с экспортированными свойствами сущности
		 * @param array $entity массив свойств сущности
		 * @param string $serviceName название сервиса
		 * @param string|null $module название модуля, который отвечает за сервисы
		 * @return DOMElement
		 * @throws databaseException
		 * @throws coreException
		 */
		private function generateEntityNode(array $entity, $serviceName, $module) {
			$entityNode = $this->doc->createElement('entity');

			/** @var iUmiCollection|iUmiConstantMapInjector|iExchange $serviceName */
			$service = $this->getService($serviceName);

			if ($service instanceof iUmiCollection) {
				$table = $service->getMap()->get('EXCHANGE_RELATION_TABLE_NAME');
				$id = $this->determineExternalEntityId($entity['id'], $table);
			} else {
				$id = $entity['id'];
			}

			$idAttribute = $this->doc->createAttribute('id');
			$idAttribute->value = $id;
			$entityNode->appendChild($idAttribute);

			$serviceAttribute = $this->doc->createAttribute('service');
			$serviceAttribute->value = $serviceName;
			$entityNode->appendChild($serviceAttribute);

			if ($module !== null) {
				$moduleAttribute = $this->doc->createAttribute('module');
				$moduleAttribute->value = $module;
				$entityNode->appendChild($moduleAttribute);
			}

			if (in_array('entities', $this->installOnly)) {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$entityNode->appendChild($installOnlyAttribute);
			}

			$properties = $entity;
			unset($properties['id']);

			$this->translateEntity($properties, $entityNode);
			return $entityNode;
		}

		/**
		 * Находит и, при необходимости, устанавливает внешний ID, связанный с ID сущности
		 * @param int $internalId идентификатор сущности
		 * @param string $table название таблицы связей
		 * @return mixed
		 * @throws \databaseException
		 * @throws \InvalidArgumentException
		 */
		private function determineExternalEntityId($internalId, $table) {
			$sourceIdBinder = $this->getEntitySourceIdBinder();
			$externalId = $sourceIdBinder->getExternalId($internalId, $table);

			if (!$externalId) {
				$externalId = $internalId;
				$sourceIdBinder->defineRelation($externalId, $internalId, $table);
			}

			return $externalId;
		}

		/** @inheritdoc */
		public function getEntitySourceIdBinder() {
			return $this->entityRelations;
		}

		/** @inheritdoc */
		public function execute() {
			if (getRequest('links') !== null) {
				$this->oldGetLinks = getRequest('links');
				unset($_REQUEST['links']);
			}

			$this->position = 0;
			$this->break = false;

			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$root = $doc->createElement('umidump');
			$root->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$version = $doc->createAttribute('version');
			$version->appendChild($doc->createTextNode(self::VERSION));
			$root->appendChild($version);
			$doc->appendChild($root);

			$this->translator = new xmlTranslator($doc);

			if ($this->showAllFields) {
				$oldshowHiddenFieldGroups = xmlTranslator::$showHiddenFieldGroups;
				xmlTranslator::$showHiddenFieldGroups = true;
				$oldshowUnsecureFields = xmlTranslator::$showUnsecureFields;
				xmlTranslator::$showUnsecureFields = true;
			}

			$oldIgnoreCache = umiObjectProperty::$IGNORE_CACHE;
			umiObjectProperty::$IGNORE_CACHE = true;

			$this->doc = $doc;
			$this->root = $root;

			$this->restoreState();
			$this->createGrid();
			$this->export();
			$this->saveState();

			if ($this->showAllFields) {
				xmlTranslator::$showHiddenFieldGroups = $oldshowHiddenFieldGroups;
				xmlTranslator::$showUnsecureFields = $oldshowUnsecureFields;
			}

			umiObjectProperty::$IGNORE_CACHE = $oldIgnoreCache;

			if ($this->oldGetLinks !== null) {
				$_REQUEST['links'] = $this->oldGetLinks;
			}

			return $this->doc;
		}

		/** Экспортирует все добавленные к экспорту объекты системы */
		private function export() {
			if ($this->directories && !$this->break) {
				$this->exportDirs();
			}

			if ($this->files && !$this->break) {
				$this->exportFiles();
			}

			if ($this->langs && !$this->break) {
				$this->exportLangs();
			}

			if ($this->domains && !$this->break) {
				$this->exportDomains();
			}

			if ($this->templates && !$this->break) {
				$this->exportTemplates();
			}

			if ($this->data_types && !$this->break) {
				$this->exportDataTypes();
			}

			if ($this->types && !$this->break) {
				$this->exportTypes();
			}

			if ($this->elements && !$this->break) {
				$this->exportElements();
			}

			if ($this->objects && !$this->break) {
				$this->exportObjects();
			}

			if ($this->branches && !$this->break) {
				$this->exportBranches();
			}

			if ($this->restrictions && !$this->break) {
				$this->exportRestrictions();
			}

			if ($this->registry && !$this->break) {
				$this->exportRegs();
			}

			if (!$this->break) {
				$this->exportEntities();
			}

			if ($this->isNeedToExportAllModuleMethodPermissions()) {
				$this->exportModuleMethodPermissions();
			}

			$this->completed = !$this->break;

			$this->exportMetaBranches();
		}

		/** Экспортирует добавленные ветки внутрь узла <meta> */
		private function exportMetaBranches() {
			if (umiCount($this->branches) && $this->completed) {
				$branchIdList = [];

				foreach ($this->branches as $branchId) {
					if (isset($this->exported_elements[$branchId]) && $this->exported_elements[$branchId] != 'found') {
						$branchId = $this->exported_elements[$branchId];
					}

					$branchIdList[] = $branchId;
				}

				$branchesNode = $this->doc->createElement('branches');
				$this->translateEntity(['nodes:id' => $branchIdList], $branchesNode);
				$this->meta_container->appendChild($branchesNode);
			}
		}

		/**
		 * Записывает в документ экспорта информацию о текущей дате
		 * в форматах unix timestamp, rfc, utc
		 * @param int $timestamp дата в формате unix timestamp
		 * @param DOMElement $container узел документа, в который нужно записать информацию
		 * @return DOMElement
		 */
		protected function createDateSection($timestamp, DOMElement $container) {
			$doc = $this->doc;
			$date = new umiDate($timestamp);

			$timestampNode = $doc->createElement('timestamp');
			$timestampNode->appendChild($doc->createTextNode($date->getFormattedDate('U')));
			$container->appendChild($timestampNode);

			$rfcNode = $doc->createElement('rfc');
			$rfcNode->appendChild($doc->createTextNode($date->getFormattedDate('r')));
			$container->appendChild($rfcNode);

			$utcNode = $doc->createElement('utc');
			$utcNode->appendChild($doc->createTextNode($date->getFormattedDate(DATE_ATOM)));
			$container->appendChild($utcNode);

			return $container;
		}

		/** Создает все узлы первого уровня в экспортируемом документе */
		protected function createGrid() {
			$document = $this->doc;

			$this->meta_container = $document->createElement('meta');
			$this->root->appendChild($this->meta_container);
			$this->exportMeta();

			$this->registry_container = $document->createElement('registry');
			$this->root->appendChild($this->registry_container);

			$this->dirs_container = $document->createElement('directories');
			$this->root->appendChild($this->dirs_container);

			$this->files_container = $document->createElement('files');
			$this->root->appendChild($this->files_container);

			$this->langs_container = $document->createElement('langs');
			$this->root->appendChild($this->langs_container);

			$this->domains_container = $document->createElement('domains');
			$this->root->appendChild($this->domains_container);

			$this->templates_container = $document->createElement('templates');
			$this->root->appendChild($this->templates_container);

			$this->data_types_container = $document->createElement('datatypes');
			$this->root->appendChild($this->data_types_container);

			$this->types_container = $document->createElement('types');
			$this->root->appendChild($this->types_container);

			$this->objects_container = $document->createElement('objects');
			$this->root->appendChild($this->objects_container);

			$this->pages_container = $document->createElement('pages');
			$this->root->appendChild($this->pages_container);

			$this->entities_container = $document->createElement('entities');
			$this->root->appendChild($this->entities_container);

			$this->relations_container = $document->createElement('relations');
			$this->root->appendChild($this->relations_container);

			$this->options_container = $document->createElement('options');
			$this->root->appendChild($this->options_container);

			$this->restrictions_container = $document->createElement('restrictions');
			$this->root->appendChild($this->restrictions_container);

			$this->permissions_container = $document->createElement('permissions');
			$this->root->appendChild($this->permissions_container);

			$this->hierarchy_container = $document->createElement('hierarchy');
			$this->root->appendChild($this->hierarchy_container);
		}

		/** Заполняет узел <meta> в экспортируемом документе */
		private function exportMeta() {
			$document = $this->doc;
			$domain = Service::DomainDetector()->detect();
			$lang = Service::LanguageDetector()->detect();

			$siteNameNode = $document->createElement('site-name');
			$siteNameNode->appendChild($document->createCDATASection(def_module::parseTPLMacroses(macros_sitename())));
			$this->meta_container->appendChild($siteNameNode);

			$domainNode = $document->createElement('domain');
			$domainNode->appendChild($document->createCDATASection($domain->getHost()));
			$this->meta_container->appendChild($domainNode);

			$langNode = $document->createElement('lang');
			$langNode->appendChild($document->createCDATASection($lang->getPrefix()));
			$this->meta_container->appendChild($langNode);

			$sourceNameNode = $document->createElement('source-name');
			$sourceName = $this->source_name ?: md5($domain->getId() . $lang->getId());
			$sourceNameNode->appendChild($document->createCDATASection($sourceName));
			$this->meta_container->appendChild($sourceNameNode);

			$generateTimeNode = $document->createElement('generate-time');
			$this->createDateSection(time(), $generateTimeNode);
			$this->meta_container->appendChild($generateTimeNode);
		}

		/**
		 * Преобразует сущность в экспортированный узел документа
		 * @param mixed $entity сущность
		 * @param DOMElement $container узел, в который нужно экспортировать данные сущности
		 * @throws coreException
		 */
		protected function translateEntity($entity, $container) {
			$this->translator->chooseTranslator($container, $entity, $this->getOptionList());
		}

		/**
		 * Возвращает опции сериализации
		 * @return array
		 */
		private function getOptionList() {
			return $this->serializeOptionList;
		}

		/** Экспортирует все файлы */
		protected function exportFiles() {
			foreach ($this->files as $file) {
				$this->exportFile($file);
			}

			if ($this->destination) {
				$newDirectory = new umiDirectory($this->destination);
				$newDirectoryDirs = $newDirectory->getFSObjects(2);
				foreach ($newDirectoryDirs as $dir) {
					chmod($dir, 0777);
				}
			}
		}

		/**
		 * Экспортирует файл
		 * @param umiFile $file файл
		 * @return bool
		 */
		protected function exportFile(umiFile $file) {
			$path = $file->getFilePath();

			if (isset($this->exported_files[$path])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$fileNode = $this->doc->createElement('file');
			$this->files_container->appendChild($fileNode);
			$this->translateEntity($file, $fileNode);

			$hash = md5_file($path);
			$hashAttribute = $this->doc->createAttribute('hash');
			$fileNode->appendChild($hashAttribute);
			$hashText = $this->doc->createTextNode("{$hash}");
			$hashAttribute->appendChild($hashText);

			$fileName = $file->getFileName();
			$nameAttribute = $this->doc->createAttribute('name');
			$fileNode->appendChild($nameAttribute);
			$nameText = $this->doc->createTextNode("{$fileName}");
			$nameAttribute->appendChild($nameText);

			if ($this->destination) {
				$filePath = $this->destination . $file->getFilePath(true);
				$filePathDir = dirname($filePath);

				if (!file_exists($filePathDir)) {
					mkdir($filePathDir, 0777, true);
				}

				if (copy($path, $filePath)) {
					chmod($filePath, 0777);
				} else {
					$this->reportError("File \"{$path} \" cannot be copied to \"{$filePath}\"");
				}
			} else {
				$this->reportError('Files cannot be copied because destination folder isn\'t defined');
			}

			$this->exported_files[$path] = $path;
			$this->position++;
			return true;
		}

		/**
		 * Проверяет, превышен ли лимит экспортированных сущностей
		 * в текущей итерации экспорта.
		 * @return bool
		 */
		private function isLimitExceeded() {
			return $this->limit && ($this->position >= $this->limit);
		}

		/** Экспортирует все директории */
		protected function exportDirs() {
			foreach ($this->directories as $directory) {
				$this->exportDir($directory);
			}
		}

		/**
		 * Экспортирует директорию
		 * @param umiDirectory $directory директория
		 * @return bool
		 */
		protected function exportDir(umiDirectory $directory) {
			$path = $directory->getPath();
			$nodeValue = ltrim($path, '.');

			if (isset($this->exported_dirs[$path])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$directoryNode = $this->doc->createElement('directory', $nodeValue);
			$this->dirs_container->appendChild($directoryNode);

			$directoryNode->setAttribute('path', $path);
			$directoryNode->setAttribute('name', $directory->getName());

			$this->exported_dirs[$path] = $path;
			$this->position++;
			return true;
		}

		/** Экспортирует все языки */
		protected function exportLangs() {
			foreach ($this->langs as $langId) {
				$this->exportLang($langId);
			}
		}

		/**
		 * Экспортирует язык
		 * @param int $langId ID языка
		 * @return bool
		 */
		protected function exportLang($langId) {
			if (isset($this->exported_langs[$langId])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$lang = Service::LanguageCollection()->getLang($langId);
			$langNode = $this->doc->createElement('lang');
			$this->translateEntity($lang, $langNode);

			$extLangId = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			if ($extLangId === false) {
				$this->relations->setLangIdRelation($this->source_id, $langId, $langId);
				$extLangId = $langId;
			} else {
				$langNode->setAttribute('id', $extLangId);
			}

			if (in_array('langs', $this->installOnly)) {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$langNode->appendChild($installOnlyAttribute);
			}

			$this->langs_container->appendChild($langNode);
			$this->exported_langs[$langId] = $extLangId;
			$this->position++;
			return true;
		}

		/** Экспортирует все домены */
		protected function exportDomains() {
			foreach ($this->domains as $domain) {
				$this->exportDomain($domain);
			}
		}

		/**
		 * Экспортирует домен
		 * @param iDomain $domain домен
		 * @return bool
		 */
		protected function exportDomain(iDomain $domain) {
			$domainId = $domain->getId();
			if (isset($this->exported_domains[$domainId])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$domainNode = $this->doc->createElement('domain');
			$this->translateEntity($domain, $domainNode);

			$extDomainId = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
			if ($extDomainId === false) {
				$this->relations->setDomainIdRelation($this->source_id, $domainId, $domainId);
				$extDomainId = $domainId;
			} else {
				$domainNode->setAttribute('id', $extDomainId);
			}

			if (in_array('domains', $this->installOnly)) {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$domainNode->appendChild($installOnlyAttribute);
			}

			$langId = $domain->getDefaultLangId();
			if ($this->exportLang($langId)) {
				if ($this->isLimitExceeded()) {
					$this->break = true;
					return true;
				}
			}

			$extLangId = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			$domainNode->setAttribute('lang-id', $extLangId);
			$domainMirrorList = $domain->getMirrorsList();

			foreach ($domainMirrorList as $domainMirror) {
				$domainMirrorNode = $this->doc->createElement('domain-mirror');
				$domainNode->appendChild($domainMirrorNode);
				$this->translateEntity($domainMirror, $domainMirrorNode);

				$mirrorId = $domainMirror->getId();
				$extMirrorId = $this->relations->getOldDomainMirrorIdRelation($this->source_id, $mirrorId);

				if ($extMirrorId === false) {
					$this->relations->setDomainMirrorIdRelation($this->source_id, $mirrorId, $mirrorId);
				} else {
					$domainMirrorNode->setAttribute('id', $extMirrorId);
				}
			}

			$this->domains_container->appendChild($domainNode);
			$this->exported_domains[$domainId] = $extDomainId;
			$this->position++;
			return true;
		}

		/** Экспортирует все шаблоны дизайна */
		protected function exportTemplates() {
			foreach ($this->templates as $templateId) {
				$this->exportTemplate($templateId);
			}
		}

		/**
		 * Экспортирует шаблон дизайна
		 * @param int $templateId ID шаблона дизайна
		 * @return bool
		 */
		protected function exportTemplate($templateId) {
			$template = templatesCollection::getInstance()->getTemplate($templateId);

			if (isset($this->exported_templates[$templateId])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$templateNode = $this->doc->createElement('template');
			$this->translateEntity($template, $templateNode);

			$extTemplateId = $this->relations->getOldTemplateIdRelation($this->source_id, $templateId);
			if ($extTemplateId === false) {
				$this->relations->setTemplateIdRelation($this->source_id, $templateId, $templateId);
				$extTemplateId = $templateId;
			} else {
				$templateNode->setAttribute('id', $extTemplateId);
			}

			if (in_array('templates', $this->installOnly)) {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$templateNode->appendChild($installOnlyAttribute);
			}

			$langId = $template->getLangId();
			if ($this->exportLang($langId)) {
				if ($this->isLimitExceeded()) {
					$this->break = true;
					return true;
				}
			}

			$extLangId = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			$templateNode->setAttribute('lang-id', $extLangId);

			$domainId = $template->getDomainId();
			$domain = Service::DomainCollection()->getDomain($domainId);

			if ($this->exportDomain($domain)) {
				if ($this->isLimitExceeded()) {
					$this->break = true;
					return true;
				}
			}

			$extDomainId = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
			$templateNode->setAttribute('domain-id', $extDomainId);

			$this->templates_container->appendChild($templateNode);
			$this->exported_templates[$templateId] = $extTemplateId;
			$this->position++;
			return true;
		}

		/** Экспортирует все типы данных полей */
		protected function exportDataTypes() {
			foreach ($this->data_types as $typeId) {
				if ($this->break) {
					return;
				}
				$this->exportDataType($typeId);
			}
		}

		/**
		 * Экспортирует тип данных поля
		 * @param int $typeId ID типа данных поля
		 * @return bool
		 */
		protected function exportDataType($typeId) {
			$type = umiFieldTypesCollection::getInstance()->getFieldType($typeId);
			if (!$type instanceof iUmiFieldType) {
				return false;
			}

			if (isset($this->exported_data_types[$typeId])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$datatypeNode = $this->doc->createElement('datatype');
			$this->data_types_container->appendChild($datatypeNode);
			$this->translateEntity($type, $datatypeNode);
			$datatypeNode->removeAttribute('id');
			$this->exported_data_types[$typeId] = $typeId;
		}

		/** Экспортирует все объектные типы данных */
		protected function exportTypes() {
			foreach ($this->types as $typeId) {
				$this->exportType($typeId);
			}
		}

		/**
		 * Экспортирует объектный тип данных
		 * @param int $typeId ID типа данных
		 * @return bool
		 */
		protected function exportType($typeId) {
			$type = umiObjectTypesCollection::getInstance()->getType($typeId);
			if (!$type instanceof iUmiObjectType) {
				return false;
			}

			if (isset($this->exported_types[$typeId])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$this->exported_types[$typeId] = 'found';

			$parentTypeId = $type->getParentId();
			if ($parentTypeId) {
				if ($this->exportType($parentTypeId)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return true;
					}
				}
			}

			$typeNode = $this->doc->createElement('type');
			$this->translateEntity($type, $typeNode);

			$extTypeId = $this->relations->getOldTypeIdRelation($this->source_id, $typeId);
			if (!$extTypeId) {
				$extTypeId = ($typeId == self::$ROOT_PAGE_TYPE_ID) ? '{root-pages-type}' : $typeId;
				$this->relations->setTypeIdRelation($this->source_id, $extTypeId, $typeId);
			}
			$typeNode->setAttribute('id', $extTypeId);

			if (in_array('types', $this->installOnly)) {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$typeNode->appendChild($installOnlyAttribute);
			}

			$parentTypeId = $type->getParentId();
			if ($parentTypeId) {
				$extParentTypeId = $this->relations->getOldTypeIdRelation($this->source_id, $parentTypeId);
				if ($extParentTypeId === false) {
					$extParentTypeId = ($parentTypeId == self::$ROOT_PAGE_TYPE_ID) ? '{root-pages-type}' : $parentTypeId;
					$this->relations->setTypeIdRelation($this->source_id, $extParentTypeId, $parentTypeId);
				}
				$typeNode->setAttribute('parent-id', $extParentTypeId);
			}

			$parser = new DOMXPath($this->doc);
			if ($parser->evaluate('base', $typeNode)->length) {
				/** @var DOMElement $baseNode */
				$baseNode = $parser->evaluate('base', $typeNode)->item(0);
				$baseNode->removeAttribute('id');
			}

			/** @var DOMElement $groupNode */
			foreach ($parser->evaluate('fieldgroups/group', $typeNode) as $groupNode) {
				$groupId = $groupNode->getAttribute('id');
				$typeGroup = $type->getFieldsGroup($groupId, true);

				if (!$typeGroup instanceof iUmiFieldsGroup) {
					continue;
				}

				if ($typeGroup->getIsActive()) {
					$groupNode->setAttribute('active', 'active');
				} else {
					$groupNode->setAttribute('active', '0');
				}

				if (!$typeGroup->getIsVisible()) {
					$groupNode->setAttribute('visible', '0');
				}

				$groupNode->removeAttribute('id');
			}

			$relationsToExport = [];
			$fieldsCollection = umiFieldsCollection::getInstance();

			/** @var DOMElement $fieldNode */
			foreach ($parser->evaluate('fieldgroups/group/field', $typeNode) as $fieldNode) {
				$fieldId = (int) $fieldNode->getAttribute('id');
				$fieldName = $fieldNode->getAttribute('name');
				$extFieldName = $this->relations->getOldFieldName($this->source_id, $typeId, $fieldId);

				if ($extFieldName === false) {
					$this->relations->setFieldIdRelation($this->source_id, $typeId, $fieldName, $fieldId);
					$extFieldName = $fieldName;
				} else {
					$fieldNode->setAttribute('name', $extFieldName);
				}

				if ($fieldNode->getElementsByTagName('restriction')->length) {
					$fieldRestriction = $fieldNode->getElementsByTagName('restriction')->item(0);
					$restrictionId = $fieldRestriction->getAttribute('id');

					$this->restrictions[] = $restrictionId;
					$this->restricted_fields[] = [
						'restriction-id' => $restrictionId,
						'field-name' => $extFieldName,
						'type-id' => $extTypeId,
					];
					$fieldRestriction->removeAttribute('field-type-id');
				}

				$guideId = $fieldNode->hasAttribute('guide-id') ? $fieldNode->getAttribute('guide-id') : false;
				if ($guideId && $this->includeGuidesEnabled) {
					$guideNode = $this->doc->createElement('guide');
					$guideNode->setAttribute('id', $guideId);

					foreach ($this->getItems($guideId, ['id', 'name']) as $object) {
						$elementNode = $this->doc->createElement('item');
						$elementNode->setAttribute('id', $object['id']);
						$elementNode->setAttribute('name', $object['name']);
						/** @var iUmiImportRelations $importRelations */
						$importRelations = umiImportRelations::getInstance();
						$relationId = $importRelations->getOldObjectIdRelation(
							$importRelations->getSourceId(self::RELATED_IMPORT_FORMAT),
							$object['id']
						);
						$elementNode->setAttribute('relation', $relationId);
						$guideNode->appendChild($elementNode);
					}

					$fieldNode->appendChild($guideNode);
				}

				if ($guideId && (!$this->ignoreRelations || in_array('guides', $this->saveRelations))) {
					if ($this->exportType($guideId)) {
						if ($this->isLimitExceeded()) {
							$this->break = true;
							return true;
						}
					}

					if ($this->objects) {
						$sel = new selector('objects');
						$sel->types('object-type')->id($guideId);
						$sel->option('return')->value('id');

						foreach ((array) $sel->result() as $object) {
							if (!in_array($object['id'], $this->objects)) {
								$this->objects[] = $object['id'];
							}
						}
					}

					$newGuideId = $this->relations->getOldTypeIdRelation($this->source_id, $guideId);
					$fieldNode->setAttribute('guide-id', $newGuideId);

					$relationNode = $this->doc->createElement('relation');
					$relationNode->setAttribute('type-id', $extTypeId);
					$relationNode->setAttribute('field-name', $extFieldName);

					$guideNode = $this->doc->createElement('guide');
					$guideNode->setAttribute('id', $newGuideId);
					$relationNode->appendChild($guideNode);

					$relationsToExport[] = $relationNode;
				}

				if ($fieldNode->getElementsByTagName('type')->length) {
					$fieldType = $fieldNode->getElementsByTagName('type')->item(0);
					$fieldType->removeAttribute('id');
				}

				$typeField = $fieldsCollection->getField($fieldId);
				if ($typeField->getIsSystem()) {
					$fieldNode->setAttribute('system', 'system');
				}

				if ($typeField->isImportant()) {
					$fieldNode->setAttribute('important', 'important');
				}

				$fieldNode->removeAttribute('field-type-id');
			}

			foreach ($relationsToExport as $relationNode) {
				$this->relations_container->appendChild($relationNode);
			}

			$this->types_container->appendChild($typeNode);
			$this->exported_types[$typeId] = $extTypeId;
			$this->position++;
			return true;
		}

		/**
		 * Возвращает значения полей элементов справочника
		 * @param int $guideId ID справочника, объекты которого нужно вернуть
		 * @param array $fields список имен полей элементов, которые нужно вернуть
		 * @return array|int|null
		 */
		public function getItems($guideId, $fields = ['id']) {
			try {
				$sel = new selector('objects');
				$sel->types('object-type')->id($guideId);
				$sel->option('return')->value($fields);
				return $sel->result();
			} catch (Exception $e) {
				return [];
			}
		}

		/** Экспортирует все ограничения */
		protected function exportRestrictions() {
			if (!$this->ignoreRelations || in_array('restrictions', $this->saveRelations)) {
				foreach ($this->restrictions as $restrictionId) {
					$this->exportRestriction($restrictionId);
				}
			}
		}

		/**
		 * Экспортирует ограничение полей
		 * @param int $restrictionId ID ограничения
		 * @return bool
		 */
		protected function exportRestriction($restrictionId) {
			if (isset($this->exported_restrictions[$restrictionId])) {
				return false;
			}

			$restriction = baseRestriction::get($restrictionId);
			if (!$restriction instanceof baseRestriction) {
				return false;
			}

			$prefix = $restriction->getClassName();
			$title = $restriction->getTitle();
			$typeId = $restriction->getFieldTypeId();
			$type = umiFieldTypesCollection::getInstance()->getFieldType($typeId);
			$dataType = $type->getDataType();
			$isMultiple = $type->getIsMultiple();

			$extRestrictionId = $this->relations->getOldRestrictionIdRelation($this->source_id, $restrictionId);
			if (!$extRestrictionId) {
				$this->relations->setRestrictionIdRelation($this->source_id, $restrictionId, $restrictionId);
				$extRestrictionId = $restrictionId;
			}

			$restrictionNode = $this->doc->createElement('restriction');
			$restrictionNode->setAttribute('id', $extRestrictionId);
			$restrictionNode->setAttribute('prefix', $prefix);
			$restrictionNode->setAttribute('title', $title);
			$restrictionNode->setAttribute('field-type', $dataType);
			$restrictionNode->setAttribute('is-multiple', $isMultiple);

			foreach ($this->restricted_fields as $key => $value) {
				if ($value['restriction-id'] == $restrictionId) {
					$fieldNode = $this->doc->createElement('field');
					$fieldNode->setAttribute('field-name', $value['field-name']);
					$fieldNode->setAttribute('type-id', $value['type-id']);
					$restrictionNode->appendChild($fieldNode);
				}
			}

			if (in_array('restriction', $this->installOnly)) {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$restrictionNode->appendChild($installOnlyAttribute);
			}

			$this->restrictions_container->appendChild($restrictionNode);
			$this->exported_restrictions[$restrictionId] = $extRestrictionId;
			$this->position++;
			return true;
		}

		/** Экспортирует все ключи реестра */
		protected function exportRegs() {
			foreach ($this->registry as $path) {
				if ($this->break) {
					return;
				}
				$this->exportReg($path);
			}
		}

		/**
		 * Экспортирует ключ реестра
		 * @param string $path путь ключа
		 * @return bool
		 */
		protected function exportReg($path) {
			if (isset($this->exported_registry_items[$path])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$regedit = Service::Registry();
			$path = trim($path, '/');

			if (!$regedit->contains($path)) {
				return false;
			}

			$value = $regedit->get($path);

			if (mb_strrpos($path, '/')) {
				$parentPath = substr_replace($path, '', mb_strrpos($path, '/'));

				if ($this->exportReg($parentPath)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return true;
					}
				}
			}

			$keyNode = $this->doc->createElement('key');
			$keyNode->setAttribute('path', $path);
			$keyNode->setAttribute('val', $value);
			$this->registry_container->appendChild($keyNode);

			$this->exported_registry_items[$path] = $path;
			$this->position++;
			return true;
		}

		/** Экспортирует все ветки */
		protected function exportBranches() {
			foreach ($this->branches as $branchId) {
				if ($this->break) {
					break;
				}
				$this->exportBranch($branchId);
			}
		}

		/**
		 * Экспортирует ветку (корневой элемент)
		 * @param int $elementId ID корневого элемента
		 * @return bool
		 */
		protected function exportBranch($elementId) {
			if (isset($this->excludedElements[$elementId])) {
				return false;
			}

			$this->exportElement($elementId);
			$children = umiHierarchy::getInstance()->getChildrenTree($elementId, true, true, 1);

			foreach ($children as $childId => $tmp) {
				if ($this->break) {
					return false;
				}
				$this->exportElement($childId);
				$this->exportBranch($childId);
			}
		}

		/** Экспортирует все страницы */
		protected function exportElements() {
			foreach ($this->elements as $elementId) {
				if ($this->break) {
					return;
				}
				$this->exportElement($elementId);
			}
		}

		/**
		 * Экспортирует страницу
		 * @param int $elementId ID страницы
		 * @return bool
		 */
		protected function exportElement($elementId) {
			umiHierarchy::getInstance()->clearCache();

			if (isset($this->excludedElements[$elementId])) {
				return false;
			}
			if (isset($this->exported_elements[$elementId])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$element = umiHierarchy::getInstance()->getElement($elementId, true, true);
			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->exported_elements[$elementId] = 'found';

			$typeId = $element->getObjectTypeId();
			if ($this->exportType($typeId)) {
				if ($this->isLimitExceeded()) {
					$this->break = true;
					return true;
				}
			}

			$pageNode = $this->doc->createElement('page');
			$this->translateEntity($element, $pageNode);
			$pageNode->removeAttribute('update-time');

			$langId = $element->getLangId();
			if (!$this->ignoreRelations || in_array('langs', $this->saveRelations)) {
				if ($this->exportLang($langId)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return true;
					}
				}

				$extLangId = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			} else {
				$extLangId = $this->relations->getOldLangIdRelation($this->source_id, $langId);
				if ($extLangId === false) {
					$this->relations->setLangIdRelation($this->source_id, $langId, $langId);
					$extLangId = $langId;
				}
			}

			$pageNode->setAttribute('lang-id', $extLangId);
			$domainId = $element->getDomainId();

			if (!$this->ignoreRelations || in_array('domains', $this->saveRelations)) {
				$domain = Service::DomainCollection()->getDomain($domainId);

				if ($this->exportDomain($domain)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return true;
					}
				}

				$extDomainId = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
			} else {
				$extDomainId = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
				if ($extDomainId === false) {
					$this->relations->setDomainIdRelation($this->source_id, $domainId, $domainId);
					$extDomainId = $domainId;
				}
			}

			$pageNode->setAttribute('domain-id', $extDomainId);
			$templateId = $element->getTplId();

			if (!$this->ignoreRelations || in_array('templates', $this->saveRelations)) {
				if ($this->exportTemplate($templateId)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return true;
					}
				}

				$extTemplateId = $this->relations->getOldTemplateIdRelation($this->source_id, $templateId);
			} else {
				$extTemplateId = $this->relations->getOldTemplateIdRelation($this->source_id, $templateId);
				if ($extTemplateId === false) {
					$this->relations->setTemplateIdRelation($this->source_id, $templateId, $templateId);
					$extTemplateId = $templateId;
				}
			}

			if (in_array('pages', $this->installOnly)) {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$pageNode->appendChild($installOnlyAttribute);
			}

			$templatePath = templatesCollection::getInstance()->getTemplate($templateId)->getFilename();
			$templateNode = $this->doc->createElement('template');
			$templateNode->setAttribute('id', $extTemplateId);
			$templateNode->appendChild($this->doc->createTextNode($templatePath));
			$pageNode->appendChild($templateNode);

			$extElementId = $this->relations->getOldIdRelation($this->source_id, $elementId);
			if ($extElementId === false) {
				$this->relations->setIdRelation($this->source_id, $elementId, $elementId);
				$extElementId = $elementId;
			} else {
				$pageNode->setAttribute('id', $extElementId);
			}

			$parentId = $element->getParentId();
			if ($parentId) {
				$extParentId = $this->relations->getOldIdRelation($this->source_id, $parentId);
				if ($extParentId === false) {
					$this->relations->setIdRelation($this->source_id, $parentId, $parentId);
					$extParentId = $parentId;
				}

				$pageNode->setAttribute('parentId', $extParentId);
			}

			$parser = new DOMXPath($this->doc);
			if ($parser->evaluate('basetype', $pageNode)->length) {
				/** @var DOMElement $basetypeNode */
				$basetypeNode = $parser->evaluate('basetype', $pageNode)->item(0);
				$basetypeNode->removeAttribute('id');
			}

			$extTypeId = $this->relations->getOldTypeIdRelation($this->source_id, $typeId);
			$pageNode->setAttribute('type-id', $extTypeId);

			$objectId = $pageNode->getAttribute('object-id');
			if (!$this->ignoreRelations || in_array('objects', $this->saveRelations)) {
				if ($this->exportObject($objectId)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return true;
					}
				}

				$extObjectId = $this->relations->getOldObjectIdRelation($this->source_id, $objectId);
			} else {
				$extObjectId = $this->relations->getOldObjectIdRelation($this->source_id, $objectId);
				if ($extObjectId === false) {
					$this->relations->setObjectIdRelation($this->source_id, $objectId, $objectId);
					$extObjectId = $objectId;
				}
			}

			$pageNode->setAttribute('object-id', $extObjectId);

			/** @var DOMElement $groupNode */
			foreach ($parser->evaluate('properties/group', $pageNode) as $groupNode) {
				if ($groupNode->hasAttribute('id')) {
					$groupNode->removeAttribute('id');
				}
			}

			$relationsToExport = [];
			$optionsToExport = [];

			/** @var DOMElement $propertyNode */
			foreach ($parser->evaluate('properties/group/property', $pageNode) as $propertyNode) {
				$fieldId = (int) $propertyNode->getAttribute('id');

				$extFieldName = $this->relations->getOldFieldName($this->source_id, $typeId, $fieldId);
				if ($extFieldName) {
					$propertyNode->setAttribute('name', $extFieldName);
				}

				if ($this->isAllowFieldsRuntimeAdd()) {
					$propertyNode->setAttribute('allow-runtime-add', '1');
				}

				$fieldType = $propertyNode->getAttribute('type');

				if ($fieldType == 'relation' && $this->shouldExportRelations()) {
					$relationNode = $this->buildPageRelationNode($extFieldName, $extElementId);

					if (!$this->exportRelation($relationNode, $propertyNode)) {
						return true;
					}

					$relationsToExport[] = $relationNode;
				}

				if ($fieldType == 'symlink' && $this->shouldExportSymlinks()) {
					$relationNode = $this->buildPageRelationNode($extFieldName, $extElementId);

					if (!$this->exportRelation($relationNode, $propertyNode)) {
						return true;
					}

					$relationsToExport[] = $relationNode;
				}

				if ($fieldType == 'optioned' && $this->shouldExportOptions()) {
					$entityNode = $this->buildPageOptionNode($extFieldName, $extElementId);

					if (!$this->exportOptions($propertyNode, $entityNode)) {
						return true;
					}

					$optionsToExport[] = $entityNode;
				}

				if (!$this->ignoreRelations || in_array('files', $this->saveRelations)) {
					if ($fieldType == 'file' || $fieldType == 'swf_file' || $fieldType == 'img_file') {
						$filePath = $propertyNode->getElementsByTagName('value')->item(0)->nodeValue;

						if (file_exists('./' . $filePath)) {
							$file = new umiFile('./' . $filePath);
							$this->exportFile($file);
						} elseif (file_exists(CURRENT_WORKING_DIR . $filePath)) {
							$file = new umiFile(CURRENT_WORKING_DIR . $filePath);
							$this->exportFile($file);
						}
					}
				}

				if ($fieldType == 'offer_id_list' || $fieldType == 'offer_id') {
					$this->exportTradeOfferList($parser, $propertyNode);
					$relationNode = $this->buildPageRelationNode($extFieldName, $extElementId);

					if (!$this->exportRelation($relationNode, $propertyNode)) {
						return true;
					}

					$relationsToExport[] = $relationNode;
				}
			}

			$permissionsToExport = [];

			if ((!$this->ignoreRelations || in_array('permissions', $this->saveRelations)) && !$this->ignorePermissions) {
				$permissions = permissionsCollection::getInstance()->getRecordedPermissions($elementId);

				if (is_array($permissions)) {
					$permissionNode = $this->doc->createElement('permission');
					$permissionNode->setAttribute('page-id', $extElementId);

					foreach ($permissions as $key => $value) {
						$ownerNode = $this->doc->createElement('owner');

						if ($this->exportObject($key)) {
							if ($this->isLimitExceeded()) {
								$this->break = true;
								return true;
							}
						}

						$extKey = $this->relations->getOldObjectIdRelation($this->source_id, $key);
						$ownerNode->setAttribute('id', $extKey);
						$ownerNode->setAttribute('level', $value);
						$permissionNode->appendChild($ownerNode);
					}

					$permissionsToExport[] = $permissionNode;
				}
			}

			foreach ($relationsToExport as $relationNode) {
				$this->relations_container->appendChild($relationNode);
			}

			foreach ($optionsToExport as $entityNode) {
				$this->options_container->appendChild($entityNode);
			}

			foreach ($permissionsToExport as $permissionNode) {
				$this->permissions_container->appendChild($permissionNode);
			}

			if (!$this->ignoreRelations || in_array('hierarchy', $this->saveRelations)) {
				$ord = $element->getOrd();
				$hierarchyRelationNode = $this->doc->createElement('relation');
				$hierarchyRelationNode->setAttribute('id', $extElementId);
				$hierarchyRelationNode->setAttribute('ord', $ord);

				if ($parentId) {
					$hierarchyRelationNode->setAttribute('parent-id', $extParentId);
				} else {
					$hierarchyRelationNode->setAttribute('parent-id', 0);
				}

				$this->hierarchy_container->appendChild($hierarchyRelationNode);
			}

			$this->pages_container->appendChild($pageNode);
			$this->exported_elements[$elementId] = $extElementId;
			$this->position++;

			return true;
		}

		/**
		 * Экспортирует список торговых предложений
		 * @param DOMXPath $parser парсер экспортированных данных
		 * @param DOMElement $propertyNode узел значения поля
		 * @return $this
		 */
		protected function exportTradeOfferList(DOMXPath $parser, DOMElement $propertyNode) {

			$offerIdList = $this->extractTradeOfferIdList($parser, $propertyNode);

			if (count($offerIdList) > 0) {
				$this->addEntities([
					'TradeOfferExchange' => $offerIdList
				]);
				$this->exportEntities();
			}

			return $this;
		}

		/**
		 * Извлекает список идентификаторов торговых предложений из значения поля типа
		 * "Ссылка на список торговых предложений"
		 * @param DOMXPath $parser парсер экспортированных данных
		 * @param DOMElement $propertyNode узел значения поля
		 * @return array
		 */
		protected function extractTradeOfferIdList(DOMXPath $parser, DOMElement $propertyNode) {
			$offerIdList = [];

			/** @var DOMAttr $idAttribute */
			foreach ($parser->evaluate('value/offer/@id', $propertyNode) as $idAttribute) {
				$offerIdList[] = $idAttribute->value;
			}

			return $offerIdList;
		}

		/**
		 * Формирует узел связи с полем страницы
		 * @param string $fieldName имя поля
		 * @param int $pageId идентификатор страницы
		 * @return DOMElement
		 */
		protected function buildPageRelationNode($fieldName, $pageId) {
			return $this->buildRelationNode($fieldName, $pageId, 'page');
		}

		/**
		 * Формирует узел связи с полем объекта
		 * @param string $fieldName имя поля
		 * @param int $objectId идентификатор объекта
		 * @return DOMElement
		 */
		protected function buildObjectRelationNode($fieldName, $objectId) {
			return $this->buildRelationNode($fieldName, $objectId, 'object');
		}

		/**
		 * Формирует узел связи с полем сущности
		 * @param string $fieldName имя поля
		 * @param int $id идентификатор сущности
		 * @param string $type тип сущности (page/object)
		 * @return DOMElement
		 */
		protected function buildRelationNode($fieldName, $id, $type) {
			$relationNode = $this->doc->createElement('relation');
			$relationNode->setAttribute(sprintf('%s-id', $type), $id);
			$relationNode->setAttribute('field-name', $fieldName);
			return $relationNode;
		}

		/**
		 * Формирует узел опции страницы
		 * @param string $fieldName имя поля
		 * @param int $pageId идентификатор страницы
		 * @return DOMElement
		 */
		protected function buildPageOptionNode($fieldName, $pageId) {
			return $this->buildOptionNode($fieldName, $pageId, 'page');
		}

		/**
		 * Формирует узел опции объекта
		 * @param string $fieldName имя поля
		 * @param int $objectId идентификатор объекта
		 * @return DOMElement
		 */
		protected function buildObjectOptionNode($fieldName, $objectId) {
			return $this->buildOptionNode($fieldName, $objectId, 'object');
		}

		/**
		 * Формирует узел сущности
		 * @param string $fieldName имя поля
		 * @param int $id идентификатор сущности
		 * @param string $type тип сущности (page/object)
		 * @return DOMElement
		 */
		protected function buildOptionNode($fieldName, $id, $type) {
			$entityNode = $this->doc->createElement('entity');
			$entityNode->setAttribute(sprintf('%s-id', $type), $id);
			$entityNode->setAttribute('field-name', $fieldName);
			return $entityNode;
		}

		/**
		 * Экспортирует связь «справочник – поле» (I),
		 * либо значения полей типа relation с множественным или единственным выбором (II),
		 * либо значение полей типа symlink для страниц или объектов (III)
		 * либо значение полей типа offerIdList для страниц или объектов (IV)
		 * @param DOMElement $relationNode узел связи
		 * @param DOMElement $propertyNode узел значения поля
		 * @return bool
		 */
		protected function exportRelation(DOMElement $relationNode, DOMElement $propertyNode) {
			$this->exportRelationPropertyRelations($relationNode, $propertyNode);
			$this->exportSymlinkPropertyRelations($relationNode, $propertyNode);
			$this->exportOfferIdListPropertyRelations($relationNode, $propertyNode);
			return true;
		}

		/**
		 * Экспортирует значение поля типа symlink
		 * @param DOMElement $relationNode узел связи
		 * @param DOMElement $propertyNode узел значения поля
		 * @return bool
		 */
		protected function exportSymlinkPropertyRelations(DOMElement $relationNode, DOMElement $propertyNode) {
			$pageNodeList = $propertyNode->getElementsByTagName('page');

			/** @var DOMElement $pageNode */
			foreach ($pageNodeList as $pageNode) {
				if ($this->exportElement($pageNode->getAttribute('id'))) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return false;
					}
				}

				$pageId = $this->relations->getOldIdRelation($this->source_id, $pageNode->getAttribute('id'));
				$relationPageNode = $this->doc->createElement('page');
				$relationPageNode->setAttribute('id', $pageId);
				$pageNode->setAttribute('id', $pageId);

				if ($this->exportType($pageNode->getAttribute('type-id'))) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return false;
					}
				}

				$typeId = $this->relations->getOldTypeIdRelation($this->source_id, $pageNode->getAttribute('type-id'));
				$pageNode->setAttribute('type-id', $typeId);

				$parentId = $pageNode->getAttribute('parentId');
				if ($this->exportElement($parentId)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return false;
					}
				}

				$extParentId = $this->relations->getOldIdRelation($this->source_id, $parentId);
				$pageNode->setAttribute('parentId', $extParentId);

				$objectId = $pageNode->getAttribute('object-id');
				if ($this->exportObject($objectId)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return false;
					}
				}

				$extObjectId = $this->relations->getOldObjectIdRelation($this->source_id, $objectId);
				$pageNode->setAttribute('object-id', $extObjectId);

				$pageNode->removeAttribute('xlink:href');
				$pageNode->removeAttribute('update-time');

				if ($pageNode->getElementsByTagName('basetype')->length) {
					$basetypeNode = $pageNode->getElementsByTagName('basetype')->item(0);
					$basetypeNode->removeAttribute('id');
				}

				$relationNode->appendChild($relationPageNode);
			}
		}

		/**
		 * Экспортирует связь «справочник – поле» или значение поля типа relation
		 * @param DOMElement $relationNode узел связи
		 * @param DOMElement $propertyNode узел значения поля
		 * @return bool
		 */
		protected function exportRelationPropertyRelations(DOMElement $relationNode, DOMElement $propertyNode) {
			$itemNodeList = $propertyNode->getElementsByTagName('item');

			/** @var DOMElement $itemNode */
			foreach ($itemNodeList as $itemNode) {
				$itemId = $itemNode->getAttribute('id');
				if ($this->exportObject($itemId)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return false;
					}
				}

				$extItemId = $this->relations->getOldObjectIdRelation($this->source_id, $itemId);
				$itemNode->setAttribute('id', $extItemId);

				$itemType = $itemNode->getAttribute('type-id');
				if ($this->exportType($itemType)) {
					if ($this->isLimitExceeded()) {
						$this->break = true;
						return false;
					}
				}

				$extItemType = $this->relations->getOldTypeIdRelation($this->source_id, $itemType);
				$itemNode->setAttribute('type-id', $extItemType);

				if ($itemNode->hasAttribute('ownerId')) {
					$itemOwnerId = $itemNode->getAttribute('ownerId');
					if ($this->exportObject($itemOwnerId)) {
						if ($this->isLimitExceeded()) {
							$this->break = true;
							return false;
						}
					}

					$extItemOwnerId = $this->relations->getOldObjectIdRelation($this->source_id, $itemOwnerId);
					$itemNode->setAttribute('ownerId', $extItemOwnerId);
				}

				$itemNode->removeAttribute('xlink:href');
				$objectNode = $this->doc->createElement('object');
				$objectNode->setAttribute('id', $extItemId);
				$relationNode->appendChild($objectNode);
			}
		}

		/**
		 * Экспортирует значение поля типа offerIdList
		 * @param DOMElement $relationNode узел связи
		 * @param DOMElement $PropertyNode узел значения поля
		 * @return bool
		 */
		protected function exportOfferIdListPropertyRelations(DOMElement $relationNode, DOMElement $PropertyNode) {
			$offerNodeList = $PropertyNode->getElementsByTagName('offer');
			$internalOfferIdList = [];

			/** @var DOMElement $offerNode */
			foreach ($offerNodeList as $offerNode) {
				$internalOfferIdList[] = $offerNode->getAttribute('id');
			}

			try {
				$externalOfferIdList = $this->getService('TradeOfferExchange')
					->getExternalIdList($internalOfferIdList, $this);
			} catch (Exception $exception) {
				$this->reportError($exception->getMessage());
				return false;
			}

			foreach ($externalOfferIdList as $externalOfferId) {
				$offerNode = $this->doc->createElement('offer');
				$offerNode->setAttribute('id', $externalOfferId);
				$relationNode->appendChild($offerNode);
			}

			return true;
		}

		/** Экспортирует все объекты */
		protected function exportObjects() {
			foreach ($this->objects as $objectId) {
				if ($this->break) {
					return;
				}
				$this->exportObject($objectId);
			}
		}

		/**
		 * Экспортирует объект
		 * @param int $objectId ID объекта
		 * @return bool
		 */
		protected function exportObject($objectId) {
			$objectId = (int) $objectId;

			if (isset($this->exported_objects[$objectId])) {
				return false;
			}

			if ($this->isLimitExceeded()) {
				$this->break = true;
				return false;
			}

			$this->exported_objects[$objectId] = 'found';
			$object = umiObjectsCollection::getInstance()->getObject($objectId);

			if (!$object instanceof iUmiObject) {
				return false;
			}

			$typeId = $object->getTypeId();

			if ($this->exportType($typeId)) {
				if ($this->isLimitExceeded()) {
					$this->break = true;
					return true;
				}
			}

			$objectNode = $this->doc->createElement('object');
			$this->translateEntity($object, $objectNode);

			$extObjectId = $this->relations->getOldObjectIdRelation($this->source_id, $objectId);
			if ($extObjectId === false) {
				$this->relations->setObjectIdRelation($this->source_id, $objectId, $objectId);
				$extObjectId = $objectId;
			} else {
				$objectNode->setAttribute('id', $extObjectId);
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($typeId);
			$typeGuid = ($type instanceof iUmiObjectType) ? $type->getGUID() : '';

			if (in_array('objects', $this->installOnly) && $typeGuid != 'tax-rate-guide') {
				$installOnlyAttribute = $this->doc->createAttribute('install-only');
				$installOnlyAttribute->value = '1';
				$objectNode->appendChild($installOnlyAttribute);
			}

			$extTypeId = $this->relations->getOldTypeIdRelation($this->source_id, $typeId);
			$objectNode->setAttribute('type-id', $extTypeId);

			$parser = new DOMXPath($this->doc);

			/** @var DOMElement $groupNode */
			foreach ($parser->evaluate('properties/group', $objectNode) as $groupNode) {
				if ($groupNode->hasAttribute('id')) {
					$groupNode->removeAttribute('id');
				}
			}

			$relationsToExport = [];
			$optionsToExport = [];

			/** @var DOMElement $propertyNode */
			foreach ($parser->evaluate('properties/group/property', $objectNode) as $propertyNode) {
				$fieldId = (int) $propertyNode->getAttribute('id');
				$fieldType = $propertyNode->getAttribute('type');

				$extFieldName = $this->relations->getOldFieldName($this->source_id, $typeId, $fieldId);
				if ($extFieldName) {
					$propertyNode->setAttribute('name', $extFieldName);
				}

				if ($fieldType == 'relation' && $this->shouldExportRelations()) {
					$relationNode = $this->buildObjectRelationNode($extFieldName, $extObjectId);

					if (!$this->exportRelation($relationNode, $propertyNode)) {
						return true;
					}

					$relationsToExport[] = $relationNode;
				}

				if ($fieldType == 'symlink' && $this->shouldExportSymlinks()) {
					$relationNode = $this->buildObjectRelationNode($extFieldName, $extObjectId);

					if (!$this->exportRelation($relationNode, $propertyNode)) {
						return true;
					}

					$relationsToExport[] = $relationNode;
				}

				if ($fieldType == 'optioned' && $this->shouldExportOptions()) {
					$entityNode = $this->buildObjectOptionNode($extFieldName, $extObjectId);

					if (!$this->exportOptions($propertyNode, $entityNode)) {
						return true;
					}

					$optionsToExport[] = $entityNode;
				}

				if ($fieldType == 'offer_id_list' || $fieldType == 'offer_id') {
					$this->exportTradeOfferList($parser, $propertyNode);
					$relationNode = $this->buildObjectRelationNode($extFieldName, $extObjectId);

					if (!$this->exportRelation($relationNode, $propertyNode)) {
						return true;
					}

					$relationsToExport[] = $relationNode;
				}
			}

			$permissionsToExport = [];
			$connection = ConnectionPool::getInstance()->getConnection();

			if ((!$this->ignoreRelations || in_array('permissions', $this->saveRelations)) && !$this->ignorePermissions) {
				$permissionNode = $this->doc->createElement('permission');
				$sql = "SELECT `module`, `method`, `allow` FROM cms_permissions WHERE owner_id = '{$objectId}'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				foreach ($result as $row) {
					$moduleNode = $this->doc->createElement('module');
					$moduleNode->setAttribute('name', array_shift($row));
					$moduleNode->setAttribute('method', array_shift($row));
					$moduleNode->setAttribute('allow', array_shift($row));

					if (in_array('modules_permissions', $this->installOnly)) {
						$moduleNode->setAttribute('install-only', '1');
					}

					$permissionNode->appendChild($moduleNode);
				}

				$permissionNode->setAttribute('object-id', $extObjectId);

				if ($objectNode->hasAttribute('ownerId')) {
					$ownerId = $object->getOwnerId();

					if ($this->exportObject($ownerId)) {
						if ($this->isLimitExceeded()) {
							$this->break = true;
							return true;
						}
					}

					$extOwnerId = $this->relations->getOldObjectIdRelation($this->source_id, $ownerId);
					$objectNode->setAttribute('ownerId', $extOwnerId);
					$ownerNode = $this->doc->createElement('owner');
					$ownerNode->setAttribute('id', $extOwnerId);
					$permissionNode->appendChild($ownerNode);
				}

				$permissionsToExport[] = $permissionNode;
			}

			foreach ($relationsToExport as $relationNode) {
				$this->relations_container->appendChild($relationNode);
			}

			foreach ($optionsToExport as $entityNode) {
				$this->options_container->appendChild($entityNode);
			}

			foreach ($permissionsToExport as $permissionNode) {
				$this->permissions_container->appendChild($permissionNode);
			}

			$this->objects_container->appendChild($objectNode);
			$this->exported_objects[$objectId] = $objectId;
			$this->position++;
			return true;
		}

		/**
		 * Экспортирует все опции опционного свойства
		 * @param DOMElement $field узел свойства
		 * @param DOMElement $entityNode родительский узел опций
		 * @return bool
		 */
		protected function exportOptions($field, $entityNode) {
			$optionNodeList = $field->getElementsByTagName('option');

			/** @var DOMElement $fieldOptionNode */
			foreach ($optionNodeList as $fieldOptionNode) {
				$optionNode = $this->doc->createElement('option');

				if ($fieldOptionNode->hasAttributes()) {
					foreach ($fieldOptionNode->attributes as $attribute) {
						$optionNode->setAttribute($attribute->name, $attribute->value);
					}
				}

				if ($fieldOptionNode->getElementsByTagName('object')->length) {
					$object = $fieldOptionNode->getElementsByTagName('object')->item(0);

					if ($this->exportObject($object->getAttribute('id'))) {
						if ($this->isLimitExceeded()) {
							$this->break = true;
							return false;
						}
					}

					$id = $this->relations->getOldObjectIdRelation($this->source_id, $object->getAttribute('id'));
					$optionNode->setAttribute('object-id', $id);
					$object->setAttribute('id', $id);

					if ($this->exportType($object->getAttribute('type-id'))) {
						if ($this->isLimitExceeded()) {
							$this->break = true;
							return false;
						}
					}

					$typeId = $this->relations->getOldTypeIdRelation($this->source_id, $object->getAttribute('type-id'));
					$object->setAttribute('type-id', $typeId);

					if ($object->hasAttribute('ownerId')) {
						$ownerId = $object->getAttribute('ownerId');

						if ($ownerId) {
							if ($this->isLimitExceeded()) {
								$this->break = true;
								return false;
							}
						}

						$this->exportObject($ownerId);
						$extOwnerId = $this->relations->getOldObjectIdRelation($this->source_id, $ownerId);
						$object->setAttribute('ownerId', $extOwnerId);
						$object->removeAttribute('xlink:href');
					}
				}

				if ($fieldOptionNode->getElementsByTagName('page')->length) {
					$page = $fieldOptionNode->getElementsByTagName('page')->item(0);

					if ($this->exportElement($page->getAttribute('id'))) {
						if ($this->isLimitExceeded()) {
							$this->break = true;
							return false;
						}
					}

					$id = $this->relations->getOldIdRelation($this->source_id, $page->getAttribute('id'));
					$optionNode->setAttribute('page-id', $id);
					$page->setAttribute('id', $id);

					if ($this->exportType($page->getAttribute('type-id'))) {
						if ($this->isLimitExceeded()) {
							$this->break = true;
							return false;
						}
					}

					$typeId = $this->relations->getOldTypeIdRelation($this->source_id, $page->getAttribute('type-id'));
					$page->setAttribute('type-id', $typeId);
					$page->removeAttribute('xlink:href');
					$page->removeAttribute('update-time');
				}

				$entityNode->appendChild($optionNode);
			}

			return true;
		}

		/**
		 * Нужно ли выгружать права на все модули и методы для всех
		 * пользователей (из таблицы cms_permissions).
		 * @return bool
		 */
		private function isNeedToExportAllModuleMethodPermissions() {
			return $this->exportAllModuleMethodPermissions;
		}

		/**
		 * Определяет был ли объект экспортирован
		 * @param int $id идентификатор объекта
		 * @return bool
		 */
		private function isObjectHasBeenExported($id) {
			return $this->relations->getOldObjectIdRelation($this->source_id, $id) !== false;
		}

		/**
		 * Получает права на модули и методы для всех пользователей и групп
		 * и формирует соответствующие узлы в результирующем документе.
		 * Возвращает результат проведения операции.
		 * @return bool
		 */
		private function exportModuleMethodPermissions() {
			if (!$this->doc instanceof DOMDocument) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT `module`, `method`, `allow`, `owner_id` FROM `cms_permissions` ORDER BY `owner_id`';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return true;
			}

			$permissions = [];
			$installOnly = in_array('modules_permissions', $this->installOnly);

			foreach ($result as $row) {
				$ownerId = isset($row['owner_id']) ? $row['owner_id'] : null;

				if (!$this->isObjectHasBeenExported($ownerId)) {
					continue;
				}

				$moduleName = isset($row['module']) ? $row['module'] : null;
				$method = isset($row['method']) ? $row['method'] : null;
				$allow = isset($row['allow']) ? $row['allow'] : null;

				/* @var DOMElement $moduleNode */
				$moduleNode = $this->doc->createElement('module');
				$moduleNode->setAttribute('name', $moduleName);
				$moduleNode->setAttribute('method', $method);
				$moduleNode->setAttribute('allow', $allow);

				if ($installOnly) {
					$moduleNode->setAttribute('install-only', '1');
				}

				if (isset($permissions[$ownerId])) {
					$permissions[$ownerId]->appendChild($moduleNode);
				} else {
					/* @var DOMElement $permissionNode */
					$permissionNode = $this->doc->createElement('permission');
					$permissionNode->setAttribute('object-id', $ownerId);
					$permissionNode->appendChild($moduleNode);
					$permissions[$ownerId] = $permissionNode;
				}
			}

			/* @var DOMElement $permissionNode */
			foreach ($permissions as $permissionNode) {
				if ($permissionNode->hasChildNodes()) {
					$this->permissions_container->appendChild($permissionNode);
				}
			}

			return true;
		}

		/**
		 * Определяет, нужно ли экспортировать значения полей типа 'relation'
		 * при экспорте страниц/объектов.
		 * @return bool
		 */
		private function shouldExportRelations() {
			return
				!$this->ignoreRelations ||
				in_array('fields_relations', $this->saveRelations) ||
				in_array('relations', $this->saveRelations);
		}

		/**
		 * Определяет, нужно ли экспортировать значения полей типа 'symlink'
		 * при экспорте страниц/объектов.
		 * @return bool
		 */
		private function shouldExportSymlinks() {
			return
				!$this->ignoreRelations ||
				in_array('fields_relations', $this->saveRelations) ||
				in_array('symlinks', $this->saveRelations);
		}

		/**
		 * Определяет, нужно ли экспортировать значения полей типа 'optioned'
		 * при экспорте страниц/объектов.
		 * @return bool
		 */
		private function shouldExportOptions() {
			return
				!$this->ignoreRelations ||
				in_array('fields_relations', $this->saveRelations) ||
				in_array('options', $this->saveRelations);
		}
	}
