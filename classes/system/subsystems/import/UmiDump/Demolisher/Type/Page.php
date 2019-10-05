<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления страниц
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Page extends Demolisher {

		/** @var \iUmiHierarchy $pageCollection коллекция страниц */
		private $pageCollection;

		/**
		 * Конструктор
		 * @param \iUmiHierarchy $pageCollection коллекция страниц
		 */
		public function __construct(\iUmiHierarchy $pageCollection) {
			$this->pageCollection = $pageCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$pageCollection = $this->getPageCollection();

			foreach ($this->getPageExtIdList() as $extId) {
				$id = $sourceIdBinder->getNewIdRelation($sourceId, $extId);

				if ($id === false) {
					$this->pushLog(sprintf('Page "%s" was ignored', $extId));
					continue;
				}

				$pageCollection->killElement($id);
				$this->pushLog(sprintf('Page "%s" was deleted', $id));
			}
		}

		/**
		 * Возвращает список внешних идентификаторов удаляемых страниц
		 * @return string[]
		 */
		private function getPageExtIdList() {
			return $this->getNodeValueList('/umidump/pages/page/@id');
		}

		/**
		 * Возвращает коллекцию страниц
		 * @return \iUmiHierarchy
		 */
		private function getPageCollection() {
			return $this->pageCollection;
		}
	}
