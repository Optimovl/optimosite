<?php
	namespace UmiCms\System\Import\UmiDump\Helper\Solution\Postfix;

	/**
	 * Класс строителя постфикса решения
	 * @package UmiCms\System\Import\UmiDump\Helper\Solution\Postfix
	 */
	class Builder implements iBuilder {

		/** @inheritdoc */
		public function run($solution, $domainId) {
			return sprintf('%s-%d', $solution, $domainId);
		}
	}