<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\Entity\Exchange as AbstractExchange;

	/**
	 * Класс фасада импорта и экспорта складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Exchange extends AbstractExchange implements iExchange {}