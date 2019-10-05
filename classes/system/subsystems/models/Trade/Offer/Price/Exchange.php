<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\Entity\Exchange as AbstractExchange;

	/**
	 * Класс фасада импорта и экспорта цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	class Exchange extends AbstractExchange implements iExchange {}