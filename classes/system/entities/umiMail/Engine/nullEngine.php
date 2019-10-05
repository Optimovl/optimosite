<?php

	namespace UmiCms\Mail\Engine;

	use UmiCms\Mail;

	/**
	 * Класс заглушки средства отправки писем
	 * @package UmiCms\Mail\Engine
	 */
	class nullEngine extends Mail\Engine {

		/** @inheritdoc */
		public function send($mail) {
			return true;
		}
	}
