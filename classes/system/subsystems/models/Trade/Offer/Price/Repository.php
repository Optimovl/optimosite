<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Orm\Entity\Repository as AbstractRepository;

	/**
	 * Класс репозитория цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	class Repository extends AbstractRepository implements iRepository {

		/** @inheritdoc */
		protected function isValidEntity($entity) {
			return $entity instanceof iPrice;
		}
	}