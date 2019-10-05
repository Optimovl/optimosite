<?php

	use UmiCms\Service;

	/** Класс для управления резервными копиями страниц */
	class backupModel extends singleton implements iBackupModel {

		/** @inheritdoc */
		protected function __construct() {}

		/** @inheritdoc */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function getChanges($pageId = false) {
			$registry = Service::Registry();

			if (!$registry->get('modules/backup/enabled')) {
				return false;
			}

			$limit = (int) $registry->get('//modules/backup/max_save_actions');
			$timeLimit = (int) $registry->get('//modules/backup/max_timelimit');
			$endTime = $timeLimit * 3600 * 24;
			$connection = ConnectionPool::getInstance()->getConnection();

			$pageId = (int) $pageId;
			$limit = ($limit > 2) ? $limit : 2;

			$sql =
				"SELECT id, ctime, changed_module, user_id, is_active FROM cms_backup WHERE param='" . $pageId . "' AND (" .
				time() . '-ctime)<' . $endTime . " ORDER BY ctime DESC LIMIT {$limit}";
			$result = $connection->queryResult($sql);

			if ($result->length() < 2) {
				$sql = "SELECT id, ctime, changed_module, user_id, is_active FROM cms_backup WHERE param='" . $pageId .
					"' ORDER BY ctime DESC LIMIT 2";
				$result = $connection->queryResult($sql);
			}

			$params = [];
			$rows = [];

			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			foreach ($result as $row) {
				$revisionInfo = $this->getChangeInfo(
					$row['id'],
					$row['ctime'],
					$row['changed_module'],
					$pageId,
					$row['user_id'],
					$row['is_active']
				);

				if (count($revisionInfo)) {
					$rows[] = $revisionInfo;
				}
			}

			$params['nodes:revision'] = $rows;
			return $params;
		}

		/** @inheritdoc */
		public function getOverdueChanges($daysToExpire = 30) {
			if ($daysToExpire === 0) {
				return [];
			}

			$secondsInDay = 24 * 3600;
			$maxSecondsLimit = $daysToExpire * $secondsInDay;
			$connection = ConnectionPool::getInstance()->getConnection();

			$overdueChangesQuery = <<<QUERY
				SELECT *
				FROM   `cms_backup` 
				WHERE  `ctime` < unix_timestamp() - ${maxSecondsLimit};
QUERY;
			$result = $connection->queryResult($overdueChangesQuery);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$changes = [];

			foreach ($result as $row) {
				$changes[] = new backupChange(
					$row['id'],
					$row['ctime'],
					$row['changed_module'],
					$row['changed_method'],
					$row['param'],
					$row['param0'],
					$row['user_id'],
					$row['is_active']
				);
			}

			return $changes;
		}

		/** @inheritdoc */
		public function deleteChanges($changes = []) {
			if (!is_array($changes)) {
				return false;
			}

			$changesID = [];

			/** var backupChange $backupChange */
			foreach ($changes as $backupChange) {
				if ($backupChange instanceof backupChange) {
					$changesID[] = $backupChange->__get('id');
				}
			}

			if (empty($changesID)) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$changesIDForDeleting = implode($changesID, ', ');
			$deletingChangesQuery = <<<QUERY
				DELETE 
				FROM   `cms_backup` 
				WHERE  `id` IN (${changesIDForDeleting})
QUERY;
			$connection->query($deletingChangesQuery);

			return true;
		}

		/** @inheritdoc */
		public function deletePageChanges($pageId) {
			$pageId = (int) $pageId;
			$sql = <<<SQL
DELETE FROM `cms_backup` WHERE `param` = $pageId
SQL;
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$connection->query($sql);
			return $connection->affectedRows();
		}

		/**
		 * Возвращает данные об изменениях в точке восстановления $revision_id
		 * @param int $revisionId - id Точки восстановления
		 * @param int $createTime - Timestamp создания точки восстановления
		 * @param string $changedModule - Название модуля к которому относится точка восстановления
		 * @param int $pageId - Id страницы, к которой относится точка восстановления
		 * @param int $userId - Id пользователя, создавшего точку восстановления
		 * @param int $isActive - активность точки восстановления
		 * @return array - данные о точке восстановления, подготовленные для шаблонизатора
		 */
		protected function getChangeInfo($revisionId, $createTime, $changedModule, $pageId, $userId, $isActive) {

			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			$revisionInfo = [];
			$element = $hierarchy->getElement($pageId);

			if ($element instanceof iUmiHierarchyElement) {

				$revisionInfo['attribute:changetime'] = $createTime;
				$revisionInfo['attribute:user-id'] = $userId;
				$changedModule = (string) $changedModule;

				if ($changedModule === '') {
					$revisionInfo['attribute:is-void'] = true;
				}

				if ($isActive) {
					$revisionInfo['attribute:active'] = 'active';
				}

				$revisionInfo['date'] = new umiDate($createTime);
				$revisionInfo['author'] = selector::get('object')->id($userId);
				$revisionInfo['link'] = "/admin/backup/rollback/{$revisionId}/";

				$module_name = $element->getModule();
				$method_name = $element->getMethod();

				$module = $cmsController->getModule($module_name);
				if ($module instanceof def_module) {
					$links = $module->getEditLink($pageId, $method_name);

					if (isset($links[1])) {
						$revisionInfo['page'] = [];
						$revisionInfo['page']['attribute:name'] = $element->getName();
						$revisionInfo['page']['attribute:edit-link'] = $links[1];
						$revisionInfo['page']['attribute:link'] = $hierarchy->getPathById($element->getId());
					}
				}
			}

			return $revisionInfo;
		}

		/** @inheritdoc */
		public function getAllChanges() {
			if (!Service::Registry()->get('modules/backup/enabled')) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<'SQL'
SELECT id, ctime, changed_module, param, user_id, is_active
FROM cms_backup ORDER BY ctime DESC LIMIT 100
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$params = [];
			$rows = [];

			foreach ($result as $row) {
				$revision_info = $this->getChangeInfo(
					$row['id'],
					$row['ctime'],
					$row['changed_module'],
					$row['param'],
					$row['user_id'],
					$row['is_active']
				);

				if (count($revision_info)) {
					$rows[] = $revision_info;
				}
			}

			$params['nodes:revision'] = $rows;
			return $params;
		}

		/** @inheritdoc */
		public function save($pageId = '', $currentModule = '', $currentMethod = '') {
			if (!Service::Registry()->get('//modules/backup/enabled')) {
				return false;
			}

			if (getRequest('rollbacked')) {
				return false;
			}

			$this->restoreIncrement();

			$cmsController = cmsController::getInstance();
			$currentModule = $currentModule ?: $cmsController->getCurrentModule();
			$currentModule = $currentModule ?: getRequest('module');
			$currentMethod = $cmsController->getCurrentMethod();
			$currentMethod = $currentMethod ?: getRequest('method');

			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$createTime = time();
			$entityData = [];

			foreach ($_REQUEST as $index => $value) {
				if ($index == 'save-mode') {
					continue;
				}

				$entityData[$index] = (!is_array($value)) ? base64_encode($value) : $value;
			}

			if (isset($entityData['data']['new'])) {
				$element = umiHierarchy::getInstance()->getElement($pageId);
				if ($element instanceof iUmiHierarchyElement) {
					$entityData['data'][$element->getObjectId()] = $entityData['data']['new'];
					unset($entityData['data']['new']);
				}
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$entityData = serialize($entityData);
			$entityData = $connection->escape($entityData);

			$pageId = $connection->escape($pageId);
			$currentModule = $connection->escape($currentModule);
			$currentMethod = $connection->escape($currentMethod);

			$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $pageId . "'";
			$connection->query($sql);

			$sql = <<<SQL
INSERT INTO cms_backup (ctime, changed_module, changed_method, param, param0, user_id, is_active)
				VALUES('{$createTime}', '{$currentModule}', '{$currentMethod}', '{$pageId}', '{$entityData}', '{$userId}', '1')
SQL;
			$connection->query($sql);

			$limit = Service::Registry()->get('//modules/backup/max_save_actions');
			$sql = "SELECT COUNT(`id`) FROM cms_backup WHERE param='" . $pageId . "' ORDER BY ctime DESC";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$recordsCount = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$recordsCount = array_shift($fetchResult);
			}

			$recordsForDeletingCount = $recordsCount - $limit;

			if ($recordsForDeletingCount < 0) {
				$recordsForDeletingCount = 0;
			}

			$sql = "SELECT id FROM cms_backup WHERE param='" . $pageId . "' ORDER BY ctime DESC LIMIT 2";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$backupIds = [];

			foreach ($result as $row) {
				$backupIds[] = array_shift($row);
			}

			$notId = '';

			if (count($backupIds)) {
				$notId = 'AND id NOT IN (' . implode(', ', $backupIds) . ')';
			}

			$sql = "DELETE FROM cms_backup WHERE param='$pageId' {$notId} ORDER BY ctime ASC LIMIT $recordsForDeletingCount";
			$connection->query($sql);

			$timeLimit = Service::Registry()->get('//modules/backup/max_timelimit');
			$endTime = $timeLimit * 3600 * 24;
			$sql = "DELETE FROM cms_backup WHERE param='" . $pageId . "' AND (" . time() . '-ctime)>' . $endTime .
				" {$notId} ORDER BY ctime ASC";
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function rollback($revisionId) {
			if (!Service::Registry()->get('//modules/backup/enabled')) {
				return false;
			}

			$revisionId = (int) $revisionId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT param, param0, changed_module, changed_method FROM cms_backup WHERE id='$revisionId' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			foreach ($result as $row) {
				$elementId = $row['param'];
				$data = $row['param0'];
				$changedModule = $row['changed_module'];
				$changedMethod = $row['changed_method'];
				$changedParam = $elementId;

				$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $changedParam . "'";
				$connection->query($sql);

				$sql = "UPDATE cms_backup SET is_active='1' WHERE id='" . $revisionId . "'";
				$connection->query($sql);

				$entityData = unserialize($data);
				$_REQUEST = [];

				foreach ($entityData as $index => $value) {
					if (is_array($value)) {
						foreach ($value as $i => $v) {
							$value[$i] = $v;
						}
					} else {
						$value = base64_decode($value);
					}

					$_REQUEST[$index] = $value;
					$_POST[$index] = $value;
				}

				$_REQUEST['rollbacked'] = true;
				$_REQUEST['save-mode'] = getLabel('label-save');

				if (!$changedModuleInst = cmsController::getInstance()->getModule($changedModule)) {
					throw new requreMoreAdminPermissionsException("You can't rollback this action. No permission to this module.");
				}

				$element = umiHierarchy::getInstance()->getElement($elementId);

				if ($element instanceof iUmiHierarchyElement) {
					$links = $changedModuleInst->getEditLink($elementId, $element->getMethod());
					if (umiCount($links) >= 2) {
						$editLink = $links[1];
						$_REQUEST['referer'] = $editLink;

						$editLink = trim($editLink, '/') . '/do';

						if (preg_match("/admin\/[A-z]+\/([^\/]+)\//", $editLink, $out)) {
							if (isset($out[1])) {
								$changedMethod = $out[1];
							}
						}

						$_REQUEST['path'] = $editLink;
						$_REQUEST['param0'] = $elementId;
						$_REQUEST['param1'] = 'do';
					}
				}

				return $changedModuleInst->cms_callMethod($changedMethod, []);
			}
		}

		/** @inheritdoc */
		public function addLogMessage($elementId) {
			if (!Service::Registry()->get('//modules/backup/enabled')) {
				return false;
			}

			$this->restoreIncrement();
			$auth = Service::Auth();
			$userId = $auth->getUserId();

			$time = time();
			$param = (int) $elementId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
INSERT INTO cms_backup (ctime, param, user_id, param0)
VALUES('{$time}', '{$param}', '{$userId}', '{$time}')
SQL;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function fakeBackup($elementId) {
			$element = selector::get('page')->id($elementId);

			if (!($element instanceof iUmiHierarchyElement)) {
				return false;
			}

			$originalRequest = $_REQUEST;
			$object = $element->getObject();
			/** @var iUmiObjectType $type */
			$type = selector::get('object-type')->id($object->getTypeId());

			$_REQUEST['name'] = $element->getName();
			$_REQUEST['alt-name'] = $element->getAltName();
			$_REQUEST['active'] = $element->getIsActive();
			foreach ($type->getAllFields() as $field) {
				$fieldName = $field->getName();
				$value = $this->fakeBackupValue($object, $field);
				if ($value === null) {
					continue;
				}
				$_REQUEST['data'][$object->getId()][$fieldName] = $value;
			}

			$this->save($elementId, $element->getModule());
			$_REQUEST = $originalRequest;
			return true;
		}

		/**
		 * Возвращает значение свойства объекта в том виде, в котором значения
		 * данного типа поля изначально передается в формах редактирования
		 * @param iUmiObject $object - Объект, свойство которого мы получаем
		 * @param iUmiField $field - Поле, значение которого мы хотим получить
		 * @return string Значение поля.
		 */
		protected function fakeBackupValue(iUmiObject $object, iUmiField $field) {
			$value = $object->getValue($field->getName());

			switch ($field->getDataType()) {
				case 'file':
				case 'img_file':
				case 'swf_file':
					return ($value instanceof iUmiFile) ? $value->getFilePath() : '';

				case 'boolean':
					return $value ? '1' : '0';

				case 'date':
					return ($value instanceof iUmiDate) ? $value->getFormattedDate('U') : null;

				case 'tags':
					return is_array($value) ? implode(', ', $value) : null;

				default:
					return is_array($value) ? $value : (string) $value;
			}
		}

		/** Проверяет и при необходимости меняет значение автоинкремента в таблице cms_backup */
		protected function restoreIncrement() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$result1 = $connection->queryResult('SELECT max( id ) FROM `cms_backup`');
			$result1->setFetchType(IQueryResult::FETCH_ROW);
			$row1 = $result1->fetch();
			$incrementToBe = $row1[0] + 1;

			$result = $connection->queryResult("SHOW TABLE STATUS LIKE 'cms_backup'");
			$result->setFetchType(IQueryResult::FETCH_ARRAY);
			$row = $result->fetch();
			$increment = isset($row['Auto_increment']) ? (int) $row['Auto_increment'] : false;

			if ($increment !== false && $increment != $incrementToBe) {
				$connection->query("ALTER TABLE `cms_backup` AUTO_INCREMENT={$incrementToBe}");
			}
		}
	}
