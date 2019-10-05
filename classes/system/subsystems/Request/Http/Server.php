<?php

	namespace UmiCms\System\Request\Http;

	use UmiCms\System\Patterns\ArrayContainer;

	/**
	 * Класс контейнера серверных переменных
	 * @package UmiCms\System\Request\Http
	 */
	class Server extends ArrayContainer implements iServer {

		/** @inheritdoc */
		public function __construct(array $array = []) {
			if (empty($array)) {
				$this->array = $_SERVER;
			} else {
				parent::__construct($array);
			}
		}
	}
