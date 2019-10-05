<?php

	/** Абстрактный класс для генерации картинки-капчи */
	abstract class captchaDrawer {

		/** @const length длина кода */
		const length = 6;

		/** @const доступные для генерации кода символы */
		const alphabet = '23456789qwertyuipasdfghjkzxcvbnm';

		/**
		 * Генерирует картинку-капчу с переданным кодом
		 * @param string $randomCode произвольный код, который нужно изобразить на картинке капчи
		 * @return mixed
		 */
		abstract public function draw($randomCode);

		/**
		 * Генерирует произвольный код
		 * @return string
		 */
		public function getRandomCode() {
			$lastIndex = mb_strlen(self::alphabet) - 1;

			/** @var string $alphabet сохранение в локальную переменную для совместимости с PHP 5.4 */
			$alphabet = self::alphabet;
			$code = '';

			for ($i = 0; $i < self::length; $i++) {
				$code .= $alphabet[mt_rand(0, $lastIndex)];
			}

			return $code;
		}
	}
