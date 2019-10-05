<?php

	/** Интерфейс зеркала домена */
	interface iDomainMirror extends iUmiEntinty {

		/**
		 * Возвращает хост домена
		 * @param bool $encode кодировать домен в punycode
		 * @return string
		 */
		public function getHost($encode = false);

		/**
		 * Устанавливает хост
		 * @param string $host
		 * @throws wrongParamException если хост невалидный
		 */
		public function setHost($host);

		/**
		 * Возвращает идентификаторы домена, к которому принадлежит зеркало
		 * @return int
		 */
		public function getDomainId();

		/**
		 * Устанавливает идентификатор домена, к которому принадлежит зеркало
		 * @param int $id идентификатор домена
		 * @throws coreException если домена с заданным идентификатором не существует
		 */
		public function setDomainId($id);
	}
