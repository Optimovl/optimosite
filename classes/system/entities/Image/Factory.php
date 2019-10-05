<?php
	namespace UmiCms\Classes\System\Entities\Image;

	use UmiCms\Classes\System\Entities\File\Factory as FileFactory;

	/**
	 * Класс фабрики изображений
	 * @package UmiCms\Classes\System\Entities\Image
	 */
	class Factory extends FileFactory implements iFactory {

		/** @inheritdoc */
		public function create($path) {
			return $this->createSecure($path);
		}

		/** @inheritdoc */
		public function createWithAttributes($path, $attributeList = []) {
			$image = $this->create($path);

			if (isset($attributeList['alt'])) {
				$image->setAlt($attributeList['alt']);
			}

			if (isset($attributeList['title'])) {
				$image->setTitle($attributeList['title']);
			}

			if (isset($attributeList['order'])) {
				$image->setOrder($attributeList['order']);
			}

			return $image;
		}

		/** @inheritdoc */
		protected function instantiate($path) {
			return new \umiImageFile($path);
		}
	}