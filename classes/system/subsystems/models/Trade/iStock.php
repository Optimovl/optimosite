<?php
	namespace UmiCms\System\Trade;

	use \iUmiObject as iDataObject;

	/**
	 * Интерфейс склада
	 * @package UmiCms\System\Trade
	 */
	interface iStock {

		/**
		 * Конструктор
		 * @param iDataObject $object объект данных
		 */
		public function __construct(iDataObject $object);

		/**
		 * Возвращает идентификатор
		 * @return int
		 */
		public function getId();

		/**
		 * Возвращает название
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает название
		 * @param string $name название
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setName($name);

		/**
		 * Определяет является ли склад основным
		 * @return bool
		 */
		public function isDefault();

		/**
		 * Устанавливает, что склад является основным
		 * @param bool $flag значение флага
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setDefault($flag = true);

		/**
		 * Возвращает объект данных
		 * @return iDataObject
		 */
		public function getDataObject();

		/**
		 * Возвращает тип отображения остатка
		 * @return string
		 */
		public function getBalanceViewType();

		/**
		 * Возвращает заголовок для остатка
		 * @return string
		 */
		public function getBalanceTitle();
	}