<?php

	/** Интерфейс домена */
	interface iDomain extends iUmiEntinty {

		/**
		 * Возвращает хост домена
		 * @param bool $encode кодировать домен в punycode
		 * @return string
		 */
		public function getHost($encode = false);

		/**
		 * Возвращает хост, кодированный в punycode
		 * @return bool|string
		 */
		public function getEncodedHost();

		/**
		 * Возвращает хост, декодированный из punycode
		 * @return bool|string
		 */
		public function getDecodedHost();

		/**
		 * Устанавливает хост
		 * @param string $host
		 * @throws wrongParamException если хост невалидный
		 */
		public function setHost($host);

		/**
		 * Проверяет является ли домен доменом по умолчанию
		 * @return bool
		 */
		public function getIsDefault();

		/**
		 * Устанавливает значение флага "по умолчанию" домена.
		 * Служебный метод, в прикладном коде стоит использовать:
		 * domainsCollection::setDefaultDomain()
		 * @param bool $flag значение флага
		 */
		public function setIsDefault($flag);

		/**
		 * Определяет использует ли домен ssl
		 * @return bool
		 */
		public function isUsingSsl();

		/**
		 * Устанавливает использует ли домен ssl
		 * @param bool $flag
		 * @return iDomain
		 */
		public function setUsingSsl($flag = true);

		/**
		 * Возвращает иконку домена
		 * @return iUmiImageFile|null
		 */
		public function getFavicon();

		/**
		 * Устанавливает иконку домена
		 * @param iUmiImageFile|null $icon иконка домена
		 * @return $this
		 * @throws wrongParamException
		 */
		public function setFavicon($icon);

		/**
		 * Возвращает идентификатор языка по умолчанию
		 * @return int
		 */
		public function getDefaultLangId();

		/**
		 * Устанавливает язык по умолчанию
		 * @param int $id идентификатор языка
		 * @return bool true
		 * @throws coreException если домена с таким идентификатором не существует
		 */
		public function setDefaultLangId($id);

		/**
		 * Создает зеркало домена
		 * @param string $host хост зеркала
		 * @return int идентификатор созданного зеркала
		 * @throws coreException если зеркало с заданным хостом уже существует
		 */
		public function addMirror($host);

		/**
		 * Удаляет зеркало домена с заданным идентификатором
		 * @param int $id идентификатор зеркала
		 * @return bool
		 * @throws coreException если зеркало с заданным id не существует
		 */
		public function delMirror($id);

		/**
		 * Удаляет все зеркала домена
		 * @return bool
		 */
		public function delAllMirrors();

		/**
		 * Возвращает идентификатор зеркала до его хосту
		 * @param string $host хост зеркала
		 * @param bool $checkIdn преобразовывать хост в punycode
		 * @return int|bool
		 */
		public function getMirrorId($host, $checkIdn = true);

		/**
		 * Возвращает зеркало домена по id
		 * @param int $id идентификатор зеркала домена
		 * @return iDomainMirror|bool
		 */
		public function getMirror($id);

		/**
		 * Проверяет существует ли зеркало с заданным идентификатором
		 * @param int $id идентификатор зеркала
		 * @return bool
		 */
		public function isMirrorExists($id);

		/**
		 * Возвращает список зеркал домена
		 * @return iDomainMirror[]
		 */
		public function getMirrorsList();

		/**
		 * Возвращает хост домена с указанием протокола
		 * @return string
		 */
		public function getUrl();

		/**
		 * Возвращает текущий хост с указанием протокола (хост может быть зеркалом)
		 * @return string
		 */
		public function getCurrentUrl();

		/**
		 * Протокол по которому доступен домен
		 * @return string
		 */
		public function getProtocol();

		/**
		 * Удаляет неподдерживаемые символы из хоста домена
		 * @param string $host
		 * @return string
		 */
		public static function filterHostName($host);

		/**
		 * Возвращает имя текущего домена или зеркала домена, если мы находимся на нем.
		 * @return string
		 */
		public function getCurrentHostName();
	}
