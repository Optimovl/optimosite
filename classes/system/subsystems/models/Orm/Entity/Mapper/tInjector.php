<?php
	namespace UmiCms\System\Orm\Entity\Mapper;

	use UmiCms\System\Orm\Entity\iMapper;

	/**
	 * Трейт инжектора маппера сущностей
	 * @package UmiCms\System\Orm\Entity\Mapper
	 */
	trait tInjector {

		/** @var iMapper $mapper маппер сущности */
		private $mapper;

		/**
		 * Устанавливает маппер сущности
		 * @param iMapper $mapper маппер
		 * @return $this
		 */
		protected function setMapper(iMapper $mapper) {
			$this->mapper = $mapper;
			return $this;
		}

		/**
		 * Возвращает маппер
		 * @return iMapper
		 */
		protected function getMapper() {
			return $this->mapper;
		}
	}