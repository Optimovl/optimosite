<?php
	namespace UmiCms\System\Trade\Offer\Vendor\Code;

	use UmiCms\System\Trade\iOffer;

	/**
	 * Класс генератора артикула для торговых предложений
	 * @package UmiCms\System\Trade\Offer\Vendor\Code
	 */
	class Generator implements iGenerator {

		/** @var string FORMAT формат артикула */
		const FORMAT = '%d-%d';

		/** @inheritdoc */
		public function generate(iOffer $offer) {
			return sprintf(self::FORMAT, $offer->getId(), $offer->getTypeId());
		}
	}