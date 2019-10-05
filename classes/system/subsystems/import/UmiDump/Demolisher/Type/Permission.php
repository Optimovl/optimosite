<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;
	use UmiCms\System\Permissions\iSystemUsersPermissions;

	/**
	 * Класс удаления прав:
	 *
	 * 1) Прав на использование администативной панели модулей;
	 * 2) Прав на использование методов и их групп;
	 * 3) Прав на специфические операции над страницами;
	 * 4) Прав на любые операции с объектов;
	 *
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Permission extends Demolisher {

		/** @var \iPermissionsCollection $permissionCollection коллекция прав */
		private $permissionCollection;

		/** @var iSystemUsersPermissions $systemUsersPermissions класс прав системных пользователей */
		private $systemUsersPermissions;

		/** @var \iUmiObjectsCollection $objectsCollection коллекция объектов */
		private $objectsCollection;

		/**
		 * Конструктор
		 * @param \iPermissionsCollection $permissionCollection коллекция прав
		 * @param iSystemUsersPermissions $systemUsersPermissions класс прав системных пользователей
		 * @param \iUmiObjectsCollection $objectsCollection коллекция объектов
		 */
		public function __construct(
			\iPermissionsCollection $permissionCollection,
			iSystemUsersPermissions $systemUsersPermissions,
			\iUmiObjectsCollection $objectsCollection
		) {
			$this->permissionCollection = $permissionCollection;
			$this->systemUsersPermissions = $systemUsersPermissions;
			$this->objectsCollection = $objectsCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$this->deleteModulePermissions();
			$this->deletePagePermissions();
			$this->resetObjectOwners();
		}

		/** Удаляет права на модули, методы и их группы */
		private function deleteModulePermissions() {
			$importSourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$permissionCollection = $this->getPermissionCollection();

			foreach ($this->getModulePermissionList() as $ownerExtId => $permissionList) {
				$ownerId = $importSourceIdBinder->getNewObjectIdRelation($sourceId, $ownerExtId);

				if (!$ownerId) {
					$this->pushLog(sprintf('Module permission with owner "%s" was ignored', $ownerId));
					continue;
				}

				foreach ($permissionList as $permission) {
					$module = $permission['module'];
					$method = $permission['method'];

					if ($method) {
						$permissionCollection->deleteMethodPermission($ownerId, $module, $method);
						$message = sprintf(
							'Method permission "%s:%s" with owner "%s" was deleted',
							$module,
							$method,
							$ownerId
						);
					} else {
						$permissionCollection->deleteModulePermission($ownerId, $module);
						$message = sprintf(
							'Module permission "%s" with owner "%s" was deleted',
							$module,
							$ownerId
						);
					}

					$this->pushLog($message);
				}
			}
		}

		/** Удаляет права на специфические операции над страницей */
		private function deletePagePermissions() {
			$importSourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$permissionCollection = $this->getPermissionCollection();

			foreach ($this->getPagePermission() as $extPageId => $ownerExtIdList) {
				$pageId = $importSourceIdBinder->getNewIdRelation($sourceId, $extPageId);

				if (!$pageId) {
					$this->pushLog(sprintf('Page permission for page "%s" was ignored', $extPageId));
					continue;
				}

				foreach ($ownerExtIdList as $ownerExtId) {
					$ownerId = $importSourceIdBinder->getNewObjectIdRelation($sourceId, $ownerExtId);

					if (!$ownerId) {
						$this->pushLog(sprintf(
							'Page permission for page "%s" with owner "%s" was ignored', $pageId, $ownerExtId
						));
						continue;
					}

					$permissionCollection->resetElementPermissions($pageId, $ownerId);
					$this->pushLog(sprintf(
						'Page permission for page "%s" with owner "%s" was deleted', $pageId, $ownerId
					));
				}
			}
		}

		/** Заменяет владельцей объектов */
		private function resetObjectOwners() {
			$importSourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$objectCollection = $this->getObjectCollection();
			$svId = $this->getSystemUsersPermissions()
				->getSvUserId();

			foreach ($this->getObjectPermission() as $objectExtId) {
				$objectId = $importSourceIdBinder->getNewObjectIdRelation($sourceId, $objectExtId);
				$object = $objectCollection->getObject($objectId);

				if (!$object instanceof \iUmiObject) {
					$this->pushLog(sprintf('Owner of object with id "%s" reset was ignored', $objectExtId));
					continue;
				}

				$object->setOwnerId($svId);
				$object->commit();
				$this->pushLog(sprintf('Owner of object with id "%s" was reset', $objectId));

				$objectCollection->unloadObject($object->getId());
			}
		}

		/**
		 * Возвращает список прав на модули, методы и их группы, сгруппирование по внешним идентификаторам пользователей
		 * @return array
		 *
		 * [
		 *      'ownerExtId' => [
		 *          [
		 *              'module', // права на группу методов модуля
		 *              'method'
		 *          ],
		 *          [
		 *              'module' // права на модуль
		 *          ]
		 *      ]
		 * ]
		 */
		private function getModulePermissionList() {
			$result = [];

			/** @var \DOMElement $permission */
			foreach ($this->parse('/umidump/permissions/permission') as $permission) {
				$extOwnerId = $permission->getAttribute('object-id');

				if (!$extOwnerId) {
					continue;
				}

				/** @var \DOMElement $module */
				foreach ($this->parse('module', $permission) as $module) {
					$name = $module->getAttribute('name');

					if (!$name) {
						continue;
					}

					$result[$extOwnerId][] = [
						'module' => $name,
						'method' => $module->getAttribute('method')
					];
				}
			}

			return $result;
		}

		/**
		 * Возвращает список внешних идентификаторов пользователей с правами на операции над станицей,
		 * сгуппированые по идентификаторам страниц
		 * @return array
		 *
		 * [
		 *      'pageId' => [
		 *          'ownerExtId'
		 *      ]
		 * ]
		 */
		private function getPagePermission() {
			return $this->getNodeValueTree(
				[],
				'/umidump/permissions/permission',
				'page-id',
				'owner/@id'
			);
		}

		/**
		 * Возвращает список внешних идентификаторов объектов, чьих владельцев нужно заменить
		 * @return \string[]
		 */
		private function getObjectPermission() {
			return $this->getNodeValueList('/umidump/permissions/permission/@object-id');
		}

		/**
		 * Возвращает коллекцию прав
		 * @return \iPermissionsCollection
		 */
		private function getPermissionCollection() {
			return $this->permissionCollection;
		}

		/**
		 * Возвращает класс прав системных пользователей
		 * @return iSystemUsersPermissions
		 */
		private function getSystemUsersPermissions() {
			return $this->systemUsersPermissions;
		}

		/**
		 * Возвращает коллекцию объектов
		 * @return \iUmiObjectsCollection
		 */
		private function getObjectCollection() {
			return $this->objectsCollection;
		}
	}
