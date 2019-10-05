<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	/**
	 * Интерфейс любимой валюты
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	interface iFavorite {

		/**
		 * Возвращает идентификатор валюты
		 * @return int|null
		 */
		public function getId();

		/**
		 * Устанавливает идентификатор валюты
		 * @param int $id идентификатор
		 * @return bool
		 */
		public function setId($id);
	}