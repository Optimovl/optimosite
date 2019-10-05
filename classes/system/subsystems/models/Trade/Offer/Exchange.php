<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Orm\Entity\Exchange as AbstractExchange;

	/**
	 * Класс фасада импорта и экспорта торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	class Exchange extends AbstractExchange implements iExchange {}