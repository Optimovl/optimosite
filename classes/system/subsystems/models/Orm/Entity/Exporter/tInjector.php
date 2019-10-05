<?php
	namespace UmiCms\System\Orm\Entity\Exporter;

	use UmiCms\System\Orm\Entity\iExporter;

	/**
	 * Трейт инжектора экспортера сущностей
	 * @package UmiCms\System\Orm\Entity\Exporter
	 */
	trait tInjector {

		/** @var iExporter $exporter экспортер сущностей */
		private $exporter;

		/**
		 * Возвращает экспортер сущностей
		 * @return iExporter
		 */
		protected function getExporter() {
			return $this->exporter;
		}

		/**
		 * Устанавливает экспортер сущностей
		 * @param iExporter $exporter экспортер сущностей
		 * @return $this
		 */
		protected function setExporter(iExporter $exporter) {
			$this->exporter = $exporter;
			return $this;
		}
	}