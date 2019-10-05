<?php

	namespace UmiCms\Classes\System\Utils\Idn;

	/**
	 * Интерфейс конвертера Idn (Internationalized Domain Names).
	 * Используется для перевода из/в punycode кириллических доменов.
	 * @package UmiCms\Classes\System\Utils\Idn
	 */
	interface iConverter {

		/**
		 * Преобразует строку из Idn в заданную кодироку
		 * @param string $input преобразуемая строка
		 * @param string|bool $encoding кодировка или false (задействовать кодировку по умолчанию (utf-8))
		 * @return string|bool строка или false (произошла ошибка)
		 */
		public function decode($input, $encoding = false);

		/**
		 * Преобразует строку из заданной кодировки в Idn
		 * @param string $input преобразуемая строка
		 * @param string|bool $encoding кодировка или false (задействовать кодировку по умолчанию (utf-8))
		 * @return string|bool строка или false (произошла ошибка)
		 */
		public function encode($input, $encoding = false);

		/**
		 * Преобразует строку из Idn или в Idn, если необходимо
		 * @param string $input преобразуемая строка
		 * @return string|bool строка или false (произошла ошибка)
		 */
		public function convert($input);
	}
