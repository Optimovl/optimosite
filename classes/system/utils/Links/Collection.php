<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Класс коллекции сущностей ссылок
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	class Collection implements
		iCollection,
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
		private $collectionItemClass = 'UmiCms\Classes\System\Utils\Links\Entity';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'Links',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false,
				],
				[
					'name' => 'ADDRESS_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'ADDRESS_HASH_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'PLACE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'BROKEN_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
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
		public function createByAddressAndPlace($address, $place) {
			if (!is_string($address)) {
				throw new \wrongParamException('Wrong value for address given');
			}

			if (!is_string($place)) {
				throw new \wrongParamException('Wrong value for place given');
			}

			$constantsMap = $this->getMap();

			$createData = [
				$constantsMap->get('ADDRESS_FIELD_NAME') => $address,
				$constantsMap->get('ADDRESS_HASH_FIELD_NAME') => $this->hashAddress($address),
				$constantsMap->get('PLACE_FIELD_NAME') => $place,
				$constantsMap->get('BROKEN_FIELD_NAME') => false,
			];

			return $this->create($createData);
		}

		/** @inheritdoc */
		public function getByAddress($address) {
			if (!is_string($address)) {
				throw new \wrongParamException('Wrong value for address given');
			}

			return $this->getBy(
				$this->getMap()->get('ADDRESS_HASH_FIELD_NAME'),
				$this->hashAddress($address)
			);
		}

		/** @inheritdoc */
		public function exportBrokenLinks($offset = 0, $limit = self::DEFAULT_RESULT_ITEMS_LIMIT) {
			$constantsMap = $this->getMap();

			$queryParams = [
				$constantsMap->get('BROKEN_FIELD_NAME') => true,
				$constantsMap->get('OFFSET_KEY') => (int) $offset,
				$constantsMap->get('LIMIT_KEY') => (int) $limit
			];

			return $this->export($queryParams);
		}

		/** @inheritdoc */
		public function countBrokenLinks() {
			$constantsMap = $this->getMap();

			$queryParams = [
				$constantsMap->get('BROKEN_FIELD_NAME') => true,
				$constantsMap->get('CALCULATE_ONLY_KEY') => true,
			];

			return $this->count($queryParams);
		}

		/** @inheritdoc */
		public function getCorrectLinks($offset = 0, $limit = self::DEFAULT_RESULT_ITEMS_LIMIT) {
			$constantsMap = $this->getMap();

			$params = [
				$constantsMap->get('BROKEN_FIELD_NAME') => false,
				$constantsMap->get('OFFSET_KEY') => (int) $offset,
				$constantsMap->get('LIMIT_KEY') => (int) $limit
			];
			return $this->get($params);
		}

		/**
		 * Хеширует адрес ссылки
		 * @param string $address адрес ссылки
		 * @return string
		 */
		private function hashAddress($address) {
			return md5($address);
		}
	}
