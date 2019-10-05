<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Интерфейс коллекции сущностей ссылок
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	interface iCollection extends \iUmiCollection {

		/** @const string DEFAULT_RESULT_ITEMS_LIMIT ограничение количество результирующих элементов по умолчанию */
		const DEFAULT_RESULT_ITEMS_LIMIT = 20;

		/**
		 * Создает ссылку по ее адресу и месту
		 * @param string $address адрес ссылки (url)
		 * @param string $place место ссылки (url страницы, где она была найдена)
		 * @return iEntity
		 */
		public function createByAddressAndPlace($address, $place);

		/**
		 * Возвращает работоспособные ссылки
		 * @param int $offset смещение выборки
		 * @param int $limit ограничение на количество выборки
		 * @return iEntity[]
		 */
		public function getCorrectLinks($offset = 0, $limit = self::DEFAULT_RESULT_ITEMS_LIMIT);

		/**
		 * Возвращает ссылку по ее адресу
		 * @param string $address адрес ссылки (url)
		 * @return iEntity|null
		 */
		public function getByAddress($address);

		/**
		 * Экспортирует неработоспособные ссылки
		 * @param int $offset смещение выборки
		 * @param int $limit ограничение на количество выборки
		 * @return array
		 */
		public function exportBrokenLinks($offset = 0, $limit = self::DEFAULT_RESULT_ITEMS_LIMIT);

		/**
		 * Возвращает количество неработоспособных ссылок
		 * @return int
		 */
		public function countBrokenLinks();
	}
