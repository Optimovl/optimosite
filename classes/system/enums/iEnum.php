<?php

	namespace UmiCms\Classes\System\Enums;

	/**
	 * Интерфейс перечисления
	 * @package UmiCms\Classes\System\Enums
	 */
	interface iEnum {

		/**
		 * Конструктор
		 * @param mixed $currentValue текущее значение перечисления,
		 * если не передано, то будет задействовано значение по умолчанию
		 * @throws EnumElementNotExistsException
		 */
		public function __construct($currentValue = null);

		/**
		 * Возвращает текущее значение перечисления
		 * @return string
		 */
		public function __toString();

		/**
		 * Возвращает все значение перечисления
		 *
		 * [
		 *      key => value
		 * ]
		 *
		 * @return array
		 */
		public function getAllValues();
	}
