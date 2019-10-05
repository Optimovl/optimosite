<?php

	namespace UmiCms\Classes\System\Entities\Directory;

	/**
	 * Фабрика директорий
	 * @package UmiCms\Classes\System\Entities\Directory
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create($path) {
			return new \umiDirectory($path);
		}
	}
