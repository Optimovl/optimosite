<?php

	namespace UmiCms\System\Permissions;

	/**
	 * Интерфейс класса прав системных пользователей
	 * @package UmiCms\System\Permissions
	 */
	interface iSystemUsersPermissions {

		/** @const string SV_USER_GUID гуид супервайзера */
		const SV_USER_GUID = 'system-supervisor';

		/** @const string SV_GROUP_GUID гуид группы супервайзеров */
		const SV_GROUP_GUID = 'users-users-15';

		/** @const string GUEST_USER_GUID гуид гостя */
		const GUEST_USER_GUID = 'system-guest';

		/** @const string REGISTERED_GROUP_GUID гуид группы зарегистрированных пользователей */
		const REGISTERED_GROUP_GUID = 'users-users-2374';

		/**
		 * Конструктор
		 * @param \iUmiObjectsCollection $umiObjects коллекция объектов
		 */
		public function __construct(\iUmiObjectsCollection $umiObjects);

		/**
		 * Возвращает идентификатор супервайзера
		 * @return int
		 */
		public function getSvUserId();

		/**
		 * Возвращает идентификатор группы супервайзеров
		 * @return int
		 */
		public function getSvGroupId();

		/**
		 * Возвращает идентификатор гостя
		 * @return int
		 */
		public function getGuestUserId();

		/**
		 * Возвращает идентификатор группы зарегистрированных пользователей
		 * @return int
		 */
		public function getRegisteredGroupId();

		/**
		 * Возвращает список идентификаторов системных пользователей
		 * @return int[]
		 */
		public function getIdList();
	}
