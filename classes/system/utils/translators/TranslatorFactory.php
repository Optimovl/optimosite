<?php

	namespace UmiCms\Classes\System\Translators;

	/**
	 * Фабрика UMI-трансляторов
	 * В данный момент классы трансляторов не могут иметь один интерфейс,
	 * так как у них отличаются методы непосредственной трансляции данных.
	 * Class TranslatorFactory
	 * @package UmiCms\Classes\System\Translators
	 */
	class TranslatorFactory {

		/** @const класс транслятора данных в JSON формат */
		const JSON = 'jsonTranslator';

		/** @const класс транслятора данных в XML формат */
		const XML = 'xmlTranslator';

		/** @const класс транслятора данных в PHP формат */
		const PHP = 'UmiCms\Classes\System\Translators\PhpTranslator';

		/**
		 * Создает транслятор указанного типа
		 * @param string $class имя класса создаваемого транслятора
		 * @return \jsonTranslator|\xmlTranslator|PhpTranslator
		 * @throws \ErrorException если класс с переданным именем не существует
		 */
		public static function create($class) {
			if (!class_exists($class)) {
				throw new \ErrorException(sprintf('Translator class %s does not exist', $class));
			}

			return self::createInstance($class);
		}

		/**
		 * Создает экземпляр класса транслятора
		 * @param string $class имя класса создаваемого экземпляра
		 * @return \jsonTranslator|\xmlTranslator|PhpTranslator
		 */
		private static function createInstance($class) {
			return new $class();
		}
	}
