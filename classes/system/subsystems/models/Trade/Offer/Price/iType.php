<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\iEntity;

	/**
	 * Интерфейс типа цены
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	interface iType extends iEntity {

		/**
		 * Возвращает название
		 * @return string|null
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
		 * Возвращает заголовок
		 * @return string|null
		 */
		public function getTitle();

		/**
		 * Устанавливает заголовок
		 * @param string $title заголовок
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setTitle($title);

		/**
		 * Определяет является ли группа группой по умолчанию
		 * @return bool
		 */
		public function isDefault();

		/**
		 * Устанавливает, что группа является группой по умолчанию
		 * @param bool $flag значение флага
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setDefault($flag = true);

		/**
		 * Возвращает тип отображения цены
		 * @return string
		 */
		public function getPriceViewType();

		/**
		 * Возвращает заголовок цены
		 * @return string
		 */
		public function getPriceTitle();
	}