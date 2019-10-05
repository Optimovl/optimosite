<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления языков
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Language extends Demolisher {

		/** @var \iLangsCollection $languageCollection коллекция языков */
		private $languageCollection;

		/**
		 * Конструктор
		 * @param \iLangsCollection $languageCollection коллекция языков
		 */
		public function __construct(\iLangsCollection $languageCollection) {
			$this->languageCollection = $languageCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$languageCollection = $this->getLanguageCollection();

			foreach ($this->getLanguageExtIdList() as $extId) {
				$id = $sourceIdBinder->getNewLangIdRelation($sourceId, $extId);

				if ($id === false || $sourceIdBinder->isLangRelatedToAnotherSource($sourceId, $id)) {
					$this->pushLog(sprintf('Language "%s" was ignored', $extId));
					continue;
				}

				$languageCollection->delLang($id);
				$this->pushLog(sprintf('Language "%s" was deleted', $id));
			}
		}

		/**
		 * Возвращает список внешних идентификаторов удаляемых языков
		 * @return string[]
		 */
		private function getLanguageExtIdList() {
			return $this->getNodeValueList('/umidump/langs/lang/@id');
		}

		/**
		 * Возвращает коллекцию языков
		 * @return \iLangsCollection
		 */
		private function getLanguageCollection() {
			return $this->languageCollection;
		}
	}
