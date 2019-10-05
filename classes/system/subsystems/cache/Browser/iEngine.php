<?php

	namespace UmiCms\System\Cache\Browser;

	use UmiCms\System\Request\iFacade as iRequest;
	use UmiCms\System\Response\iFacade as iResponse;

	/**
	 * Интерфейс реализации браузерного кеширования
	 * @package UmiCms\System\Cache\Browser
	 */
	interface iEngine {

		/**
		 * Конструктор
		 * @param iRequest $request запрос
		 * @param iResponse $response ответ
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(iRequest $request, iResponse $response, \iConfiguration $configuration);

		/** Запускает кеширование */
		public function process();
	}
