<?php

	namespace UmiCms\System\Auth\PasswordHash;

	/**
	 * Интерфейс алгоритма хеширования паролей
	 * @package UmiCms\System\Auth\PasswordHash
	 */
	interface iAlgorithm {

		/** Алгоритм хеширования пароля SHA256 */
		const SHA256 = 0;

		/** Алгоритм хеширования пароля md5 */
		const MD5 = 1;

		/** Соль для хеширования пароля */
		const HASH_SALT = 'o95j43hiwjrthpoiwj45ihwpriobneop;jfgp3408ghqpqh5gpqoi4hgp9q85h';

		/**
		 * Выполняет хеширование пароля
		 * @param string $password Пароль для шифрования
		 * @param int $algorithm Алгоритм шифрования пароля self::SHA256|self::MD5
		 * @return string хешированная строка
		 * @throws WrongAlgorithmException
		 */
		public static function hash($password, $algorithm = self::SHA256);

		/**
		 * Проверяет, используется ли для пароля алгоритм хеширования md5
		 * @param string $hashedPassword Хеш пароля
		 * @param string $rawPassword Пароль пользователя в открытом виде
		 * @return bool результат проверки
		 */
		public static function isHashedWithMd5($hashedPassword, $rawPassword);
	}
