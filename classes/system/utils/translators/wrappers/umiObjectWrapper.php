<?php

	use UmiCms\Service;

	/** Класс xml транслятора (сериализатора) объекта */
	class umiObjectWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iUmiObject $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует объект в массив с разметкой для последующей сериализации в xml
		 * @param iUmiObject $object
		 * @return array
		 */
		protected function translateData(iUmiObject $object) {
			$result = [
				'attribute:id' => $object->getId(),
				'attribute:guid' => $object->getGUID(),
				'attribute:name' => $object->getName(),
				'attribute:type-id' => $object->getTypeId(),
				'attribute:type-guid' => $object->getTypeGUID(),
				'attribute:update-time' => $object->getUpdateTime(),
				'attribute:ownerId' => $object->getOwnerId(),
				'attribute:locked' => (int) $object->getIsLocked(),
				'xlink:href' => $object->getXlink()
			];

			if ($this->getOption('serialize-related-entities') === false) {
				return $result;
			}

			$objectType = umiObjectTypesCollection::getInstance()
				->getType($object->getTypeId());

			if (getRequest('links') !== null) {
				$editLink = $this->getObjectEditLink($object, $objectType);

				if ($editLink) {
					$result['edit-link'] = $editLink;
				}
			}

			$isJson = Service::Request()
				->isJson();
			$i = 0;

			foreach ($objectType->getFieldsGroupsList() as $group) {
				/** @var umiFieldsGroupWrapper $serializer */
				$serializer = parent::get($group);

				foreach ($this->getOptionList() as $name => $value) {
					$serializer->setOption($name, $value);
				}

				$serializedGroup = $serializer->translateProperties($group, $object);

				if (empty($serializedGroup)) {
					continue;
				}

				$index = $isJson ? $i++ : ++$i;
				$result['properties']['nodes:group'][$index] = $serializedGroup;
			}

			return $result;
		}

		/**
		 * Возвращает ссылку на страницу редактирования объекта
		 * @param iUmiObject $object объект
		 * @param iUmiObjectType $objectType тип объекта
		 * @return string
		 */
		protected function getObjectEditLink(iUmiObject $object, iUmiObjectType $objectType) {
			$cmsController = cmsController::getInstance();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()
				->getType($objectType->getHierarchyTypeId());

			if ($hierarchyType instanceof iUmiHierarchyType) {

				$module = $cmsController->getModule($hierarchyType->getName());

				if ($module instanceof def_module) {

					/** @var menu|users|banners|def_module $module */
					$link = $module->getObjectEditLink($object->getId(), $hierarchyType->getExt());

					if (is_string($link)) {
						return $link;
					}
				}
			}

			if ($cmsController->getCurrentModule() == 'data' && $cmsController->getCurrentMethod() == 'guide_items') {
				/** @var data $module */
				$module = $cmsController->getModule('data');
				return (string) $module->getObjectEditLink($object->getId());
			}

			return '';
		}
	}
