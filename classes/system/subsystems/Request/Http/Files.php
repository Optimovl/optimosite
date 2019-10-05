<?php

	namespace UmiCms\System\Request\Http;

	use UmiCms\System\Patterns\ArrayContainer;

	/**
	 * Класс контейнера загруженных файлов
	 * @package UmiCms\System\Request\Http
	 */
	class Files extends ArrayContainer implements iFiles {

		/** @inheritdoc */
		public function __construct(array $array = []) {
			if (empty($array)) {
				$this->array = $_FILES;
			} else {
				parent::__construct($array);
			}
		}
	}
