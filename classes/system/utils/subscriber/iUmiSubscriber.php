<?php
	/** Интерфейс подписчика на рассылку */
	interface iUmiSubscriber {

		/**
		 * Определяет является ли подписчик зарегистрированным пользователем?
		 * @return bool
		 */
		public function isRegisteredUser();

		/**
		 * Возвращает список идентификаторов рассылок, на которые подписан подписчик
		 * @return int[]
		 */
		public function getDispatches();

		/**
		 * Определяет был ли выпуск рассылки отправлен подписчику
		 * @param int $id идентификатор выпуска
		 * @return bool
		 */
		public function releaseWasSent($id);

		/**
		 * Возвращает список идентификаторов выпусков рассылок, отправленных подписчику
		 * @return int[]
		 */
		public function getSentReleaseIdList();

		/**
		 * Помещает выпуск рассылки в список отправленных подписчику
		 * @param int $id идентификатор выпуска
		 * @return $this
		 */
		public function putReleaseToSentList($id);

		/**
		 * Возвращает полное имя подписчика
		 * @return string
		 */
		public function getFullName();

		/**
		 * Возвращает почтовый ящик подписчика
		 * @return string
		 */
		public function getEmail();

		/**
		 * Возвращает идентификатор подписчика по идентификатору пользователя.
		 * Если пользователя нет, он будет создан.
		 * @param int $userId идентификатор пользователя
		 * @return int
		 * @throws selectorException
		 */
		public static function getSubscriberByUserId($userId);
	}
