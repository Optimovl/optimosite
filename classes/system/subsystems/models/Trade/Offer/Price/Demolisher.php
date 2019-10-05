<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\Entity\Demolisher as AbstractDemolisher;

	/**
	 * Класс удаления импортированных цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	class Demolisher extends AbstractDemolisher implements iDemolisher {}