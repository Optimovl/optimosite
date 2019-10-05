<?php

	/** Быстрый csv экспортер страниц */
	class QuickCsvPageExporter extends csvExporter {

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
			parent::initializeExporter();
			$this->exporter->setSerializeOption('field-name-white-list', $this->getFieldNameWhiteList());
		}

		/**
		 * @inheritdoc
		 * Не учитывает вложенные страницы.
		 */
		protected function getEntityIdList($exportList) {
			$pageCollection = umiHierarchy::getInstance();
			$pageIdList = [];

			foreach ($exportList as $branch) {
				if (!$branch instanceof iUmiHierarchyElement) {
					$branch = $pageCollection->getElement($branch, true, true);
				}

				if (!$branch instanceof iUmiHierarchyElement) {
					continue;
				}

				$pageIdList[] = $branch->getId();
			}

			return $pageIdList;
		}

		/**
		 * Возвращает список разрешенных имен полей
		 * @return array
		 */
		private function getFieldNameWhiteList() {
			return $this->fieldNameWhiteList;
		}
	}
