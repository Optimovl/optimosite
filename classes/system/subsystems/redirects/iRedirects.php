<?php

	use UmiCms\System\Hierarchy\Domain\iDetector;

	/** Интерфейс менеджера редиректов */
	interface iRedirects {

		/**
		 * Возвращает экземпляр класса
		 * @return iRedirects
		 */
		public static function getInstance();

		/**
		 * Создает редирект
		 * @param string $source адрес, откуда перенаправлять
		 * @param string $target адрес, куда перенаправлять
		 * @param int $status код статуса редиректа
		 * @param bool|int $madeByUser редирект сделан пользователем (не автоматически)
		 * @return mixed
		 */
		public function add($source, $target, $status = 301, $madeByUser = false);

		/**
		 * Возвращает данные редиректов по адресу, откуда перенаправлять
		 * @param string $source адрес, откуда перенаправлять
		 * @param bool|int $madeByUser редирект сделан пользователем (не автоматически)
		 * @return array
		 */
		public function getRedirectsIdBySource($source, $madeByUser = false);

		/**
		 * Возвращает данные редиректов по адресу, куда перенаправлять
		 * @param string $target адрес, куда перенаправлять
		 * @param bool|int $madeByUser редирект сделан пользователем (не автоматически)
		 * @return mixed
		 */
		public function getRedirectIdByTarget($target, $madeByUser = false);

		/**
		 * Удаляет редирект
		 * @param int $id идентификатор редиректа
		 * @return mixed
		 */
		public function del($id);

		/**
		 * Осуществляет редирект, если это необходимо
		 * @param string $currentUri текущая страница
		 * @param bool|int $madeByUser учитывать редиректы ручные/автоматические
		 * @return mixed
		 */
		public function redirectIfRequired($currentUri, $madeByUser = false);

		/**
		 * Удаляет все редиректы
		 * @return mixed
		 */
		public function deleteAll();

		/**
		 * Включает обработчики событий изменения страниц
		 * @return mixed
		 */
		public function init();

		/**
		 * Устанавливает определитель домена
		 * @param iDetector $detector определитель домена
		 * @return $this
		 */
		public function setDomainDetector(iDetector $detector);

		/**
		 * Устанавливает коллекцию доменов
		 * @param iDomainsCollection $domainCollection коллекция доменов
		 * @return $this
		 */
		public function setDomainCollection(\iDomainsCollection $domainCollection);

		/**
		 * Устанавливает коллекцию языков
		 * @param iLangsCollection $languageCollection коллекция языков
		 * @return $this
		 */
		public function setLanguageCollection(\iLangsCollection $languageCollection);
	}
