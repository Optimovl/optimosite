<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\Entity\Exporter as AbstractExporter;

	/**
	 * Класс экспортера складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Exporter extends AbstractExporter implements iExporter {}