<?php

	/** Класс xml транслятора (сериализатора) зеркал домена */
	class domainMirrorWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iDomainMirror $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует зеркало домена в массив с разметкой для последующей сериализации в xml
		 * @param iDomainMirror $domainMirror зеркало домена
		 * @return array
		 */
		protected function translateData(iDomainMirror $domainMirror) {
			return [
				'attribute:id' => $domainMirror->getId(),
				'attribute:host' => $domainMirror->getHost()
			];
		}
	}
