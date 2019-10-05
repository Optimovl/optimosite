<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\Entity\Exchange as AbstractExchange;

	/**
	 * Класс фасада импорта и экспорта типов цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	class Exchange extends AbstractExchange implements iExchange {}