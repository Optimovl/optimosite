<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency\Favorite;

	use \iUmiObject as iObject;
	use UmiCms\System\Auth\iAuth;
	use \iUmiObjectsCollection as iObjectCollection;
	use UmiCms\System\Trade\Offer\Price\Currency\Favorite as AbstractFavorite;

	/**
	 * Класс любимой валюты пользователя
	 * @package UmiCms\System\Trade\Offer\Price\Currency\Favorite
	 */
	class User extends AbstractFavorite implements iUser {

		/** @var iAuth $auth фасад авторизации и аутентификации */
		private $auth;

		/** @var iObjectCollection $objectCollection коллекция объектов */
		private $objectCollection;

		/** @inheritdoc */
		public function __construct(iAuth $auth, iObjectCollection $objectsCollection) {
			$this->setAuth($auth)
				->setObjectCollection($objectsCollection);
		}

		/**
		 * @inheritdoc
		 * @return int|null
		 */
		public function getId() {
			try {
				$id = (int) $this->getUser()
					->getValue(self::FIELD_NAME);

				if ($id !== 0) {
					return $id;
				}

			} catch (\ErrorException $exception) {
				//nothing
			}

			return null;
		}

		/**
		 * @inheritdoc
		 * @param int $id
		 * @return bool
		 */
		public function setId($id) {
			try {

				if ($this->getAuth()->isAuthorized()) {
					$user = $this->getUser();
					$user->setValue(self::FIELD_NAME, $id);
					$user->commit();
					return true;
				}

			} catch (\ErrorException $exception) {
				//nothing
			}

			return false;
		}

		/**
		 * Возвращает объект текущего пользователя
		 * @return iObject
		 * @throws \ErrorException
		 */
		private function getUser() {
			$id = $this->getAuth()
				->getUserId();
			$user = $this->getObjectCollection()
				->getObject($id);

			if (!$user instanceof iObject) {
				throw new \ErrorException('Cannot get current user');
			}

			return $user;
		}

		/**
		 * Устанавливает фасад авторизации и аутентификации
		 * @param iAuth $auth фасад авторизации и аутентификации
		 * @return $this
		 */
		private function setAuth(iAuth $auth) {
			$this->auth = $auth;
			return $this;
		}

		/**
		 * Возвращает фасад авторизации и аутентификации
		 * @return iAuth
		 */
		private function getAuth() {
			return $this->auth;
		}

		/**
		 * Устанавливает коллекцию объектов
		 * @param iObjectCollection $objectsCollection коллекция объектов
		 * @return $this
		 */
		private function setObjectCollection(iObjectCollection $objectsCollection) {
			$this->objectCollection = $objectsCollection;
			return $this;
		}

		/**
		 * Возвращает коллекцию объектов
		 * @return iObjectCollection
		 */
		private function getObjectCollection() {
			return $this->objectCollection;
		}
	}