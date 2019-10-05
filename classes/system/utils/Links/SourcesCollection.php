<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Коллекция источников ссылок
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	class SourcesCollection implements
		iSourcesCollection,
		\iUmiDataBaseInjector,
		\iUmiService,
		\iUmiConstantMapInjector,
		\iClassConfigManager {

		use \tUmiDataBaseInjector;
		use \tUmiService;
		use \tCommonCollection;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;

		/** @var string $collectionItemClass класс элемента коллекции, с которым она работает */
		private $collectionItemClass = 'UmiCms\Classes\System\Utils\Links\Source';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'LinksSources',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false,
				],
				[
					'name' => 'LINK_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'PLACE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TYPE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				]
			]
		];

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/** @inheritdoc */
		public function exportByLinkId($linkId) {
			$params = [
				$this->getMap()->get('LINK_ID_FIELD_NAME') => (int) $linkId
			];
			return $this->export($params);
		}

		/** @inheritdoc */
		public function createByLinkIdAndPlace($linkId, $place) {
			$constantsMap = $this->getMap();

			$createData = [
				$constantsMap->get('LINK_ID_FIELD_NAME') => $linkId,
				$constantsMap->get('PLACE_FIELD_NAME') => $place,
				$constantsMap->get('TYPE_FIELD_NAME') => $this->getTypeByPlace($place)
			];

			return $this->create($createData);
		}

		/**
		 * Определяет тип источника по его месту
		 * @param string $place место источника (адрес шаблона или объекта)
		 * @return string тип источника
		 */
		private function getTypeByPlace($place) {
			$type = new SourceTypes(SourceTypes::OBJECT_KEY);

			if (is_file($place)) {
				$type = new SourceTypes(SourceTypes::TEMPLATE_KEY);
			}

			return (string) $type;
		}
	}
