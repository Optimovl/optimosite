<?php
	namespace UmiCms\System\Orm\Entity\Repository;

	use UmiCms\System\Orm\Entity\iRepository;

	/**
	 * Трейт инжектора репозитория сущностей
	 * @package UmiCms\System\Orm\Entity\Repository
	 */
	trait tInjector {

		/** @var iRepository $repository репозиторий сущностей */
		private $repository;

		/**
		 * Возвращает репозиторий сущностей
		 * @return iRepository
		 */
		protected function getRepository() {
			return $this->repository;
		}

		/**
		 * Устанавливает репозиторий сущностей
		 * @param iRepository $repository репозиторий
		 * @return $this
		 */
		protected function setRepository(iRepository $repository) {
			$this->repository = $repository;
			return $this;
		}
	}