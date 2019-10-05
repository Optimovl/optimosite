<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Orm\Entity\Repository as AbstractRepository;

	/**
	 * Класс репозитория типов цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	class Repository extends AbstractRepository implements iRepository {

		/** @inheritdoc */
		protected function isValidEntity($entity) {
			return $entity instanceof iType;
		}

		/** @inheritdoc */
		public function save(iEntity $entity) {
			if (!$this->isValidEntity($entity)) {
				throw new \ErrorException('Incorrect entity given');
			}

			try {
				if ($entity->hasId()) {
					$entity = $this->update($entity);
				} else {
					$entity = $this->create($entity);
				}
			} catch (\databaseException $exception) {
				$isErrorAboutName = contains($exception->getMessage(), iMapper::NAME);

				if ($this->getConnection()->isDuplicateKey($exception) && $isErrorAboutName) {
					throw new \databaseException(getLabel('label-error-duplicate-price-type-name'));
				}

				throw $exception;
			}

			return $entity->setUpdated(false);
		}
	}