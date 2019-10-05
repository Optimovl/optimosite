<?php

	use UmiCms\Service;

	/** Класс xml транслятора (сериализатора) языков */
	class langWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iLang $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует язык в массив с разметкой для последующей сериализации в xml
		 * @param iLang $lang
		 * @return array
		 */
		protected function translateData(iLang $lang) {
			$result = [
				'attribute:id' => $lang->getId(),
				'attribute:prefix' => $lang->getPrefix(),
				'node:title' => $lang->getTitle()
			];

			if ($lang->getIsDefault()) {
				$result['attribute:is-default'] = 1;
			}

			if (Service::LanguageDetector()->detectId() == $lang->getId()) {
				$result['attribute:is-current'] = 1;
			}

			return $result;
		}
	}
