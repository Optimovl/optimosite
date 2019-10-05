<?php

	namespace UmiCms\System\Utils\Url;

	use UmiCms\System\Utils\iUrl;

	/**
	 * Интерфейс фабрики url
	 * @package UmiCms\System\Url
	 */
	interface iFactory {

		/**
		 * Создает экземпляр url
		 * @param string $url адрес
		 * @return iUrl
		 */
		public function create($url);
	}