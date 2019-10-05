<?php

	namespace UmiCms\Classes\System\Utils\Stub\Settings;

	use UmiCms\Classes\System\Utils\Settings\iSettings as iMainSettings;

	/**
	 * Интерфейс настроек доступа к сайту
	 * @package UmiCms\Classes\System\Utils\Stub\Settings
	 */
	interface iSettings extends iMainSettings {

		/** @const string STUB_DIRECTORY директория файла страницы заглушки*/
		const STUB_DIRECTORY = '/errors';

		/** @const string FILE_NAME имя файла страницы заглушки*/
		const FILE_NAME = '/userStub';

		/** @const string FILE_EXTENSION расширение файла страницы заглушки*/
		const FILE_EXTENSION = '.html';

		/**
		 * Включена ли страница заглушка
		 * @return bool
		 */
		public function isIpStub();

		/**
		 * Изменяет состояние страницы заглушки
		 * @param bool $stub
		 * @return $this
		 */
		public function setIpStub($stub);

		/**
		 * Отключена ли индексация поисковыми системами
		 * @return bool
		 */
		public function isDisableRobotIndex();

		/**
		 * Изменяет состояние отключения индексации поисковыми системами
		 * @param bool $isDisable
		 * @return $this
		 */
		public function setDisableRobotIndex($isDisable);

		/**
		 * Возвращает содержимое файла страницы заглушки
		 * @return string
		 */
		public function getStubContent();

		/**
		 * Изменяет содержимое страницы заглушки
		 * @param string $content
		 * @return $this
		 */
		public function setStubContent($content);

		/**
		 * Возвращает список адресов для которых разрешен доступ к сайту
		 * при включенной странице заглушке
		 * @return array
		 */
		public function getWhiteList();

		/**
		 * Добавляет ip адрес в список адресов для которых разрешен доступ к сайту
		 * @param string $ip
		 * @return int|bool идентификатор объекта
		 */
		public function addToWhiteList($ip);

		/**
		 * Использовать ли черный список ip адресов
		 * @return bool
		 */
		public function isUseBlackList();

		/**
		 * Включает/отключает использование черного списка ip адресов
		 * @param bool $isUse
		 * @return $this
		 */
		public function setUseBlackList($isUse);

		/**
		 * Возвращает список ip адресов черного списка
		 * @return array
		 */
		public function getBlackList();

		/**
		 * Добавляет ip адрес в черный список
		 * @param string $ip
		 * @return int|bool идентификатор объекта
		 */
		public function addToBlackList($ip);

		/**
		 * Возвращает адрес файла страницы заглушки
		 * @return string
		 */
		public function getStubFilePath();
	}