<?php

	/** Редирект */
	class umiRedirect implements iUmiCollectionItem, iUmiRedirect, iUmiDataBaseInjector, iUmiConstantMapInjector, iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var string $source адрес, откуда перенаправлять */
		private $source;

		/** @var string $target адрес, куда перенаправлять */
		private $target;

		/** @var int $status код статуса редиректа */
		private $status;

		/** @var bool $madeByUser редирект сделан пользователем (не автоматически) */
		private $madeByUser;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'constructor' => [
				'callback' => [
					'before' => '',
					'after' => ''
				]
			],
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId',
				],
				[
					'name' => 'SOURCE_FIELD_NAME',
					'setter' => 'setSource',
					'getter' => 'getSource',
				],
				[
					'name' => 'TARGET_FIELD_NAME',
					'setter' => 'setTarget',
					'getter' => 'getTarget',
				],
				[
					'name' => 'STATUS_FIELD_NAME',
					'setter' => 'setStatus',
					'getter' => 'getStatus',
				],
				[
					'name' => 'MADE_BY_USER_FIELD_NAME',
					'setter' => 'setIsMadeByUser',
					'getter' => 'isMadeByUser',
				]
			]
		];

		/** @inheritdoc */
		public function getId() {
			return $this->id;
		}

		/** @inheritdoc */
		public function getSource() {
			return $this->source;
		}

		/** @inheritdoc */
		public function getTarget() {
			return $this->target;
		}

		/** @inheritdoc */
		public function getStatus() {
			return $this->status;
		}

		/** @inheritdoc */
		public function isMadeByUser() {
			return $this->madeByUser;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$sourceField = $connection->escape($map->get('SOURCE_FIELD_NAME'));
			$targetField = $connection->escape($map->get('TARGET_FIELD_NAME'));
			$statusField = $connection->escape($map->get('STATUS_FIELD_NAME'));
			$madeByUserField = $connection->escape($map->get('MADE_BY_USER_FIELD_NAME'));

			$id = (int) $this->getId();
			$source = $connection->escape($this->getSource());
			$target = $connection->escape($this->getTarget());
			$status = (int) $this->getStatus();
			$madeByUser = (int) $this->isMadeByUser();

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$sourceField` = '$source', `$targetField` = '$target', `$statusField` = $status, `$madeByUserField` = $madeByUser
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function setSource($source) {
			if (!is_string($source)) {
				throw new Exception('Wrong value for source given');
			}

			$source = trim($source);

			if ($source === '') {
				throw new Exception('Empty value for source given');
			}

			if ($this->getSource() != $source) {
				$this->setUpdatedStatus(true);
			}

			$this->source = $source;
			return true;
		}

		/** @inheritdoc */
		public function setTarget($target) {
			if (!is_string($target)) {
				throw new Exception('Wrong value for target given');
			}

			$target = trim($target);

			if ($target === '') {
				throw new Exception('Empty value for target given');
			}

			if ($this->getTarget() != $target) {
				$this->setUpdatedStatus(true);
			}

			$this->target = $target;
			return true;
		}

		/** @inheritdoc */
		public function setStatus($status) {
			if (!is_numeric($status) || !self::getRedirectMessage($status)) {
				throw new Exception('Wrong value for status given');
			}

			if ($this->getStatus() != $status) {
				$this->setUpdatedStatus(true);
			}

			$this->status = $status;
			return true;
		}

		/** @inheritdoc */
		public function setIsMadeByUser($isMadeByUser) {
			$isMadeByUser = (bool) $isMadeByUser;

			if ($this->isMadeByUser() != $isMadeByUser) {
				$this->setUpdatedStatus(true);
			}

			$this->madeByUser = $isMadeByUser;
			return true;
		}

		/** @inheritdoc */
		public static function getRedirectMessage($status) {
			$statuses = [
				300 => 'Multiple Choices',
				'Moved Permanently',
				'Found',
				'See Other',
				'Not Modified',
				'Use Proxy',
				'Switch Proxy',
				'Temporary Redirect'
			];

			if (!isset($statuses[$status])) {
				return false;
			}

			return $statuses[$status];
		}
	}

