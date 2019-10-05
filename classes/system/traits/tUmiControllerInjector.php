<?php

	/** Трейт работника с контроллером приложения */
	trait tUmiControllerInjector {

		/** @var iCmsController $cmsController контроллер приложения */
		private $cmsController;

		/**
		 * Возвращает экземпляр контроллером приложения
		 * @return iCmsController
		 * @throws Exception
		 */
		public function getCmsController() {
			if (!$this->cmsController instanceof iCmsController) {
				throw new Exception('You should set iCmsController first');
			}

			return $this->cmsController;
		}

		/**
		 * Устанавливает экземпляр контроллера приложения
		 * @param iCmsController $cmsController экземпляр контроллера приложения
		 * @return iUmiControllerInjector|tUmiControllerInjector
		 */
		public function setCmsController(iCmsController $cmsController) {
			$this->cmsController = $cmsController;
			return $this;
		}
	}
