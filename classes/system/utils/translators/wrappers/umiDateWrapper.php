<?php

	/** Класс xml транслятора (сериализатора) дат */
	class umiDateWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iUmiDate $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует дату в массив с разметкой для последующей сериализации в xml
		 * @param iUmiDate $date дата
		 * @return array
		 */
		protected function translateData(iUmiDate $date) {
			return [
				'attribute:unix-timestamp' => $date->getFormattedDate('U'),
				'attribute:rfc' => $date->getFormattedDate('r'),
				'attribute:formatted-date' => $date->getFormattedDate('d.m.Y H:i'),
				'node:std' => $date->getFormattedDate()
			];
		}
	}
