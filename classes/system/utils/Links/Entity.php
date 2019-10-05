<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Class Entity класс сущности ссылки
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	class Entity implements
		\iUmiDataBaseInjector,
		iEntity,
		\iUmiConstantMapInjector,
		\iClassConfigManager {

		use \tUmiDataBaseInjector;
		use \tCommonCollectionItem;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;

		/** @var string $address адрес ссылки (ее url) */
		private $address;

		/** @var string $addressHash хеш адреса ссылки */
		private $addressHash;

		/** @var string $place место ссылки (url страницы, где она была найдена) */
		private $place;

		/** @var bool $broken статус нерабоспособности: true - не работает, false - работает */
		private $broken = false;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId',
				],
				[
					'name' => 'ADDRESS_FIELD_NAME',
					'required' => true,
					'setter' => 'setAddress',
					'getter' => 'getAddress',
				],
				[
					'name' => 'ADDRESS_HASH_FIELD_NAME',
					'required' => true,
					'setter' => 'setAddressHash',
					'getter' => 'getAddressHash',
				],
				[
					'name' => 'PLACE_FIELD_NAME',
					'required' => true,
					'setter' => 'setPlace',
					'getter' => 'getPlace',
				],
				[
					'name' => 'BROKEN_FIELD_NAME',
					'required' => false,
					'setter' => 'setBroken',
					'getter' => 'getBroken',
				]
			]
		];

		/** @inheritdoc */
		public function setAddress($address) {
			if (!is_string($address)) {
				throw new \wrongParamException('Wrong value for address given');
			}

			$trimmedAddress = trim($address);

			if ($trimmedAddress === '') {
				throw new \wrongParamException('Empty value for address given');
			}

			if ($this->getAddress() != $trimmedAddress) {
				$this->address = $trimmedAddress;
				$this->setUpdatedStatus(true);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getAddress() {
			return $this->address;
		}

		/** @inheritdoc */
		public function setAddressHash($addressHash) {
			if (!is_string($addressHash)) {
				throw new \wrongParamException('Wrong value for address hash given');
			}

			$trimmedAddressHash = trim($addressHash);

			if ($trimmedAddressHash === '') {
				throw new \wrongParamException('Empty value for address hash given');
			}

			if ($this->getAddressHash() != $trimmedAddressHash) {
				$this->addressHash = $trimmedAddressHash;
				$this->setUpdatedStatus(true);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getAddressHash() {
			return $this->addressHash;
		}

		/** @inheritdoc */
		public function setPlace($place) {
			if (!is_string($place)) {
				throw new \wrongParamException('Wrong value for place given');
			}

			$trimmedPlace = trim($place);

			if ($trimmedPlace === '') {
				throw new \wrongParamException('Empty value for place given');
			}

			if ($this->getPlace() != $trimmedPlace) {
				$this->place = $trimmedPlace;
				$this->setUpdatedStatus(true);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getPlace() {
			return $this->place;
		}

		/** @inheritdoc */
		public function setBroken($status) {
			$status = (bool) $status;

			if ($this->getBroken() != $status) {
				$this->broken = $status;
				$this->setUpdatedStatus(true);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getBroken() {
			return $this->broken;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return $this;
			}

			$tableName = $this->getColumnName('TABLE_NAME');
			$idField = $this->getColumnName('ID_FIELD_NAME');
			$addressField = $this->getColumnName('ADDRESS_FIELD_NAME');
			$addressHashField = $this->getColumnName('ADDRESS_HASH_FIELD_NAME');
			$placeField = $this->getColumnName('PLACE_FIELD_NAME');
			$brokenField = $this->getColumnName('BROKEN_FIELD_NAME');

			$connection = $this->getConnection();

			$id = (int) $this->getId();
			$address = $connection->escape($this->getAddress());
			$addressHash = $connection->escape($this->getAddressHash());
			$place = $connection->escape($this->getPlace());
			$broken = (int) $this->getBroken();

			$sql = <<<SQL
UPDATE
	`$tableName`
SET
	`$addressField` = '$address', `$addressHashField` = '$addressHash', `$placeField` = '$place',
	`$brokenField` = '$broken'
WHERE
	`$idField` = $id;
SQL;
			$connection->query($sql);
			return $this;
		}
	}
