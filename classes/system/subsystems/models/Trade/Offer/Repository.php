<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Entity\Repository as AbstractRepository;

	/**
	 * Класс репозитория торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	class Repository extends AbstractRepository implements iRepository {

		/** @var int NEXT_ORDER_STEP шаг увеличение индекса сортировки */
		const NEXT_ORDER_STEP = 100;

		/** @inheritdoc */
		public function save(iEntity $offer) {
			if (!$this->isValidEntity($offer)) {
				throw new \ErrorException('Incorrect entity given');
			}

			try {
				/** @var iOffer $offer */
				if (!$offer->hasId() && !$offer->hasOrder()) {
					$orderIndex = $this->calculateOrder($offer->getTypeId());
					$offer->setOrder($orderIndex);
				}

				parent::save($offer);

			} catch (\databaseException $exception) {
				$isErrorAboutVendorCode = contains($exception->getMessage(), iMapper::VENDOR_CODE);

				if ($this->getConnection()->isDuplicateKey($exception) && $isErrorAboutVendorCode) {
					throw new \databaseException(getLabel('label-error-duplicate-vendor-code'));
				}

				throw $exception;
			}

			return $offer;
		}

		/**
		 * Вычисляет индекс сортировки для нового торгового предложения
		 * @param int $typeId идентификатор типа торгового предложения
		 * @return int
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		private function calculateOrder($typeId) {
			$table = $this->getTable();
			$typeId = (int) $typeId;
			$sql = <<<SQL
SELECT max(`order`) as `order` FROM `$table` WHERE `type_id` = $typeId;
SQL;
			$orderData = $this->getConnection()
				->queryResult($sql)
				->setFetchAssoc()
				->fetch();
			$maxOrder = $orderData['order'] ?: 0;
			return $maxOrder + self::NEXT_ORDER_STEP;
		}

		/** @inheritdoc */
		protected function isValidEntity($entity) {
			return $entity instanceof iOffer;
		}
	}