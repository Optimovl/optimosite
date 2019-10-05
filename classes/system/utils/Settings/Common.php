<?php

	namespace UmiCms\Classes\System\Utils\Settings;

	/**
	 * Абстрактный класс настроек, общих для всех сайтов
	 * @package UmiCms\Classes\System\Utils\Settings
	 */
	abstract class Common implements iSettings, \iUmiRegistryInjector  {

		use \tUmiRegistryInjector;

		/**
		 * Конструктор
		 * @param \iRegedit $registry класс регистра
		 */
		public function __construct(\iRegedit $registry) {
			$this->setRegistry($registry);
		}
	}