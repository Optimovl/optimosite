<?php

	namespace UmiCms\System\Response;

	/** Интерфейс класса, работающего с ответом на запрос */
	interface iInjector {

		/**
		 * Устанавливает фасад над буферами вывода
		 * @param iFacade $response фасад над буферами вывода
		 * @return $this
		 */
		public function setResponse(iFacade $response);

		/**
		 * Возвращает фасад над буферами вывода
		 * @return iFacade
		 * @throws \DependencyNotInjectedException
		 */
		public function getResponse();
	}
