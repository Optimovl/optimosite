<?php

	use UmiCms\Service;

	/** Класс xml транслятора (сериализатора) групп полей */
	class umiFieldsGroupWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iUmiFieldsGroup $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует группу полей со значениями полей объекта в массив с разметкой для последующей сериализации в xml
		 * @param iUmiFieldsGroup $group группа полей
		 * @param iUmiObject $object объект
		 * @return array
		 */
		public function translateProperties(iUmiFieldsGroup $group, iUmiObject $object) {
			$result = [
				'attribute:id' => $group->getId(),
				'attribute:name' => $group->getName(),
				'title' => $group->getTitle(),
				'nodes:property' => []
			];

			$i = 0;
			$isJson = Service::Request()
				->isJson();

			foreach ($group->getFields() as $field) {

				if (!$this->isValidFieldName($field->getName())) {
					continue;
				}

				$property = $object->getPropByName($field->getName());

				if (!$property instanceof iUmiObjectProperty) {
					continue;
				}

				/** @var umiObjectPropertyWrapper $serializer */
				$serializer = parent::get($property);

				foreach ($this->getOptionList() as $name => $value) {
					$serializer->setOption($name, $value);
				}

				$serializedProperty = $serializer->translate($property);

				if (empty($serializedProperty)) {
					continue;
				}

				$index = $isJson ? $i++ : ++$i;
				$result['nodes:property'][$index] = $serializedProperty;
			}

			return empty($result['nodes:property']) ? [] : $result;
		}

		/**
		 * Преобразует группу полей в массив с разметкой для последующей сериализации в xml
		 * @param iUmiFieldsGroup $group группа полей
		 * @return array
		 */
		protected function translateData(iUmiFieldsGroup $group) {
			$result = [
				'attribute:id' => $group->getId(),
				'attribute:name' => $group->getName(),
				'attribute:title' => $group->getTitle(),
				'nodes:field' => $group->getFields()
			];

			if ($group->getTip()) {
				$result['tip'] = $group->getTip();
			}

			if ($group->getIsVisible()) {
				$result['attribute:visible'] = 'visible';
			}

			if ($group->getIsLocked()) {
				$result['attribute:locked'] = 'locked';
			}

			return $result;
		}

		/**
		 * Валидирует имя поля
		 * @param string $name имя поля
		 * @return bool
		 */
		private function isValidFieldName($name) {
			$nameWhiteList = $this->getOption('field-name-white-list');

			if (!is_array($nameWhiteList)) {
				return true;
			}

			return in_array($name, $nameWhiteList);
		}
	}
