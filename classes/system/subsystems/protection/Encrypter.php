<?php
	namespace UmiCms\System\Protection;

	/**
	 * Класс шифровальщика
	 * @package UmiCms\System\Protection
	 */
	class Encrypter implements iEncrypter {

		/** @var \iConfiguration $configuration */
		private $configuration;

		/** @inheritdoc */
		public function __construct(\iConfiguration $configuration) {
			$this->setConfiguration($configuration);
		}

		/** @inheritdoc */
		public function encrypt($string) {
			if (!is_string($string) || empty($string)) {
				throw new \ErrorException('Incorrect string for encryption given');
			}

			return base64_encode($this->encryptString($string));
		}

		/** @inheritdoc */
		public function decrypt($encryptedString) {
			if (!is_string($encryptedString) || empty($encryptedString)) {
				throw new \ErrorException('Incorrect string for decryption given');
			}

			return $this->encryptString(base64_decode($encryptedString));
		}

		/**
		 * Шифрует строку
		 * @param string $string строка
		 * @return mixed
		 */
		private function encryptString($string) {
			$password = $this->getPassword();
			$salt = $this->getSalt();
			$stringLength = strlen($string);
			$seq = $password;
			$gamma = '';

			while (strlen($gamma) < $stringLength) {
				$seq = sha1($seq . $salt, true);
				$gamma .= substr($seq, 0, 8);
			}

			return $string ^ $gamma;
		}

		/**
		 * Возвращает пароль
		 * @return string
		 */
		private function getPassword() {
			return 'MakeUmiGreatAgain!';
		}

		/**
		 * Возвращает соль
		 * @return string
		 */
		private function getSalt() {
			return (string) $this->getConfiguration()
				->get('system', 'salt');
		}

		/**
		 * Устанавливает конфигурацию
		 * @param \iConfiguration $configuration конфигурация
		 * @return $this
		 */
		private function setConfiguration(\iConfiguration $configuration) {
			$this->configuration = $configuration;
			return $this;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}
	}