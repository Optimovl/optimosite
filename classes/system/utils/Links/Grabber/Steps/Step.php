<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber\Steps;

	/**
	 * Class Step шаг сбора ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Grabber\Steps
	 */
	abstract class Step implements iStep {

		/** @var bool $isComplete завершен ли шаг */
		protected $isComplete;

		/** @var array $result результат итерации сбора шага */
		protected $result;

		/** @const string HTML_LINK_PATTERN регулярное выражение поиска значений атрибута href тега <a> в тексте */
		const HTML_LINK_PATTERN = '/<a(?!>).*?href\s{0,}=\s{0,}(".*?")/';

		/** @const string URL_HOST_PATTERN регулярное выражение валидации хоста URL */
		const URL_HOST_PATTERN = '/^(([a-z0-9-_]+\.){1,}([a-z]+){1}[\/]{0,})$/';

		/** @const string COMPLETE_KEY ключ статуса завершенности */
		const COMPLETE_KEY = 'complete';

		/** @const string URL_HOST_KEY ключ части url "host" */
		const URL_HOST_KEY = 'host';

		/** @const string URL_PATH_KEY ключ части url "path" */
		const URL_PATH_KEY = 'path';

		/** @inheritdoc */
		abstract public function getName();

		/** @inheritdoc */
		abstract public function setState(array $state);

		/** @inheritdoc */
		abstract public function grab();

		/** @inheritdoc */
		abstract public function getState();

		/** @inheritdoc */
		abstract public function getStartStateStructure();

		/** @inheritdoc */
		public function isComplete() {
			if ($this->isComplete === null) {
				throw new \RequiredPropertyHasNoValueException('You should set is complete status first');
			}

			return $this->isComplete;
		}

		/** @inheritdoc */
		public function getResult() {
			if ($this->result === null) {
				throw new \RequiredPropertyHasNoValueException('You should grab first');
			}

			return $this->result;
		}

		/**
		 * Устанавливает статус завершенности шага сбора
		 * @param bool $completeStatus статус завершенности
		 * @return $this
		 * @throws \wrongParamException
		 */
		protected function setCompleteStatus($completeStatus) {
			if (!is_bool($completeStatus)) {
				throw new \wrongParamException('Wrong complete status given');
			}
			$this->isComplete = $completeStatus;
			return $this;
		}

		/**
		 * Устанавливает результат работы одной итерации шага
		 * @param array $result результат работы
		 * @return $this
		 */
		protected function setResult(array $result) {
			$this->result = $result;
			return $this;
		}

		/**
		 * Разбирает строку и возвращает список url, которые в нее входят.
		 * Считает, что строка скорее представляет собой отдельный url, а а не html с ссылками.
		 * @param string $string разбираемая строка
		 * @return array
		 */
		protected function parseUrlsFromString($string) {
			if ($this->isLink($string)) {
				return [$string];
			}

			return $this->getLinksFromHtmlText($string);
		}

		/**
		 * Разбирает строку и возвращает список url, которые в нее входят.
		 * Считает, что строка скорее представляет собой html с ссылками, а не отдельный url.
		 * @param string $text разбираемая строка
		 * @return array
		 */
		protected function parseUrlsFromText($text) {
			$linksFromHtml = $this->getLinksFromHtmlText($text);

			if (umiCount($linksFromHtml) > 0) {
				return $linksFromHtml;
			}

			if ($this->isLink($text)) {
				return [$text];
			}

			return [];
		}

		/**
		 * Определяет является ли строка ссылкой
		 * @param string $string строка
		 * @return bool
		 */
		protected function isLink($string) {
			$parsedUrl = parse_url($string);

			if ($parsedUrl === false) {
				return false;
			}

			$urlComponentsCount = umiCount($parsedUrl);
			$hostUrlPartParsed = isset($parsedUrl[self::URL_HOST_KEY]);
			$pathUrlPartParsed = isset($parsedUrl[self::URL_PATH_KEY]);

			if ($urlComponentsCount > 0 && !($hostUrlPartParsed || $pathUrlPartParsed)) {
				return true;
			}

			if ($hostUrlPartParsed && preg_match(self::URL_HOST_PATTERN, $parsedUrl[self::URL_HOST_KEY])) {
				return true;
			}

			if (preg_match(self::URL_HOST_PATTERN, $string)) {
				return true;
			}

			return false;
		}

		/**
		 * Возвращает список ссылок из html текста
		 * @param string $html html текст
		 * @return array
		 */
		protected function getLinksFromHtmlText($html) {
			$matches = [];
			preg_match_all(self::HTML_LINK_PATTERN, $html, $matches);

			if (!isset($matches[1]) || !is_array($matches[1]) || umiCount($matches[1]) == 0) {
				return [];
			}

			return array_map(
				function ($link) {
					return trim($link, '"');
				},
				$matches[1]
			);
		}
	}
