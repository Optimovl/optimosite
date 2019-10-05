<?php

	/** Класс xml транслятора (сериализатора) ограничений поля */
	class baseRestrictionWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param baseRestriction $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует ограничение поля в массив с разметкой для последующей сериализации в xml
		 * @param baseRestriction $restriction ограничение поля
		 * @return array
		 */
		protected function translateData(baseRestriction $restriction) {
			return [
				'attribute:id' => $restriction->getId(),
				'attribute:name' => $restriction->getClassName(),
				'attribute:field-type-id' => $restriction->getFieldTypeId(),
				'node:title' => $restriction->getTitle()
			];
		}
	}
