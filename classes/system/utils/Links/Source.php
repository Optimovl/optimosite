<?php

	namespace UmiCms\Classes\System\Utils\Links;

	use UmiCms\Classes\System\Enums\EnumElementNotExistsException;

	/**
	 * Источник ссылки
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	class Source implements
		iSource,
		\iUmiDataBaseInjector,
		\iUmiConstantMapInjector,
		\iClassConfigManager {

		use \tUmiDataBaseInjector;
		use \tCommonCollectionItem;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;

		/** @var int $linkId идентификатор ссылки, @see UmiCms\Classes\System\Utils\Links\Entity */
		private $linkId;

		/** @var string $place место источника (адрес шаблона или объекта) */
		private $place;

		/** @var string $type тип источника, @see UmiCms\Classes\System\Utils\Links\SourceTypes */
		private $type;

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
					'name' => 'LINK_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setLinkId',
					'getter' => 'getLinkId',
				],
				[
					'name' => 'PLACE_FIELD_NAME',
					'required' => true,
					'setter' => 'setPlace',
					'getter' => 'getPlace',
				],
				[
					'name' => 'TYPE_FIELD_NAME',
					'required' => true,
					'setter' => 'setType',
					'getter' => 'getType'
				]
			]
		];

		/** @inheritdoc */
		public function setLinkId($linkId) {
			if (!is_numeric($linkId)) {
				throw new \wrongParamException('Wrong value for link id given');
			}

			if ($this->getLinkId() != $linkId) {
				$this->linkId = (int) $linkId;
				$this->setUpdatedStatus(true);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getLinkId() {
			return $this->linkId;
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

			$normalisedPlace = is_file($trimmedPlace) ? $this->getLocalFilePath($trimmedPlace) : $trimmedPlace;

			if ($this->getPlace() != $normalisedPlace) {
				$this->place = $normalisedPlace;
				$this->setUpdatedStatus(true);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getPlace() {
			return $this->place;
		}

		/** @inheritdoc */
		public function setType($type) {
			try {
				$sourceType = new SourceTypes($type);
			} catch (EnumElementNotExistsException $e) {
				throw new \wrongParamException('Wrong value for type given');
			}

			if ($this->getType() != $type) {
				$this->type = (string) $sourceType;
				$this->setUpdatedStatus(true);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getType() {
			return $this->type;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return $this;
			}

			$tableName = $this->getColumnName('TABLE_NAME');
			$idField = $this->getColumnName('ID_FIELD_NAME');
			$linkIdField = $this->getColumnName('LINK_ID_FIELD_NAME');
			$placeField = $this->getColumnName('PLACE_FIELD_NAME');
			$typeField = $this->getColumnName('TYPE_FIELD_NAME');

			$connection = $this->getConnection();

			$id = (int) $this->getId();
			$linkId = (int) $this->getLinkId();
			$place = $connection->escape($this->getPlace());
			$type = $connection->escape($this->getType());

			$sql = <<<SQL
UPDATE
	`$tableName`
SET
	`$linkIdField` = $linkId, `$placeField` = '$place', `$typeField` = '$type'
WHERE
	`$idField` = $id;
SQL;
			$connection->query($sql);
			return $this;
		}

		/**
		 * Возвращает относительный адрес файла (относительно UMI.CMS)
		 * @param string $absoluteFilePath абсолютный адрес файла
		 * @return string
		 */
		private function getLocalFilePath($absoluteFilePath) {
			$cwdLength = mb_strlen(CURRENT_WORKING_DIR);
			return mb_substr($absoluteFilePath, $cwdLength);
		}
	}
