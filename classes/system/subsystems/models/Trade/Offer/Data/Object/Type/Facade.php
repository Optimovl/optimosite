<?php
	namespace UmiCms\System\Trade\Offer\Data\Object\Type;

	use \iUmiObjectType as iType;
	use \iUmiObjectTypesCollection as iTypeCollection;

	/**
	 * Класс фасада типов объектов данных торговых предложений
	 * @package UmiCms\System\Trade\Offer\Data\Object\Type
	 */
	class Facade implements iFacade {

		/** @var iTypeCollection $typeCollection коллекция типов */
		private $typeCollection;

		/** @inheritdoc */
		public function __construct(iTypeCollection $typeCollection) {
			$this->setTypeCollection($typeCollection);
		}

		/** @inheritdoc */
		public function get($id) {
			$type = $this->getTypeCollection()
				->getType($id);

			if (!$type instanceof iType || !$this->isCorrectType($type)) {
				return null;
			}

			return $type;
		}

		/** @inheritdoc */
		public function getList(array $idList) {
			return array_filter($this->getTypeCollection()->getTypeList($idList), function($type) {
				return ($type instanceof iType && $this->isCorrectType($type));
			});
		}

		/** @inheritdoc */
		public function create($name) {
			$typeCollection = $this->getTypeCollection();
			$rootTypeId = $this->getRootType()
				->getId();
			$id = $typeCollection->addType($rootTypeId, $name);
			$type = $typeCollection->getType($id);

			if (!$type instanceof iType) {
				throw new \ErrorException('Cannot create trade offer data object type');
			}

			return $type;
		}

		/** @inheritdoc */
		public function getRootType() {
			$type = $this->getTypeCollection()
				->getTypeByGUID(self::ROOT_TYPE_GUID);

			if (!$type instanceof iType) {
				throw new \ErrorException('Cannot get trade offer data object root type');
			}

			return $type;
		}

		/** @inheritdoc */
		public function delete($id) {
			$typeCollection = $this->getTypeCollection();
			$type = $typeCollection->getType($id);

			if (!$type instanceof iType || !$this->isCorrectType($type)) {
				return $this;
			}

			$typeCollection->delType($id);
			return $this;
		}

		/** @inheritdoc */
		public function isValid($id) {
			return $this->get($id) instanceof iType;
		}

		/**
		 * Определяет корректность типа
		 * @param iType $type проверяемый тип
		 * @return bool
		 * @throws \ErrorException
		 */
		private function isCorrectType(\iUmiObjectType $type) {
			return $type->getHierarchyTypeId() == $this->getRootType()->getHierarchyTypeId();
		}

		/**
		 * Устанавливает коллекцию типов
		 * @param iTypeCollection $typeCollection коллекция типов
		 * @return $this
		 */
		private function setTypeCollection(iTypeCollection $typeCollection) {
			$this->typeCollection = $typeCollection;
			return $this;
		}

		/**
		 * Возвращает коллекцию типов
		 * @return iTypeCollection
		 */
		private function getTypeCollection() {
			return $this->typeCollection;
		}
	}