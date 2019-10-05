<?php
	namespace UmiCms\System\Protection;

	/**
	 * Интерфейс шифровальщика
	 * @package UmiCms\System\Protection
	 */
	interface iEncrypter {

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(\iConfiguration $configuration);

		/**
		 * Шифрует строку
		 * @param string $string строка
		 * @return string
		 * @throws \ErrorException
		 */
		public function encrypt($string);

		/**
		 * Дешифрует строку
		 * @param string $encryptedString строка
		 * @return string
		 * @throws \ErrorException
		 */
		public function decrypt($encryptedString);
	}