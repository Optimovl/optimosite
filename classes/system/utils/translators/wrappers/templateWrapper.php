<?php

	/** Класс xml транслятора (сериализатора) шаблонов */
	class templateWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iTemplate $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует шаблон в массив с разметкой для последующей сериализации в xml
		 * @param iTemplate $template шаблон
		 * @return array
		 */
		protected function translateData(iTemplate $template) {
			$result = [
				'attribute:id' => $template->getId(),
				'attribute:title' => $template->getTitle(),
				'attribute:name' => $template->getName(),
				'attribute:type' => $template->getType(),
				'attribute:filename' => $template->getFilename(),
				'attribute:domain-id' => $template->getDomainId(),
				'attribute:lang-id' => $template->getLangId(),
			];

			if ($template->getIsDefault()) {
				$result['attribute:is-default'] = true;
			}

			return $result;
		}
	}
