<?php

	namespace UmiCms\System\Request\Http;

	use UmiCms\System\Patterns\ArrayContainer;

	/**
	 * Класс контейнера GET параметров
	 * @package UmiCms\System\Request\Http
	 */
	class Get extends ArrayContainer implements iGet {

		/** @inheritdoc */
		public function __construct(array $array = []) {
			if (empty($array)) {
				$this->array = $_GET;
			} else {
				parent::__construct($array);
			}
		}
	}
