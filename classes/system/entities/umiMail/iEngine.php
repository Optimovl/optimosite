<?php

	namespace UmiCms\Mail;

	/**
	 * Интерфейс средства отправки писем
	 * @package UmiCms\Mail
	 */
	interface iEngine {

		/**
		 * Устанавливает тему письма
		 * @param string $subject тема письма
		 * @return iEngine
		 */
		public function setSubject($subject);

		/**
		 * Устанавливает сообщение письма
		 * @param string $message сообщение письма
		 * @return iEngine
		 */
		public function setMessage($message);

		/**
		 * Устанавливает заголовки письма
		 * @param string $headers заголовки письма
		 * @return iEngine
		 */
		public function setHeaders($headers);

		/**
		 * Устанавливает параметры отправки
		 * @param string $parameters параметры отправки
		 * @return iEngine
		 */
		public function setParameters($parameters);

		/**
		 * Отправляет письмо
		 * @param string $address адрес в формате: имя <почтовый ящик>
		 * @return bool было ли письмо отправлено
		 */
		public function send($address);
	}
