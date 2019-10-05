<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency\Favorite;

	use UmiCms\System\Cookies\iCookieJar;
	use UmiCms\System\Trade\Offer\Price\Currency\Favorite as AbstractFavorite;

	/**
	 * Класс любимой валюты покупателя
	 * @package UmiCms\System\Trade\Offer\Price\Currency\Favorite
	 */
	class Customer extends AbstractFavorite implements iCustomer {

		/** @var iCookieJar $cookieJar */
		private $cookieJar;

		/** @inheritdoc */
		public function __construct(iCookieJar $cookieJar) {
			$this->setCookieJar($cookieJar);
		}

		/** @inheritdoc */
		public function getId() {
			$id = (int) $this->getCookieJar()->get(self::COOKIE_NAME);

			if ($id !== 0) {
				return $id;
			}

			return null;
		}

		/** @inheritdoc */
		public function setId($id) {
			try {
				$this->getCookieJar()->set(self::COOKIE_NAME, $id);
				return true;
			} catch (\wrongParamException $exception) {
				//nothing
			}

			return false;
		}

		/**
		 * Устанавливает фасад кук
		 * @param iCookieJar $cookieJar фасад кук
		 * @return $this
		 */
		private function setCookieJar(iCookieJar $cookieJar) {
			$this->cookieJar = $cookieJar;
			return $this;
		}

		/**
		 * Возвращает фасад кук
		 * @return iCookieJar
		 */
		private function getCookieJar() {
			return $this->cookieJar;
		}
	}