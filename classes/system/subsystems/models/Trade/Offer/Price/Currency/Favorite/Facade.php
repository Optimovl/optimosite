<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency\Favorite;

	use UmiCms\System\Trade\Offer\Price\Currency\Favorite as AbstractFavorite;

	/**
	 * Класс фасада любимых валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency\Favorite
	 */
	class Facade extends AbstractFavorite implements iFacade {

		/** @var iUser $userFavoriteCurrency любимая валюта пользователя */
		private $userFavoriteCurrency;

		/** @var iCustomer $customerFavoriteCurrency любимая валюта покупателя */
		private $customerFavoriteCurrency;

		/** @inheritdoc */
		public function __construct(iUser $userFavoriteCurrency, iCustomer $customerFavoriteCurrency) {
			$this->setUserFavoriteCurrency($userFavoriteCurrency)
				->setCustomerFavoriteCurrency($customerFavoriteCurrency);
		}

		/** @inheritdoc */
		public function getId() {
			return $this->getCustomerFavoriteCurrency()->getId() ?: $this->getUserFavoriteCurrency()->getId();
		}

		/** @inheritdoc */
		public function setId($id)  {
			return $this->getUserFavoriteCurrency()->setId($id) ?: $this->getCustomerFavoriteCurrency()->setId($id);
		}

		/**
		 * Устанавливает любимую валюту пользоватея
		 * @param iUser $userFavoriteCurrency любимая валюта пользователя
		 * @return $this
		 */
		private function setUserFavoriteCurrency(iUser $userFavoriteCurrency) {
			$this->userFavoriteCurrency = $userFavoriteCurrency;
			return $this;
		}

		/**
		 * Возвращает любимую валюту пользователя
		 * @return iUser
		 */
		private function getUserFavoriteCurrency() {
			return $this->userFavoriteCurrency;
		}

		/**
		 * Устанавливает любимую валюту покупателя
		 * @param iCustomer $customerFavoriteCurrency любимая валюта покупателя
		 * @return $this
		 */
		private function setCustomerFavoriteCurrency(iCustomer $customerFavoriteCurrency) {
			$this->customerFavoriteCurrency = $customerFavoriteCurrency;
			return $this;
		}

		/**
		 * Возвращает любимую валюту покупателя
		 * @return iCustomer
		 */
		private function getCustomerFavoriteCurrency() {
			return $this->customerFavoriteCurrency;
		}
	}