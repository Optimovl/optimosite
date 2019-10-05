<?php

	namespace UmiCms\System\Permissions;

	/**
	 * Класс прав системных пользователей
	 * @package UmiCms\System\Permissions
	 */
	class SystemUsersPermissions implements iSystemUsersPermissions {

		/** @var \iUmiObjectsCollection $umiObjects коллекция объектов */
		private $umiObjects;

		/** @inheritdoc */
		public function __construct(\iUmiObjectsCollection $umiObjects) {
			$this->umiObjects = $umiObjects;
		}

		/** @inheritdoc */
		public function getSvUserId() {
			return (int) $this->getUmiObjects()
				->getObjectIdByGUID(self::SV_USER_GUID);
		}

		/** @inheritdoc */
		public function getSvGroupId() {
			return (int) $this->getUmiObjects()
				->getObjectIdByGUID(self::SV_GROUP_GUID);
		}

		/** @inheritdoc */
		public function getGuestUserId() {
			return (int) $this->getUmiObjects()
				->getObjectIdByGUID(self::GUEST_USER_GUID);
		}

		/** @inheritdoc */
		public function getRegisteredGroupId() {
			return (int) $this->getUmiObjects()
				->getObjectIdByGUID(self::REGISTERED_GROUP_GUID);
		}

		/** @inheritdoc */
		public function getIdList() {
			return [
				$this->getSvUserId(),
				$this->getSvGroupId(),
				$this->getGuestUserId(),
				$this->getRegisteredGroupId(),
			];
		}

		/**
		 * Возвращает коллекцию объектов
		 * @return \iUmiObjectsCollection
		 */
		private function getUmiObjects() {
			return $this->umiObjects;
		}
	}
