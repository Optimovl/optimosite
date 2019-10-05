<?php
	namespace UmiCms\System\Import\UmiDump\Helper\Solution\Postfix;

	/**
	 * Класс фильтра постфикса решения
	 * @package UmiCms\System\Import\UmiDump\Helper\Solution\Postfix
	 */
	class Filter implements iFilter {

		/** @inheritdoc */
		public function run($solution) {
			return preg_replace('|(-\d{1,})$|', '', $solution);
		}
	}