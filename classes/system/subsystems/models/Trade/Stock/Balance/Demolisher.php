<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\Entity\Demolisher as AbstractDemolisher;

	/**
	 * Класс удаления импортированных складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Demolisher extends AbstractDemolisher implements iDemolisher {}