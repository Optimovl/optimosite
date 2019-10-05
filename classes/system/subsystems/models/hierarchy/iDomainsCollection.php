<?php

	use UmiCms\Classes\System\Utils\Idn\iConverter;

	/** Интерфейс коллекции доменов */
	interface iDomainsCollection {

		/**
		 * Конструктор
		 * @param IConnection $connection соединение с базой данных
		 * @param iConverter $idnConverter Idn конвертер
		 */
		public function __construct(IConnection $connection, iConverter $idnConverter);

		/**
		 * Добавляет домен
		 * @param string $host хост домена
		 * @param int|bool $languageId идентификатор языка по умолчанию для домена
		 * @param bool $isDefault будет ли домен доменом по умолчанию
		 * @param bool $usingSsl использует ли домен ssl
		 * @return int идентификатор добавленного домена
		 * @throws coreException если домен с заданным хостом уже существует
		 * @throws wrongParamException если значения параметров невалидны
		 */
		public function addDomain($host, $languageId = false, $isDefault = false, $usingSsl = false);

		/**
		 * Удаляет домен и все связанные с ним сущности.
		 * @param int $id идентификатор домена, который требуется удалить
		 * @return bool
		 * @throws coreException если заданный домен не существует
		 */
		public function delDomain($id);

		/**
		 * Возвращает экземпляр домена по его идентификатору
		 * @param int $id идентификатор домена
		 * @return iDomain|bool
		 */
		public function getDomain($id);

		/**
		 * Возвращает домен по умолчанию или false, если такой домен не задан
		 * @return iDomain|bool
		 */
		public function getDefaultDomain();

		/**
		 * Установить домен по умолчанию
		 * @param int $id id домена, который нужно сделать доменом по умолчанию
		 * @return bool
		 * @throws coreException если домена с заданным id не существует
		 */
		public function setDefaultDomain($id);

		/**
		 * Возвращает идентификатор домена по его хосту
		 * @param string $host искомый хост
		 * @param bool $useMirrors искать соответствие хоста среди зеркал доменов
		 * @param bool $checkIdn преобразовывать хост в punycode
		 * @return int|bool
		 */
		public function getDomainId($host, $useMirrors = true, $checkIdn = true);

		/**
		 * Возвращает домен по его хосту
		 * @param string $host искомый хост
		 * @return iDomain|bool
		 */
		public function getDomainByHost($host);

		/**
		 * Возвращает список загруженных доменов
		 * @return iDomain[]
		 */
		public function getList();

		/**
		 * Проверяет существует ли домен с заданными идентификатором
		 * @param int $id идентификатор домена
		 * @return bool
		 */
		public function isExists($id);

		/**
		 * Определяет идентификатор домена по URL
		 * @param string $url
		 * @return bool|int результат определения
		 */
		public function getDomainIdByUrl($url);

		/** Очищает внутренний кеш класса */
		public function clearCache();

		/**
		 *  Проверяет, является ли домен доменом по умолчанию
		 * @param string $host - имя домена
		 * @return bool
		 */
		public function isDefaultDomain($host);
	}
