<?php
	namespace UmiCms\System\Orm\Entity\Relation\Accessor;

	use UmiCms\System\Orm\Entity\iAccessor;

	/**
	 * Трейт инжектора аксессора связей сущности
	 * @package UmiCms\System\Orm\Entity\Relation\Accessor
	 */
	trait tInjector {

		/** @var iAccessor $attributeAccessor аксессор связей сущности */
		private $relationAccessor;

		/**
		 * Возвращает аксессор связей сущности
		 * @return iAccessor
		 */
		protected function getRelationAccessor() {
			return $this->relationAccessor;
		}

		/**
		 * Устанавливает аксессор связей сущности
		 * @param iAccessor $accessor аксессор связей сущности
		 * @return $this
		 */
		protected function setRelationAccessor(iAccessor $accessor) {
			$this->relationAccessor = $accessor;
			return $this;
		}
	}