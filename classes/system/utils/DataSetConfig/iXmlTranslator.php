<?php
	namespace UmiCms\Classes\System\Utils\DataSetConfig;

	/**
	 * Интерфейс транслятора конфигурации данных контрола в xml формат
	 * @package UmiCms\Classes\System\Utils\DataSetConfig
	 */
	interface iXmlTranslator {

		/**
		 * Конструктор
		 * @param \iUmiObjectTypesCollection $objectTypeCollection коллекция объектных типов
		 * @param \iCmsController $cmsController cms контроллер
		 */
		public function __construct(\iUmiObjectTypesCollection $objectTypeCollection, \iCmsController $cmsController);

		/**
		 * Переводит конфигурацию данных контрола в xml формат
		 * @param array|mixed $config конфигурация данных контрола
		 * @return \DOMDocument
		 */
		public function translate($config);
	}