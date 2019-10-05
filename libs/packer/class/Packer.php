<?php

	use UmiCms\Service;

	/** Упаковщик решений для umi.cms */
	class Packer {

		/** @var array конфигурация упаковщика */
		private $config;

		/** @var xmlExporter экспортер */
		private $exporter;

		/** @var array список типов по модулям */
		private $objectTypes = [];

		/**
		 * Конструктор.
		 * @param string $configFilePath путь до конфигурации пакера
		 * @throws RuntimeException в случае если не передана конфигурация
		 */
		public function __construct($configFilePath) {
			if (!$configFilePath) {
				throw new RuntimeException('Не передан файл конфигурации.');
			}

			if (file_exists(realpath($configFilePath))) {
				$this->config = require realpath($configFilePath);
				return;
			}

			if (file_exists(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $configFilePath)) {
				$this->config = require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $configFilePath;
				return;
			}

			throw new RuntimeException('Передан путь до несуществующего файла.');
		}

		/** @param xmlExporter $exporter экспортер */
		public function setExporter(xmlExporter $exporter) {
			$this->exporter = $exporter;
		}

		/**
		 * Возвращает свойство конфигурации.
		 * @param string $name имя свойства
		 * @param mixed $default значение возвращаемое по умолчанию
		 * @return mixed
		 */
		public function getConfig($name, $default = null) {
			return isset($this->config[$name]) ? $this->config[$name] : $default;
		}

		/** Запускает упаковщик. */
		public function run() {
			if (!$this->exporter instanceof xmlExporter) {
				throw new RuntimeException('Непредвиденная ошибка.');
			}

			if (!$this->getConfig('package')) {
				throw new RuntimeException('Ключ "package" обязателен для заполнения.');
			}

			$destination = new umiDirectory($this->getDestinationDir());
			$destination->deleteRecursively();

			$savedRelations = $this->getConfig('savedRelations');
			if (is_array($savedRelations)) {
				$this->exporter->ignoreRelationsExcept($savedRelations);
			} else {
				$this->exporter->setIgnoreRelations();
			}

			$this->exporter->setFieldsAllowRuntimeAdd();
			$this->packComponent();

			$umiDumpPath = $destination->getPath() . '/' . $this->getConfig('package') . '.xml';
			$this->exporter
				->execute()
				->save($umiDumpPath);

			$relationKey = 'savedRelations';
			$relationOptions = is_array($this->getConfig($relationKey)) ? $this->getConfig($relationKey) : [];

			if (in_array('files', $relationOptions)) {
				$this->packFilesFromUmiDump($umiDumpPath);
			}

			$this->addFileToArchive(
				new SplFileInfo($umiDumpPath),
				$this->getArchive(),
				'./' . $this->getConfig('package') . '.xml'
			);
		}

		/** Упаковывает компонент. */
		private function packComponent() {
			if (!$this->getConfig('directories') && !$this->getConfig('files')) {
				throw new RuntimeException(
					'Хотя бы один из ключей "directories" или "files" должен быть заполнен в конфигурации.'
				);
			}

			$this->packDirectories();
			$this->packFiles();
			$this->packRegistry();
			$this->addTypes();
			$this->addDataTypes();
			$this->addObjects();
			$this->addBranchesStructure();
			$this->addLangs();
			$this->addTemplates();
			$this->addEntities();
		}

		/**
		 * Добавляет директории в файл экспорта.
		 * @param array $directories пути до директорий
		 */
		private function addDirectories(array $directories) {
			$destination = $this->getDestinationDir();
			$this->exporter->setDestination($destination);
			$this->exporter->addDirs($directories);
		}

		/**
		 * Добавляет файлы в файл экспорта.
		 * @param array $files пути до файлов
		 */
		private function addFiles(array $files) {
			$destination = $this->getDestinationDir();
			$this->exporter->setDestination($destination);
			$this->exporter->addFiles($files);
		}

		/**
		 * Упаковывает директории.
		 * В конфигурационном файле необходимо указать массив путей до директорий
		 * относительно корневой директории системы.
		 * @throws RuntimeException в случае если не задана директория для выходных файлов
		 */
		private function packDirectories() {
			$directoryList = $this->getConfig('directories');
			if (!is_array($directoryList)) {
				return;
			}

			$archive = $this->getArchive();
			$filesInArchive = [];
			$directoriesInArchive = [];

			foreach ($directoryList as $directory) {
				$objects = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($directory),
					RecursiveIteratorIterator::SELF_FIRST
				);

				$directoriesInArchive[] = strtr($directory, '\\', '/');

				foreach ($objects as $object) {
					if (!$object->isDir()) {
						$filesInArchive[] = $this->addFileToArchive($object, $archive);
					} elseif (!in_array($object->getFilename(), ['.', '..'])) {
						$directoriesInArchive[] = $object->getPathname();
					}
				}
			}

			$this->addFiles($filesInArchive);
			$this->addDirectories($directoriesInArchive);
		}

		/**
		 * Упаковывает файлы.
		 * В конфигурационном файле необходимо указать массив путей до файлов
		 * относительно корневой директории системы.
		 * @throws RuntimeException в случае если не задана директория для выходных файлов
		 */
		private function packFiles() {
			$filePathList = $this->getConfig('files');
			if (!is_array($filePathList)) {
				return;
			}

			$archive = $this->getArchive();
			$filesInArchive = [];

			foreach ($filePathList as $filePath) {
				$file = new SplFileInfo($filePath);
				if (!$file->isDir()) {
					$filesInArchive[] = $this->addFileToArchive($file, $archive);
				}
			}

			$this->addFiles($filesInArchive);
		}

		/**
		 * Добавляет в tar архив все файлы, которые заданы в umiDump.
		 * В umiDump могут попасть файлы, которые отсутствуют в конфиге пакера.
		 * @param string $umiDumpPath путь до файла с umiDump
		 */
		private function packFilesFromUmiDump($umiDumpPath) {
			$umiDump = new DOMDocument('1.0', 'utf-8');
			if (!$umiDump->load($umiDumpPath)) {
				throw new RuntimeException('UmiDump сформирован неправильно: ' . $umiDumpPath);
			}

			$query = new DOMXPath($umiDump);
			$archive = $this->getArchive();

			/** @var DOMAttr $path */
			foreach ($query->evaluate('/umidump/files/file/@path') as $path) {
				$file = new SplFileInfo($path->nodeValue);
				if ($file->isFile()) {
					$filesTar[] = $this->addFileToArchive($file, $archive);
				}
			}
		}

		/**
		 * Возвращает директорию для хранения запакованного решения.
		 * @throws RuntimeException
		 * @return string
		 */
		private function getDestinationDir() {
			$destination = $this->getConfig('destination');
			if (!$destination) {
				throw new RuntimeException('Ключ "destination" обязателен для заполнения.');
			}

			if (!file_exists($destination)) {
				mkdir($destination, 0777, true);
			}

			return $destination;
		}

		/**
		 * Возвращает ключи реестра.
		 * @param string $component имя компонента
		 * @param string $parentPath путь в реестре
		 * @param bool $recursive рекурсивный выбор
		 * @return array
		 */
		private function getRegistryList($component = 'core', $parentPath = '//', $recursive = true) {
			if ($component == 'core' && startsWith($parentPath, 'modules')) {
				return [];
			}

			$paths = [];
			$children = Service::Registry()
				->getList($parentPath);

			if (!is_array($children)) {
				return $paths;
			}

			foreach ($children as $child) {

				if ($parentPath != '//') {
					$childPath = $parentPath . '/' . $child[0];
				} else {
					$childPath = $child[0];
				}

				$paths[] = $childPath;

				if ($recursive) {
					$paths = array_merge($paths, $this->getRegistryList($component, $childPath));
				}
			}

			return $paths;
		}

		/**
		 * Упаковывает объектные типы данных
		 * и сущности (объекты и страницы), связанные с этими типами.
		 * В конфигурационном файле необходимо указать массив идентификаторов объектных типов данных.
		 */
		private function addTypes() {
			$this->detectComponents();
			$this->sortObjectTypesByModule();

			/** @var int[] $typeIdList */
			$typeIdList = $this->getConfig('types', []);
			$this->exporter->addTypes($typeIdList);

			foreach ($typeIdList as $typeId) {
				$this->addObjectsOrPagesWithType($typeId);
			}

			$this->exporter->setShowAllFields(true);
		}

		/** Получает список модулей в системе. */
		private function detectComponents() {
			$this->objectTypes['core'] = [];

			$modulesList = Service::Registry()
				->getList('//modules');

			foreach ($modulesList as $moduleName) {
				list($moduleName) = $moduleName;
				$this->objectTypes[$moduleName] = [];
			}
		}

		/**
		 * Строит иерархию типов объектов по модулям.
		 * @param int $parentType
		 */
		private function sortObjectTypesByModule($parentType = 0) {
			$collection = umiObjectTypesCollection::getInstance();
			$types = $collection->getSubTypesList($parentType);

			foreach ($types as $typeId) {
				$type = $collection->getType($typeId);
				$module = $type->getModule();

				if (!$module) {
					$module = 'core';
				}

				if (!isset($this->objectTypes[$module])) {
					continue;
				}

				$this->objectTypes[$module][] = $typeId;
				$this->sortObjectTypesByModule($typeId);
			}
		}

		/**
		 * Упаковывает сущности (объекты и страницы), связанные с объектным типом данных.
		 * @param int $typeId идентификатор типа данных
		 */
		private function addObjectsOrPagesWithType($typeId) {
			$pages = new selector('pages');
			$pages->types('object-type')->id($typeId);

			if ($pages->length() > 0) {
				$this->exporter->addElements($pages->result());
				return;
			}

			$objects = new selector('objects');
			$objects->types('object-type')->id($typeId);
			$this->exporter->addObjects($objects->result());
		}

		/**
		 * Добавляет файл в Tar архив.
		 * @param SplFileInfo $file добавляемый файл
		 * @param PharData $archive архив, в который необходимо добавить файл
		 * @param null|string $localPath локальный путь до добавляемого файла
		 * @return string
		 */
		private function addFileToArchive(SplFileInfo $file, PharData $archive, $localPath = null) {
			$path = $file->getPathname();
			echo $path . PHP_EOL;

			$localPath = $localPath ?: strtr($path, '\\', '/');
			$archive->addFile($path, $localPath);
			return $path;
		}

		/**
		 * Упаковывает реестр.
		 * В конфигурационном файле необходимо указать массив:
		 *
		 * [
		 *      "имя модуля" => [
		 *          "ключ реестра" = "значение реестра"
		 *      ]
		 * ]
		 */
		private function packRegistry() {
			$registryList = $this->getConfig('registry');

			if (!is_array($registryList)) {
				return;
			}

			foreach ($registryList as $module => $registry) {
				$this->exporter->addRegistry(
					$this->getRegistryList(
						$module,
						$registry['path'],
						isset($registry['recursive']) ? $registry['recursive'] : true
					)
				);
			}
		}

		/**
		 * Упаковывает типы полей.
		 * В конфигурационном файле необходимо указать массив идентификаторов типов полей.
		 */
		private function addDataTypes() {
			$fieldTypeIdList = $this->getConfig('fieldTypes');

			if (!is_array($fieldTypeIdList)) {
				return;
			}

			$this->exporter->addDataTypes($fieldTypeIdList);
		}

		/**
		 * Упаковывает объекты.
		 * В конфигурационном файле необходимо указать массив идентификаторов объектов.
		 */
		private function addObjects() {
			$objectIdList = $this->getConfig('objects');

			if (!is_array($objectIdList)) {
				return;
			}

			$this->exporter->addObjects($objectIdList);
			$this->exporter->setShowAllFields(true);
		}

		/**
		 * Упаковывает корневые страницы.
		 * В конфигурационном файле необходимо указать массив идентификаторов родительских элементов.
		 */
		private function addBranchesStructure() {
			$pageIdList = $this->getConfig('branchesStructure');

			if (!is_array($pageIdList)) {
				return;
			}

			$this->exporter->addBranches($pageIdList);
		}

		/**
		 * Упаковывает языки.
		 * В конфигурационном файле необходимо указать массив идентификаторов языков.
		 */
		private function addLangs() {
			$languageList = $this->getConfig('langs');

			if (!is_array($languageList)) {
				return;
			}

			$this->exporter->addLangs($languageList);
		}

		/**
		 * Упаковывает шаблоны.
		 * В конфигурационном файле необходимо указать массив идентификаторов шаблонов.
		 */
		private function addTemplates() {
			$templateIdList = $this->getConfig('templates');

			if (!is_array($templateIdList)) {
				return;
			}

			$this->exporter->addTemplates($templateIdList);
		}

		/**
		 * Упаковывает нестандартные сущности.
		 * В конфигурационном файле необходимо указать массив идентификаторов сущностей с названиями сервисов.
		 *
		 * [
		 *      'modules_to_load' => [ // если для инициализации сервиса требуется загрузить некоторый модуль
		 *            'service1' => 'module1'
		 *        ],
		 *      'service1' => [
		 *          1, 2, 3, 4, 5
		 *      ],
		 *      'service2' => [
		 *          1, 2, 3, 4, 5
		 *      ]
		 * ]
		 */
		private function addEntities() {
			$entitiesList = $this->getConfig('entities');

			if (!is_array($entitiesList)) {
				return;
			}

			$this->exporter->addEntities($entitiesList);
		}

		/**
		 * Возвращает архив с готовым решением
		 * @return PharData
		 */
		private function getArchive() {
			$destination = $this->getDestinationDir();
			return new PharData("{$destination}/{$this->getConfig('package')}.tar");
		}
	}
