<?php

	namespace UmiCms\Mail\Engine;

	use UmiCms\Mail;

	/**
	 * Класс средства отправки писем через php функциию mail()
	 * @package UmiCms\Mail\Engine
	 */
	class phpMail extends Mail\Engine {

		/** @inheritdoc */
		public function send($address) {
			return mail($address, $this->getSubject(), $this->getMessage(), $this->getHeaders(), $this->getParameters());
		}
	}
