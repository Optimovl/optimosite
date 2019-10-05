<?php

	/** Работа с транслитом */
	class translit implements iTranslit {

		public static $fromUpper = [
			'Э',
			'Ч',
			'Ш',
			'Ё',
			'Ё',
			'Ж',
			'Ю',
			'Ю',
			'Я',
			'Я',
			'А',
			'Б',
			'В',
			'Г',
			'Д',
			'Е',
			'З',
			'И',
			'Й',
			'К',
			'Л',
			'М',
			'Н',
			'О',
			'П',
			'Р',
			'С',
			'Т',
			'У',
			'Ф',
			'Х',
			'Ц',
			'Щ',
			'Ъ',
			'Ы',
			'Ь'
		];

		public static $fromLower = [
			'э',
			'ч',
			'ш',
			'ё',
			'ё',
			'ж',
			'ю',
			'ю',
			'я',
			'я',
			'а',
			'б',
			'в',
			'г',
			'д',
			'е',
			'з',
			'и',
			'й',
			'к',
			'л',
			'м',
			'н',
			'о',
			'п',
			'р',
			'с',
			'т',
			'у',
			'ф',
			'х',
			'ц',
			'щ',
			'ъ',
			'ы',
			'ь'
		];

		public static $toLower = [
			'e',
			'ch',
			'sh',
			'yo',
			'jo',
			'zh',
			'yu',
			'ju',
			'ya',
			'ja',
			'a',
			'b',
			'v',
			'g',
			'd',
			'e',
			'z',
			'i',
			'j',
			'k',
			'l',
			'm',
			'n',
			'o',
			'p',
			'r',
			's',
			't',
			'u',
			'f',
			'h',
			'c',
			'w',
			'~',
			'y',
			"\'"
		];

		/**
		 * Конвертировать строку в транслит
		 * @param string $str входная строка
		 * @param string $separator заменитель невалидных символов
		 *
		 * @return string транслитерированная строка
		 */
		public static function convert($str, $separator = '_') {
			$separator = $separator ? addcslashes($separator, ']/\$') : '_';

			$str = umiObjectProperty::filterInputString($str);
			$str = str_replace(self::$fromLower, self::$toLower, $str);
			$str = str_replace(self::$fromUpper, self::$toLower, $str);
			$str = mb_strtolower($str);

			$str = preg_replace("/([^A-z0-9_\-]+)/", $separator, $str);
			$str = preg_replace("/[\/\\\',\t`\^\[\]]*/", '', $str);
			$str = str_replace(chr(8470), '', $str);
			$str = preg_replace("/[ \.]+/", $separator, $str);

			$str = preg_replace('/([' . $separator . ']+)/', $separator, $str);
			$str = trim(trim($str), $separator);

			return $str;
		}
	}

