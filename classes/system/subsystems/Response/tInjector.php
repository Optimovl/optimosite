<?php

	namespace UmiCms\System\Response;

	/**
	 * Трейт для класса, работающего с ответом на запрос
	 * @package UmiCms\System\Response
	 */
	trait tInjector {

		/** @var iFacade $response фасад над буферами вывода */
		private $response;

		/**
		 * Устанавливает фасад над буферами вывода
		 * @param iFacade $response фасад над буферами вывода
		 * @return $this
		 */
		public function setResponse(iFacade $response) {
			$this->response = $response;
			return $this;
		}

		/**
		 * Возвращает фасад над буферами вывода
		 * @return iFacade
		 * @throws \DependencyNotInjectedException
		 */
		public function getResponse() {
			if (!$this->response instanceof iFacade) {
				throw new \DependencyNotInjectedException('You should inject UmiCms\System\Response\iFacade first');
			}

			return $this->response;
		}
	}
