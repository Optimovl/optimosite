<?php

	namespace UmiCms\System\MailNotification;

	use UmiCms\System\Hierarchy\Domain\iDetector as DomainDetector;
	use UmiCms\System\Hierarchy\Language\iDetector as LanguageDetector;
	use UmiCms\System\iMailNotification;

	/**
	 * Интерфейс коллекции уведомлений
	 * @package UmiCms\System\MailNotification
	 */
	interface iCollection extends \iUmiCollection {

		/**
		 * Возвращает уведомление по его имени
		 * @param string $name
		 * @return iMailNotification|null
		 */
		public function getByName($name);

		/**
		 * Возвращает уведомление по его модулю, в котором оно используется
		 * @param string $module
		 * @return iMailNotification|null
		 */
		public function getByModule($module);

		/**
		 * Возвращает уведомление для текущего языка/домена по его имени.
		 * Если такое уведомление не найдено - пытается вернуть уведомление для языка/домена по умолчанию.
		 * @param string $name название уведомления
		 * @return iMailNotification|null
		 */
		public function getCurrentByName($name);

		/**
		 * Устанавливает определитель домена
		 * @param DomainDetector $detector определитель домена
		 * @return $this
		 */
		public function setDomainDetector(DomainDetector $detector);

		/**
		 * Устанавливает определитель языка
		 * @param LanguageDetector $detector определитель языка
		 * @return $this
		 */
		public function setLanguageDetector(LanguageDetector $detector);
	}
