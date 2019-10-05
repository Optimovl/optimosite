<?php

	/** Быстрый csv экспортер объектов */
	class QuickCsvObjectExporter extends csvExporter {

		/** @var array $fieldNameWhiteList список разрешенных имен полей */
		private $fieldNameWhiteList = [];

		/**
		 * Устанавливает список разрешенных имен полей
		 * @param array $whiteList список разрешенных имен полей
		 * @return $this
		 */
		public function setFieldNameWhiteList(array $whiteList) {
			$this->fieldNameWhiteList = $whiteList;
			return $this;
		}

		/** @inheritdoc */
		protected function initializeExporter() {
			$this->exporter = $this->createXmlExporter($this->sourceName, $this->getLimit());
			$this->exporter->ignoreRelationsExcept('options');

			$objectIdList = $this->entityIdList;

			if (!$this->shouldFlushToBuffer) {
				$objectIdList = array_slice($objectIdList, 0, $this->getLimit() + 1);
			}

			$this->exporter->addObjects($objectIdList);
			$this->exporter->setSerializeOption('field-name-white-list', $this->getFieldNameWhiteList());
		}

		/**
		 * Загружает объекты, которые нужно экспортировать
		 * @param iUmiObject[]|int[] $exportList список экспортируемых объектов
		 * @param mixed $ignoreList не используется
		 * @throws selectorException
		 */
		protected function loadEntityIdList($exportList, $ignoreList) {
			$this->entityIdList = [];

			if (file_exists($this->entityIdListFilePath)) {
				$this->entityIdList = unserialize(file_get_contents($this->entityIdListFilePath));
				return;
			}

			$objectIdList = $this->getObjectIdList($exportList);

			foreach ($objectIdList as $id) {
				$this->entityIdList[$id] = $id;
			}
		}

		/** @inheritdoc */
		protected function getPropertyList(DOMDocument $document) {
			$pagesProperties = [];
			$xpath = new DOMXPath($document);
			$pageNodes = $xpath->query('/umidump/objects/object');

			/** @var DOMElement $node */
			foreach ($pageNodes as $node) {
				$pagesProperties[] = $this->getEntityPropertyList($node);
			}

			return $pagesProperties;
		}

		/**
		 * Возвращает системные свойства объекта
		 * @param DOMElement $object узел объекта в формате UMIDUMP
		 * @return mixed
		 */
		protected function getSystemProperties(DOMElement $object) {
			$id = $object->getAttribute('id');
			$name = $object->getAttribute('name');
			$typeId = $object->getAttribute('type-id');

			return [
				$id,
				$name,
				$typeId
			];
		}

		/** @inheritdoc */
		protected function loadSystemHeaders() {
			$this->nameList = [
				'id',
				'name',
				'type-id'
			];
			$this->titleList = [
				getLabel('csv-property-id', 'exchange'),
				getLabel('csv-property-name', 'exchange'),
				getLabel('csv-property-type-id', 'exchange')
			];
			$this->typeList = [
				'native',
				'native',
				'native'
			];
		}

		/** @inheritdoc */
		protected function updateEntityIdList() {
			$exportedPages = array_keys($this->exporter->getExportedObject());
			$this->entityIdList = array_diff($this->entityIdList, $exportedPages);

			if (!$this->shouldFlushToBuffer) {
				$this->saveEntityIdList();
			}
		}

		/**
		 * Возвращает список идентификаторов объектов
		 * @param int[]|iUmiObject[] $branchList
		 * @return int[]
		 */
		private function getObjectIdList($branchList) {
			$objectCollection = umiObjectsCollection::getInstance();
			$objectIdList = [];

			foreach ($branchList as $branch) {
				if (!$branch instanceof iUmiObject) {
					$branch = $objectCollection->getObject($branch);
				}

				if (!$branch instanceof iUmiObject) {
					continue;
				}

				$objectIdList[] = $branch->getId();
			}

			return $objectIdList;
		}

		/**
		 * Возвращает список разрешенных имен полей
		 * @return array
		 */
		private function getFieldNameWhiteList() {
			return $this->fieldNameWhiteList;
		}
	}
