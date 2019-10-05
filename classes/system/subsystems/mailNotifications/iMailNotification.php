<?php

	namespace UmiCms\System;

	/**
	 * Интерфейс уведомления
	 * @package UmiCms\System
	 */
	interface iMailNotification extends \iUmiCollectionItem {

		/**
		 * Возвращает идентификатор языка
		 * @return int
		 */
		public function getLangId();

		/**
		 * Устанавливает идентификатор языка
		 * @param int $id новый ID
		 */
		public function setLangId($id);

		/**
		 * Возвращает идентификатор домена
		 * @return int
		 */
		public function getDomainId();

		/**
		 * Устанавливает идентификатор домена
		 * @param int $id новый ID
		 */
		public function setDomainId($id);

		/**
		 * Возвращает имя
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает имя
		 * @param string $name новое имя
		 */
		public function setName($name);

		/**
		 * Возвращает модуль
		 * @return string
		 */
		public function getModule();

		/**
		 * Устанавливает модуль, в котором используется уведомление
		 * @param string $module
		 */
		public function setModule($module);

		/**
		 * Возвращает шаблоны, которыми пользуется уведомление
		 * @return \MailTemplate[]
		 */
		public function getTemplates();

		/**
		 * Возвращает шаблон уведомления по его названию
		 * @param string $name название шаблона
		 * @return \MailTemplate|null
		 */
		public function getTemplateByName($name);
	}
