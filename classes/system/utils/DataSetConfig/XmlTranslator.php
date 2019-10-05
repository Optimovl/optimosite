<?php
	namespace UmiCms\Classes\System\Utils\DataSetConfig;

	/**
	 * Класс транслятора конфигурации данных контрола в xml формат
	 * @package UmiCms\Classes\System\Utils\DataSetConfig
	 */
	class XmlTranslator implements iXmlTranslator {

		/** @var \iUmiObjectTypesCollection $objectTypeCollection коллекция объектных типов */
		private $objectTypeCollection;

		/** @var \iCmsController $cmsController cms контроллер */
		private $cmsController;

		/** @var \DOMDocument $document результирующий документ */
		private $document;

		/** @inheritdoc */
		public function __construct(\iUmiObjectTypesCollection $objectTypeCollection, \iCmsController $cmsController) {
			$this->objectTypeCollection = $objectTypeCollection;
			$this->cmsController = $cmsController;
		}

		/** @inheritdoc */
		public function translate($config) {
			$document = $this->createDataSetConfigDocument();
			$this->setDocument($document);

			if (is_array($config)) {
				$this->translateConfigToXml($config);
			}

			return $this->getDocument();
		}

		/**
		 * Создает xml документ для представления конфигурации данных контрола
		 * @return \DOMDocument
		 * @example:
		 *
		 *	<dataset>
		 * 		...
		 * 	</dataset>
		 */
		private function createDataSetConfigDocument() {
			$document = new \DOMDocument();
			$document->encoding = 'utf-8';
			$root = $document->createElement('dataset');
			$document->appendChild($root);
			return $document;
		}

		/**
		 * Переводит конфигурацию данных контрола в xml документ
		 * @param array $datasetConfig конфигурация данных контрола
		 * @example:
		 *
		 *	<dataset>
		 *		<methods>...</methods>
		 *		<types>...</types>
		 *		<stoplist>...</stoplist>
		 *		<default>...</default>
		 * 	</dataset>
		 */
		private function translateConfigToXml(array $datasetConfig) {
			foreach ($datasetConfig as $sectionName => $propertyList) {
				$this->translateSectionList($sectionName, $propertyList);
			}
		}

		/**
		 * Переводит секцию параметров конфигурации данных контрола в xml документ
		 * @param string $sectionName имя секции
		 * @param array|string $propertyList список параметров секции или параметр
		 * @example:
		 *
		 *  <dataset>
		 * 		<methods>...</methods>
		 * 		<types>...</types>
		 *		<stoplist>...</stoplist>
		 *		<default>foo[50px]|bar[50px]</default>
		 *  </dataset>
		 */
		private function translateSectionList($sectionName, $propertyList) {
			$document = $this->getDocument();
			$section = $document->createElement($sectionName);
			$root = $document->getElementsByTagName('dataset')
				->item(0);
			$root->appendChild($section);

			if (!is_array($propertyList)) {
				$section->appendChild($document->createTextNode($propertyList));
				return;
			}

			$this->translatePropertyList($propertyList, $section);
		}

		/**
		 * Переводит список параметров секции конфигурации данных контрола в xml документ
		 * @param array $propertyList список параметров секции конфигурации данных
		 * @param \DOMElement $section элемент секции
		 * @example:
		 *
		 *  <dataset>
		 * 		<methods>
		 * 			<method>foo</method>
		 * 		</methods>
		 * 		<types>
		 * 			<type>foo</type>
		 * 		</types>
		 *		<stoplist>
		 * 			<exclude>foo</exclude>
		 * 		</stoplist>
		 *		<default>foo[50px]|bar[50px]</default>
		 *  </dataset>
		 */
		private function translatePropertyList(array $propertyList, \DOMElement $section) {
			$document = $this->getDocument();
			$childMap = $this->getSectionTreeMap();
			$sectionName = $section->tagName;

			foreach ($propertyList as $propertyAttributeList) {
				$property = $document->createElement($childMap[$sectionName]);

				if (!is_array($propertyAttributeList)) {
					$property->appendChild($document->createTextNode($propertyAttributeList));
					$section->appendChild($property);
					continue;
				}

				$this->translatePropertyAttributeList($propertyAttributeList, $property);
				$section->appendChild($property);
			}
		}

		/**
		 * Возвращает карту имен секции и их параметров
		 * @return array
		 * @example:
		 *
		 *	[
		 * 		'<имя секциии>' => '<имя параметра>'
		 *	]
		 */
		private function getSectionTreeMap() {
			return [
				'methods' => 'method',
				'types' => 'type',
				'stoplist' => 'exclude',
				'default' => 'column',
				'fields' => 'field'
			];
		}

		/**
		 * Переводит атрибуты параметра секции конфигурации данных контрола в xml документ
		 * @param array $propertyAttributeList список атрибутов секции конфигурации данных
		 * @param \DOMElement $property элемент параметра
		 * @example:
		 *
		 *  <dataset>
		 * 		<methods>
		 * 			<method title="baz">foo</method>
		 * 		</methods>
		 * 		<types>
		 * 			<type id="baz">foo</type>
		 * 		</types>
		 *		<stoplist>
		 * 			<exclude>foo</exclude>
		 * 		</stoplist>
		 *		<default>foo[50px]|bar[50px]</default>
		 *  </dataset>
		 */
		private function translatePropertyAttributeList(array $propertyAttributeList, \DOMElement $property) {
			foreach ($propertyAttributeList as $name => $value) {
				$this->translatePropertyAttribute($name, $value, $property);
			}
		}

		/**
		 * Переводит атрибут параметра секции конфигурации данных контрола в xml документ
		 * @param string $name имя атрибута
		 * @param mixed $value значение атрибута
		 * @param \DOMElement $property элемент параметра
		 */
		private function translatePropertyAttribute($name, $value, \DOMElement $property) {
			$document = $this->getDocument();

			if ($name === '#__name') {
				$property->appendChild($document->createTextNode($value));
				return;
			}

			if ($name == 'id' && !is_numeric($value)) {
				$objectTypeCollection = $this->getObjectTypeCollection();
				$moduleName = $this->getCmsController()
					->getCurrentModule();
				$value = $objectTypeCollection->getTypeIdByHierarchyTypeName($moduleName, $value);
			}

			$value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
			$property->setAttribute($name, $value);
		}

		/**
		 * Устанавливает результирующий документ
		 * @param \DOMDocument $document
		 * @return $this
		 */
		private function setDocument(\DOMDocument $document) {
			$this->document = $document;
			return $this;
		}

		/**
		 * Возвращает результирующий документ
		 * @return \DOMDocument
		 */
		private function getDocument() {
			return $this->document;
		}

		/**
		 * Возвращает cms контроллер
		 * @return \iCmsController
		 */
		private function getCmsController() {
			return $this->cmsController;
		}

		/**
		 * Возвращает коллекцию объектных типов
		 * @return \iUmiObjectTypesCollection
		 */
		private function getObjectTypeCollection() {
			return $this->objectTypeCollection;
		}
	}