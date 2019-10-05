<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Entity\Repository as AbstractRepository;

	/**
	 * Класс репозитория складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	class Repository extends AbstractRepository implements iRepository {

		/** @inheritdoc */
		protected function isValidEntity($entity) {
			return $entity instanceof iBalance;
		}
	}