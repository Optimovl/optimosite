<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Orm\Entity\Facade as AbstractFacade;

	/**
	 * Класс фасада типов цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	class Facade extends AbstractFacade implements iFacade {

		/** @inheritdoc */
		public function getByName($name) {
			$defaultTypeList = $this->getListBy(iMapper::NAME, $name);
			return array_shift($defaultTypeList);
		}

		/** @inheritdoc */
		public function create(array $attributeList = []) {
			if (!isset($attributeList[iMapper::NAME])) {
				throw new \ErrorException('Price type name expected');
			}

			if (!isset($attributeList[iMapper::TITLE])) {
				throw new \ErrorException('Price type title expected');
			}

			if ($this->getDefault() instanceof iType) {
				unset($attributeList[iMapper::IS_DEFAULT]);
			}

			return parent::create($attributeList);
		}

		/**
		 * @inheritdoc
		 * @param iEntity $entity
		 * @return AbstractFacade|iFacade
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function save(iEntity $entity) {
			/** @var iType $entity */
			if (!$this->isValidEntity($entity)) {
				throw new \ErrorException('Incorrect entity given');
			}

			$defaultEntity = $this->getDefault();

			if ($defaultEntity instanceof iType && $entity->isDefault() && $defaultEntity->getId() !== $entity->getId()) {
				$entity->setDefault(false);
			}

			return parent::save($entity);
		}

		/** @inheritdoc */
		public function copy(iEntity $source) {
			$attributeList = $this->extractAttributeList($source);
			unset($attributeList[iMapper::ID]);

			/** @var iType $source */
			if ($source->isDefault()) {
				unset($attributeList[iMapper::IS_DEFAULT]);
			}

			$copyName = sprintf('name_%d', rand());
			$attributeList[iMapper::NAME] = $copyName;
			return $this->create($attributeList);
		}

		/**
		 * @inheritdoc
		 * @param int $id
		 * @return $this|AbstractFacade|iFacade
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function delete($id) {
			$defaultType = $this->getDefault();

			if ($defaultType instanceof iType && $defaultType->getId() == $id) {
				return $this;
			}

			return parent::delete($id);
		}

		/** @inheritdoc */
		public function deleteList(array $idList) {
			$defaultType = $this->getDefault();

			if ($defaultType instanceof iType) {
				$idList = array_filter($idList, function($id) use ($defaultType) {
					return $id != $defaultType->getId();
				});
			}

			return parent::deleteList($idList);
		}

		/** @inheritdoc */
		public function getDefault() {
			$defaultTypeList = $this->getListBy(iMapper::IS_DEFAULT, true);
			return array_shift($defaultTypeList);
		}

		/** @inheritdoc */
		public function setDefault(iType $type) {
			/** @var iType[] $defaultTypeList */
			$defaultTypeList = $this->getListBy(iMapper::IS_DEFAULT, true);
			$repository = $this->getRepository();

			foreach ($defaultTypeList as $defaultType) {
				$defaultType->setDefault(false);
				$repository->save($defaultType);
			}

			$type->setDefault();
			return $repository->save($type);
		}

		/** @inheritdoc */
		protected function isValidEntity($entity) {
			return $entity instanceof iType;
		}
	}