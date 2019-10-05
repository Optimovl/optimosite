<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher;

	use UmiCms\System\Import\UmiDump\Demolisher\Type\iFactory;

	/**
	 * Класс исполнителя удаления данных
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	class Executor implements iExecutor, iFileSystem {

		/** @var \DOMXPath $parser парсер документа в формате umiDump */
		private $parser;

		/** @var iFactory $factory экземпляр фабрики классов удаления группы однородных данных */
		private $factory;

		/** @var string $rootPath абсолютный путь до корневой директории системы */
		private $rootPath;

		/** @var int $sourceId идентификатор источника данных */
		private $sourceId;

		/** @var string[] $log журнал удаления */
		private $log = [];

		/**
		 * Конструктор
		 * @param iFactory $factory
		 */
		public function __construct(iFactory $factory) {
			$this->factory = $factory;
		}

		/** @inheritdoc */
		public function run(\DOMXPath $parser) {
			$this->setParser($parser);

			foreach ($this->getManifest() as $name => $xpathList) {
				foreach ($xpathList as $xpath) {
					if ($this->nodeExists($xpath)) {
						$this->execute($name);
						continue 2;
					}
				}
			}

			$log = $this->getLog();
			$this->clearLog();
			return $log;
		}

		/** @inheritdoc */
		public function setRootPath($path) {
			$this->rootPath = $path;
			return $this;
		}

		/** @inheritdoc */
		public function setSourceId($id) {
			$this->sourceId = (int) $id;
			return $this;
		}

		/**
		 * Возвращает идентификатор источника данных
		 * @return int
		 */
		private function getSourceId() {
			return $this->sourceId;
		}

		/**
		 * Устанавливает парсер документа в формате umiDump
		 * @param \DOMXPath $parser парсер документа в формате umiDump
		 * @return iExecutor
		 */
		private function setParser(\DOMXPath $parser) {
			$this->parser = $parser;
			return $this;
		}

		/**
		 * Возвращает манифест удаления данных
		 * @return array
		 *
		 * [
		 *      'имя группы удаляемых однородных данных' => [
		 *          'xpath запрос на получение списка удаляемых данных'
		 *      ]
		 * ]
		 */
		private function getManifest() {
			return [
				'File' => [
					'/umidump/files/file'
				],
				'Directory' => [
					'/umidump/directories/directory'
				],
				'Field' => [
					'/umidump/types/type/fieldgroups/group/field',
					'/umidump/pages/page/properties/group/property',
					'/umidump/objects/object/properties/group/property'
				],
				'FieldGroup' => [
					'/umidump/types/type/fieldgroups/group',
					'/umidump/pages/page/properties/group',
					'/umidump/objects/object/properties/group'
				],
				'Permission' => [
					'/umidump/permissions/permission'
				],
				'Object' => [
					'/umidump/objects/object'
				],
				'ObjectType' => [
					'/umidump/types/type'
				],
				'Page' => [
					'/umidump/pages/page'
				],
				'Domain' => [
					'/umidump/domains/domain'
				],
				'Template' => [
					'/umidump/templates/template'
				],
				'Language' => [
					'/umidump/langs/lang'
				],
				'Registry' => [
					'/umidump/registry/key'
				],
				'Entity' => [
					'/umidump/entities/entity'
				],
				'Restriction' => [
					'/umidump/restrictions/restriction'
				]
			];
		}

		/**
		 * Определяет существует ли узел
		 * @param string $xpath запрос узла
		 * @return bool
		 */
		private function nodeExists($xpath) {
			return $this->getParser()
					->query($xpath)->length > 0;
		}

		/**
		 * Выполняет удаление группы однородных данных
		 * @param string $name имя группы
		 */
		private function execute($name) {
			try {
				$demolisher = $this->getFactory()
					->create($name);

				if ($demolisher instanceof iFileSystem) {
					$demolisher->setRootPath($this->getRootPath());
				}

				$demolisher->setSourceId($this->getSourceId());
				$log = $demolisher->run($this->getParser());
				$this->mergeLog($log);
			} catch (\Exception $exception) {
				$this->pushLog($exception->getMessage());
			}
		}

		/**
		 * Возвращает парсер документа в формате umiDump
		 * @return \DOMXPath
		 */
		private function getParser() {
			return $this->parser;
		}

		/**
		 * Возвращает экземпляр фабрики классов удаления группы однородных данных
		 * @return iFactory
		 */
		private function getFactory() {
			return $this->factory;
		}

		/**
		 * Возвращает журнал
		 * @return \string[]
		 */
		private function getLog() {
			return $this->log;
		}

		/**
		 * Производит слияние журналов
		 * @param array $log слияемый журнал
		 * @return iExecutor
		 */
		private function mergeLog(array $log) {
			$this->log = array_merge($this->log, $log);
			return $this;
		}

		/**
		 * Очищает журнал
		 * @return iExecutor
		 */
		private function clearLog() {
			$this->log = [];
			return $this;
		}

		/**
		 * Помещает сообщение в журнал
		 * @param string $message сообщение
		 * @return iExecutor
		 */
		private function pushLog($message) {
			$this->log[] = $message;
			return $this;
		}

		/**
		 * Возвращает абсолютный путь до корневой директории системы
		 * @return string
		 */
		private function getRootPath() {
			return $this->rootPath;
		}
	}
