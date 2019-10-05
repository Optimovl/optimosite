<?php

	/** Интерфейс работника с контроллером приложения */
	interface iUmiControllerInjector {

		/**
		 * Возвращает экземпляр контроллера приложения
		 * @return iCmsController
		 * @throws Exception
		 */
		public function getCmsController();

		/**
		 * Устанавливает экземпляр коллекции языков
		 * @param iCmsController $cmsController экземпляр контроллера приложения
		 * @return iUmiControllerInjector
		 */
		public function setCmsController(iCmsController $cmsController);
	}
