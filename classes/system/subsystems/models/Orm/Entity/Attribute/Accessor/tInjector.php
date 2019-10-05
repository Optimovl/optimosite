<?php
	namespace UmiCms\System\Orm\Entity\Attribute\Accessor;

	use UmiCms\System\Orm\Entity\iAccessor;

	/**
	 * Трейт инжектора аксессора атрибутов сущности
	 * @package UmiCms\System\Orm\Entity\Attribute\Accessor
	 */
	trait tInjector {

		/** @var iAccessor $attributeAccessor аксессор атрибутов сущности */
		private $attributeAccessor;

		/**
		 * Возвращает аксессор атрибутов сущности
		 * @return iAccessor
		 */
		protected function getAttributeAccessor() {
			return $this->attributeAccessor;
		}

		/**
		 * Устанавливает аксессор атрибутов сущности
		 * @param iAccessor $accessor аксессор атрибутов сущности
		 * @return $this
		 */
		protected function setAttributeAccessor(iAccessor $accessor) {
			$this->attributeAccessor = $accessor;
			return $this;
		}
	}