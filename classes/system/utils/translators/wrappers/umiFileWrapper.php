<?php

	/** Класс xml транслятора (сериализатора) файла */
	class umiFileWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iUmiFile $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует файл с разметкой для последующей сериализации в xml
		 * @param iUmiFile $file файл
		 * @return array
		 */
		protected function translateData(iUmiFile $file) {
			$result = [
				'attribute:id' => $file->getId(),
				'attribute:path' => $file->getFilePath(),
				'attribute:size' => $file->getSize(),
				'attribute:ext' => $file->getExt(),
				'attribute:ord' => $file->getOrder(),
				'node:src' => $file->getFilePath(true)
			];

			if ($file instanceof iUmiImageFile) {
				$result['attribute:width'] = $file->getWidth();
				$result['attribute:height'] = $file->getHeight();
				$result['attribute:alt'] = $file->getAlt();
				$result['attribute:title'] = $file->getTitle();
			}

			return $result;
		}
	}
