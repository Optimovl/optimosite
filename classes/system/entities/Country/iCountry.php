<?php

	namespace UmiCms\Classes\System\Entities\Country;

	/**
	 * Интерфейс страны
	 * @package UmiCms\Classes\System\Entities\Country;
	 */
	interface iCountry {

		/**
		 * Возвращает название страны
		 * @return string
		 */
		public function getName();

		/**
		 * Возвращает ISO код страны
		 * @return string
		 */
		public function getISOCode();
	}
