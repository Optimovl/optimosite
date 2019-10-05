<?php
	namespace UmiCms\System\Import\UmiDump\Helper\Solution\Postfix;

	/**
	 * Интерфейс строителя постфикса решения
	 * @package UmiCms\System\Import\UmiDump\Helper\Solution\Postfix
	 */
	interface iBuilder {

		/**
		 * Добавляет постфикс в имя решения
		 * @param string $solution имя решения
		 * @param int $domainId идентификатор домена
		 * @return string
		 */
		public function run($solution, $domainId);
	}