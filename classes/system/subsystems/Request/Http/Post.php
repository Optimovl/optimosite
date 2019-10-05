<?php

	namespace UmiCms\System\Request\Http;

	use UmiCms\System\Patterns\ArrayContainer;

	/**
	 * Класс контейнера POST параметров
	 * @package UmiCms\System\Request\Http
	 */
	class Post extends ArrayContainer implements iPost {

		/** @inheritdoc */
		public function __construct(array $array = []) {
			if (empty($array)) {
				$this->array = $_POST;
			} else {
				parent::__construct($array);
			}
		}
	}
