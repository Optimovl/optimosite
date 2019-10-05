<?php

	namespace UmiCms\System\Cache\Key;

	/**
	 * Абстрактный класс валидатора ключей кеша
	 * @package UmiCms\System\Cache\Key
	 */
	abstract class Validator implements iValidator {

		/** @var \iConfiguration $configuration конфигурация системы */
		private $configuration;

		/** @inheritdoc */
		public function __construct(\iConfiguration $configuration) {
			$this->configuration = $configuration;
		}

		/** @inheritdoc */
		abstract public function isValid($key);

		/**
		 * Определяет вхождение ключа в черный список
		 * @param string $key ключ кеша
		 * @return bool
		 */
		protected function isOnBlackList($key) {
			return $this->isKeyOnList($this->getBlackList(), $key);
		}

		/**
		 * Определяет вхождение ключа в белый список
		 * @param string $key ключ кеша
		 * @return bool
		 */
		protected function isOnWhiteList($key) {
			return $this->isKeyOnList($this->getWhiteList(), $key);
		}

		/**
		 * Определяет вхождение ключа в список
		 * @param array $list список
		 * @param string $key ключ кеша
		 * @return bool
		 */
		private function isKeyOnList(array $list, $key) {
			foreach ($list as $entry) {
				if (is_string($entry) && contains($key, $entry)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Возвращает черный список.
		 * Параметры конфигурации 'not-allowed-methods' и 'not-allowed-streams' использовались ранее,
		 * они оставлены для обратной совместимости.
		 * @return array
		 */
		private function getBlackList() {
			$config = $this->getConfiguration();
			$methodBlackList = (array) $config->get('cache', 'not-allowed-methods');
			$streamBlackList = (array) $config->get('cache', 'not-allowed-streams');
			$commonBlackList = (array) $config->get('cache', 'blacklist');
			$blacklist = array_merge($methodBlackList, $streamBlackList, $commonBlackList);
			$blacklist = array_unique($blacklist);
			return $this->filterList($blacklist);
		}

		/**
		 * Возвращает белый список
		 * @return array
		 */
		private function getWhiteList() {
			$whiteList = (array) $this->getConfiguration()
				->get('cache', 'whitelist');
			$whiteList = array_unique($whiteList);
			return $this->filterList($whiteList);
		}

		/**
		 * Фильтрует список от некорректных значений
		 * @param array $list список
		 * @return array
		 */
		private function filterList(array $list) {
			return array_filter($list, function ($value) {
				if (!is_string($value)) {
					return false;
				}

				$trimmedValue = trim($value);
				return mb_strlen($trimmedValue) > 0;
			});
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}
	}
