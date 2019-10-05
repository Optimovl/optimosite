<?php
	namespace UmiCms\System\Utils\Emoji;

	/**
	 * Интерфейс переводчика эмодзи
	 * @package UmiCms\System\Utils\Emoji
	 */
	interface iTranslator {

		/**
		 * Переводит эмодзи из юникода в краткое имя
		 * @example 😈 => :smiling_imp:
		 * @param string $unicode юникод
		 * @return string
		 */
		public function fromUnicodeToShortName($unicode);

		/**
		 * Переводит краткое имя эмодзи в его юникод
		 * @example :smiling_imp: => 😈
		 * @param string $shortName краткое имя
		 * @return string
		 */
		public function fromShortNameToUnicode($shortName);
	}