<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления шаблонов
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Part
	 */
	class Template extends Demolisher {

		/** @var \iTemplatesCollection $templateCollection коллекция шаблонов */
		private $templateCollection;

		/**
		 * Конструктор
		 * @param \iTemplatesCollection $templateCollection коллекция шаблонов
		 */
		public function __construct(\iTemplatesCollection $templateCollection) {
			$this->templateCollection = $templateCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$templateCollection = $this->getTemplateCollection();

			foreach ($this->getTemplateExtIdList() as $extId) {
				$id = $sourceIdBinder->getNewTemplateIdRelation($sourceId, $extId);

				if ($id === false || $sourceIdBinder->isTemplateRelatedToAnotherSource($sourceId, $id)) {
					$this->pushLog(sprintf('Template "%s" was ignored', $extId));
					continue;
				}

				$templateCollection->delTemplate($id);
				$this->pushLog(sprintf('Template "%s" was deleted', $id));
			}
		}

		/**
		 * Возвращает список внешних идентификаторов удаляемых шаблонов
		 * @return string[]
		 */
		private function getTemplateExtIdList() {
			return $this->getNodeValueList('/umidump/templates/template/@id');
		}

		/**
		 * Возвращает коллекцию шаблонов
		 * @return \iTemplatesCollection
		 */
		private function getTemplateCollection() {
			return $this->templateCollection;
		}
	}
