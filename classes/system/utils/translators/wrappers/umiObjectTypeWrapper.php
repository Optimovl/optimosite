<?php

	/** Класс xml транслятора (сериализатора) объектного типа данных */
	class umiObjectTypeWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iUmiObjectType $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует объектный тип данных в массив с разметкой для последующей сериализации в xml
		 * @param iUmiObjectType $type объектный тип данных
		 * @return array
		 */
		protected function translateData(iUmiObjectType $type) {
			$result = [
				'attribute:id' => $type->getId(),
				'attribute:guid' => $type->getGUID(),
				'attribute:title' => $type->getName(),
				'attribute:parent-id' => $type->getParentId(),
				'attribute:domain-id' => (int) $type->getDomainId()
			];

			if (getRequest('childs') !== null) {
				$result['attribute:parentId'] = $type->getParentId();
			}

			if ($type->getIsGuidable()) {
				$result['attribute:guide'] = 'guide';
			}

			if ($type->getIsPublic()) {
				$result['attribute:public'] = 'public';
			}

			if ($type->getIsLocked()) {
				$result['attribute:locked'] = 'locked';
			}

			$hierarchyType = umiHierarchyTypesCollection::getInstance()
				->getType($type->getHierarchyTypeId());
			$result['base'] = $hierarchyType;

			if (getRequest('childs') !== null) {
				$childrenList = umiObjectTypesCollection::getInstance()
					->getSubTypesList($type->getId());
				$result['childs'] = umiCount($childrenList);
			}

			if (getRequest('links') !== null) {
				$cmsController = cmsController::getInstance();
				$module = $cmsController->getModule($cmsController->getCurrentModule());

				if ($module instanceof webforms || $module instanceof data) {
					/** @var webforms|data $module */
					$links = $module->getObjectTypeEditLink($type->getId());
					$result['create-link'] = $links['create-link'];
					$result['edit-link'] = $links['edit-link'];
				}
			}

			if ($this->getOption('serialize-related-entities')) {
				$result['fieldgroups'] = [
					'nodes:group' => $type->getFieldsGroupsList(xmlTranslator::$showHiddenFieldGroups)
				];
			}

			return $result;
		}
	}
