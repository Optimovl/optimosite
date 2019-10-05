<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Interface iEntity интерфейс сущности ссылки
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	interface iEntity extends \iUmiCollectionItem {

		/**
		 * Устанавливает адрес ссылки (ее url)
		 * @param string $address адрес ссылки
		 * @return iEntity
		 */
		public function setAddress($address);

		/**
		 * Возвращает адрес ссылки (ее url)
		 * @return string
		 */
		public function getAddress();

		/**
		 * Устанавливает хеш адреса ссылки
		 * @param string $addressHash хеш адрес ссылки
		 * @return iEntity
		 */
		public function setAddressHash($addressHash);

		/**
		 * Возвращает хеш адрес ссылки
		 * @return string
		 */
		public function getAddressHash();

		/**
		 * Устанавливает место ссылки (url страницы, где она была найдена)
		 * @param string $place место ссылки
		 * @return iEntity
		 */
		public function setPlace($place);

		/**
		 * Возвращает место ссылки
		 * @return string
		 */
		public function getPlace();

		/**
		 * Устанавливает статус нерабоспособности
		 * @param bool $status true - не работает, false - работает
		 * @return iEntity
		 */
		public function setBroken($status);

		/**
		 * Возвращает статус нерабоспособности
		 * @return bool
		 */
		public function getBroken();
	}
