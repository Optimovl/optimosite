<?php
	namespace UmiCms\System\Import\UmiDump\Helper\Solution\Postfix;

	/**
	 * Интерфейс фильтра постфикса решения
	 * @package UmiCms\System\Import\UmiDump\Helper\Solution\Postfix
	 */
	interface iFilter {

		/**
		 * Фильтрует постфикс из имени решения
		 * @param string $solution имя решения
		 * @return string
		 */
		public function run($solution);
	}