<?php

	namespace UmiCms\System\Auth\PasswordHash;

	/**
	 * Класс алгоритма хеширования паролей
	 * @package UmiCms\System\Auth\PasswordHash
	 */
	class Algorithm implements iAlgorithm {

		/** @inheritdoc */
		public static function hash($password, $algorithm = self::SHA256) {
			switch ($algorithm) {
				case self::MD5:
					return md5($password);
				case self::SHA256:
					$salted = $password . self::HASH_SALT;
					return hash('sha256', $salted);
				default:
					throw new WrongAlgorithmException("Unknown hash algorithm: {$algorithm}");
			}
		}

		/** @inheritdoc */
		public static function isHashedWithMd5($hashedPassword, $rawPassword) {
			$hashLength = mb_strlen(self::hash($rawPassword, self::MD5));
			return ($hashLength == mb_strlen($hashedPassword));
		}
	}
