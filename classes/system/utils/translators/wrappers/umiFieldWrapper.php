<?php

	/** Класс xml транслятора (сериализатора) поля */
	class umiFieldWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iUmiField $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует поле в массив с разметкой для последующей сериализации в xml
		 * @param iUmiField $field поле
		 * @return array
		 */
		public function translateData(iUmiField $field) {
			$result = [
				'attribute:id' => $field->getId(),
				'attribute:name' => $field->getName(),
				'attribute:title' => $field->getTitle(),
				'attribute:field-type-id' => $field->getFieldTypeId(),
				'type' => $field->getFieldType()
			];

			if ($field->getIsVisible()) {
				$result['attribute:visible'] = 'visible';
			}

			if ($field->getIsInheritable()) {
				$result['attribute:inheritable'] = 'inheritable';
			}

			if ($field->getIsLocked()) {
				$result['attribute:locked'] = 'locked';
			}

			if ($field->getIsInFilter()) {
				$result['attribute:filterable'] = 'filterable';
			}

			if ($field->getIsInSearch()) {
				$result['attribute:indexable'] = 'indexable';
			}

			if ($field->getGuideId()) {
				$result['attribute:guide-id'] = $field->getGuideId();
			}

			if ($field->getTip()) {
				$result['tip'] = $field->getTip();
			}

			if ($field->getIsRequired()) {
				$result['attribute:required'] = 'required';
			}

			if ($field->isImportant()) {
				$result['attribute:important'] = 'important';
			}

			if ($field->getIsSystem()) {
				$result['attribute:system'] = 'system';
			}

			if ($field->getRestrictionId()) {
				$result['restriction'] = baseRestriction::get($field->getRestrictionId());
			}

			return $result;
		}
	}
