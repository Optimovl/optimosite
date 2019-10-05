<?php

	use UmiCms\Service;

	/** Класс xml транслятора (сериализатора) страниц */
	class umiHierarchyElementWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iUmiHierarchyElement $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует страницу в массив с разметкой для последующей сериализации в xml
		 * @param iUmiHierarchyElement $page страница
		 * @return array
		 */
		protected function translateData(iUmiHierarchyElement $page) {
			$pageCollection = umiHierarchy::getInstance();

			$result = [
				'@id' => $page->getId(),
				'@parentId' => $page->getParentId(),
				'@link' => $pageCollection->getPathById($page->getId()),
				'@object-id' => $page->getObjectId(),
				'@object-guid' => $page->getObject()->getGUID(),
				'@type-id' => $page->getObjectTypeId(),
				'@type-guid' => $page->getObject()->getTypeGUID(),
				'@alt-name' => $page->getAltName(),
				'@update-time' => $page->getUpdateTime(),
				'name' => str_replace(['<', '>'], ['&lt;', '&gt;'], $page->getName()),
				'xlink:href' => 'upage://' . $page->getId()
			];

			if ($page->getIsDefault()) {
				$result['@is-default'] = $page->getIsDefault();
			}

			if ($page->getIsVisible()) {
				$result['@is-visible'] = $page->getIsVisible();
			}

			if ($page->getIsActive()) {
				$result['@is-active'] = $page->getIsActive();
			}

			if ($page->getIsDeleted()) {
				$result['@is-deleted'] = $page->getIsDeleted();
			}

			$lockInfo = $this->getLockInfo($page);

			if (!empty($lockInfo)) {
				$result['locked-by'] = $lockInfo;
			}

			$expirationInfo = $this->getExpirationInfo($page);

			if (!empty($expirationInfo)) {
				$result['expiration'] = $expirationInfo;
			}

			if (getRequest('virtuals') !== null && $page->hasVirtualCopy()) {
				$result['has-virtual-copy'] = 1;

				if ($page->isOriginal()) {
					$result['is-original'] = 1;
				}
			}

			if (getRequest('templates') !== null) {
				$result['@template-id'] = $page->getTplId();
				$result['@domain-id'] = $page->getDomainId();
				$result['@lang-id'] = $page->getLangId();
			}

			if (getRequest('childs') !== null) {
				$result['childs'] = $pageCollection->getChildrenCount($page->getId(), true, true, 1);
			}

			if (getRequest('permissions') !== null) {
				$result['permissions'] = $this->getPermissionLevel($page);
			}

			$hierarchyType = umiHierarchyTypesCollection::getInstance()
				->getType($page->getTypeId());
			$result['basetype'] = $hierarchyType;

			if (getRequest('links') !== null && !$page->getIsDeleted()) {
				$linkList = $this->getAdminLinkList($page, $hierarchyType);

				if (isset($linkList[0])) {
					$result['create-link'] = $linkList[0];
				}

				if (isset($linkList[1])) {
					$result['edit-link'] = $linkList[1];
				}
			}

			if (!$this->getOption('serialize-related-entities')) {
				return $result;
			}

			$objectType = umiObjectTypesCollection::getInstance()
				->getType($page->getObjectTypeId());

			if (!$objectType instanceof iUmiObjectType) {
				return $result;
			}

			$result['properties'] = [
				'nodes:group' => []
			];

			$i = 0;
			$isJson = Service::Request()
				->isJson();

			foreach ($objectType->getFieldsGroupsList() as $group) {
				/** @var umiFieldsGroupWrapper $serializer */
				$serializer = parent::get($group);

				foreach ($this->getOptionList() as $name => $value) {
					$serializer->setOption($name, $value);
				}

				$serializedGroup = $serializer->translateProperties($group, $page->getObject());

				if (empty($serializedGroup)) {
					continue;
				}

				$index = $isJson ? $i++ : ++$i;
				$result['properties']['nodes:group'][$index] = $serializedGroup;
			}

			return $result;
		}

		/**
		 * Возвращает уровень прав на действия текущего пользователя над страницей
		 * @param iUmiHierarchyElement $page
		 * @return int
		 */
		private function getPermissionLevel(iUmiHierarchyElement $page) {
			$level = permissionsCollection::getInstance()
				->isAllowedObject(Service::Auth()->getUserId(), $page->getId());

			return ($level[4] ? 16 : 0) |
				($level[3] ? 8 : 0) |
				($level[2] ? 4 : 0) |
				($level[1] ? 2 : 0) |
				($level[0] ? 1 : 0);
		}

		/**
		 * Возвращает список ссылок на страницы с административными действиями над страницей заданного типа
		 * @param iUmiHierarchyElement $page страница
		 * @param iUmiHierarchyType $type тип страницы
		 * @return array
		 */
		private function getAdminLinkList(iUmiHierarchyElement $page, iUmiHierarchyType $type) {
			$module = cmsController::getInstance()
				->getModule($type->getName());

			if (!$module instanceof def_module) {
				return [];
			}

			/** @var content|users|news|catalog|def_module $module */
			return (array) $module->getEditLink($page->getId(), $type->getExt());
		}

		/**
		 * Возвращает информацию об блокировке страницы
		 * @param iUmiHierarchyElement $page страница
		 * @return array
		 */
		private function getLockInfo(iUmiHierarchyElement $page) {
			$lockedId = (int) $page->getValue('lockuser');

			if ($lockedId === 0) {
				return [];
			}

			$lockTime = $page->getValue('locktime');
			$lockDuration = (int) Service::Registry()
				->get('//settings/lock_duration');

			if (!$lockTime instanceof iUmiDate || !(($lockTime->getDateTimeStamp() + $lockDuration) > time())) {
				$page->setValue('lockuser', null);
				$page->setValue('locktime', null);
				$page->commit();
				return [];
			}

			if (Service::Auth()->getUserId() == $lockedId) {
				return [];
			}

			$locker = umiObjectsCollection::getInstance()
				->getObject($lockedId);

			if (!$locker instanceof iUmiObject) {
				return [];
			}

			return [
				'user-id' => $lockedId,
				'login' => $locker->getValue('login'),
				'lname' => $locker->getValue('lname'),
				'fname' => $locker->getValue('fname'),
				'father-name' => $locker->getValue('father_name'),
				'locktime' => $lockTime->getFormattedDate(),
				'@ts' => $lockTime->getDateTimeStamp()
			];
		}

		/**
		 * Возвращает информацию об истечении срока публикации страницы
		 * @param iUmiHierarchyElement $page страница
		 * @return array
		 */
		private function getExpirationInfo(iUmiHierarchyElement $page) {
			if (!Service::Registry()->get('//settings/expiration_control')) {
				return [];
			}

			$status = umiObjectsCollection::getInstance()
				->getObject($page->getValue('publish_status'));

			if (!$status instanceof iUmiObject) {
				return [
					'status' => [
						'@id' => 'page_status_publish',
						'#name' => getLabel('object-status-publish'),
					],
					'@ts' => ''
				];
			}

			$expiration = [
				'status' => [
					'@id' => $status->getValue('publish_status_id') ?: 'page_status_publish',
					'#name' => $status->getName(),
				],
			];

			$expirationTime = $page->getValue('expiration_date');

			if ($expirationTime instanceof iUmiDate) {
				$expiration['@id'] = $expirationTime->getDateTimeStamp();
				$expiration['date'] = $expirationTime->getFormattedDate();
				$expiration['comments'] = $page->getValue('publish_comments');
			}

			return $expiration;
		}
	}
