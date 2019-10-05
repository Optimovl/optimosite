<?php

	namespace UmiCms\System\Request\Http;

	use UmiCms\System\Patterns\ArrayContainer;

	/**
	 * Класс контейнера кук запроса
	 * @package UmiCms\System\Request\Http
	 */
	class Cookies extends ArrayContainer implements iCookies {

		/** @inheritdoc */
		public function __construct(array $array = []) {
			if (empty($array)) {
				$this->array = $_COOKIE;
			} else {
				parent::__construct($array);
			}
		}
	}
