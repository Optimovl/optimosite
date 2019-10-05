<?php

	namespace UmiCms\Classes\System\Utils\Links\Checker;

	use UmiCms\Classes\System\Utils\Links\iEntity;
	use UmiCms\Classes\System\Utils\Links\Injectors;

	/**
	 * Class Checker проверщик сущностей ссылок.
	 * Умеет проверять ссылки на предмет того, что по ним отдается корректный ответ сервера.
	 * Для работы классы необходима библиотека cURL.
	 * @example:
	 *
	 * $checker = new UmiCms\Classes\System\Utils\Links\Checker\Checker('linksChecker');
	 * $checker->setRegistry(regedit::getInstance());
	 * $checker->flushSavedState();
	 *
	 * while (!$checker->checkBrokenUrls()) {
	 *        $checker->grab();
	 *    }
	 *
	 * $checker->saveState();
	 *
	 * @package UmiCms\Classes\System\Utils\Links\Checker
	 */
	class Checker implements iChecker, \iUmiService, \iUmiRegistryInjector, Injectors\iLinksCollection {

		use \tUmiService;
		use \tUmiRegistryInjector;
		use Injectors\tLinksCollection;

		/** @var iState $state состояние проверки */
		private $state;

		/** @const string REGISTRY_KEY ключ реестра, где сохраняется состояние */
		const REGISTRY_KEY = '/settings/umiLinksChecker';

		/** @const string OK_HTTP_STATUS_CODE код статуса корректного ответа веб сервера */
		const OK_HTTP_STATUS_CODE = 200;

		/** @const string USER_AGENT user agent, который используется в http запросах */
		const USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:48.0) Gecko/20100101 Firefox/48.0';

		/** @const string HOST_WITH_PROTOCOL_PATTERN регулярное выражение для получения протокола с хостом */
		const HOST_WITH_PROTOCOL_PATTERN = '/(https?:\/\/)([a-z0-9-_]+\.){1,}([a-z]+){1}/';

		/** @const string HOST_PATTERN регулярное выражение для получения хоста */
		const HOST_PATTERN = '/([a-z0-9-_]+\.){1,}([a-z]+){1}';

		/** @const string URL_PROTOCOL_START_SYMBOLS стартовые символы, которые обозначают протокол в URL */
		const URL_PROTOCOL_START_SYMBOLS = 'http';

		/** @const string URL_DEFAULT_PROTOCOL HTTP протокол по умолчанию */
		const URL_DEFAULT_PROTOCOL = 'http://';

		/** @const string URI_SEPARATOR разделитель адресов страниц в URL */
		const URI_SEPARATOR = '/';

		/** @const string URL_QUERY_START_SYMBOL символ, с которого начинает query в URL */
		const URL_QUERY_START_SYMBOL = '?';

		/** @const string URL_FRAGMENT_START_SYMBOL символ, с которого начинает fragment в URL */
		const URL_FRAGMENT_START_SYMBOL = '#';

		/** @const string HEAD_HTTP_REQUEST_TYPE тип HTTP запроса "HEAD" */
		const HEAD_HTTP_REQUEST_TYPE = 'HEAD';

		/** @const string CURL_TIMEOUT таймаут ожидания ответа на запрос в секундах */
		const CURL_TIMEOUT = 5;

		/** @const string CHECKING_LIMIT_BY_ONE_ITERATION ограничение на количество проверяемые адресов в одну итерацию */
		const CHECKING_LIMIT_BY_ONE_ITERATION = 10;

		/** @inheritdoc */
		public function checkBrokenUrls() {
			$state = $this->getState();

			if ($state->isComplete()) {
				return $this;
			}

			$links = $this->getLinks();

			if (umiCount($links) === 0) {
				$state->setCompleteStatus(true);
				return $this;
			}

			/** @var iEntity $link */
			foreach ($links as $link) {
				$url = $this->getAbsoluteLink($link);

				if ($this->isUrlBroken($url)) {
					$link->setBroken(true);
					$link->commit();
				}
			}

			$state->setOffset(
				$state->getOffset() + $state->getLimit()
			);

			return $this;
		}

		/** @inheritdoc */
		public function setState(iState $state) {
			$this->state = $state;
			return $this;
		}

		/** @inheritdoc */
		public function saveState() {
			$registry = $this->getRegistry();
			$state = $this->getState()
				->export();

			$registry->set(self::REGISTRY_KEY, json_encode($state));
			return $this;
		}

		/** @inheritdoc */
		public function flushSavedState() {
			$registry = $this->getRegistry();
			$registry->set(self::REGISTRY_KEY, null);
			return $this;
		}

		/** @inheritdoc */
		public function isComplete() {
			return $this->getState()
				->isComplete();
		}

		/**
		 * Возвращает абсолютную ссылку сущности
		 * @param iEntity $entity сущность
		 * @return string
		 */
		private function getAbsoluteLink(iEntity $entity) {
			$linkAddress = $entity->getAddress();

			if ($this->isAbsoluteUrl($linkAddress)) {
				return $linkAddress;
			}

			$linkPlaceAddress = $entity->getPlace();
			$protocolWithHost = $this->getProtocolWithHostFromAbsoluteUrl($linkPlaceAddress);

			if ($this->isUrlPath($linkAddress)) {
				return $protocolWithHost . $linkAddress;
			}

			if ($this->isUrlQuery($linkAddress)) {
				return $protocolWithHost . self::URI_SEPARATOR . $linkAddress;
			}

			if ($this->isUrlFragment($linkAddress)) {
				return $protocolWithHost . self::URI_SEPARATOR . $linkAddress;
			}

			if ($this->isUrlHost($linkAddress)) {
				return self::URL_DEFAULT_PROTOCOL . $linkAddress;
			}

			return $linkPlaceAddress . $linkAddress;
		}

		/**
		 * Получает и возвращает протокол с хостом из абсолютного url,
		 * то есть http://foo.bar/ из http://foo.bar/baz
		 * @param string $url абсолютный адрес страницы
		 * @return string
		 * @throws \privateException
		 */
		private function getProtocolWithHostFromAbsoluteUrl($url) {
			preg_match(self::HOST_WITH_PROTOCOL_PATTERN, $url, $matches);

			if (!isset($matches[0])) {
				throw new \privateException('Cant detect host');
			}

			return $matches[0];
		}

		/**
		 * Является ли строка абсолютным url, например http://foo.bar/baz
		 * @param string $string проверяемая строка
		 * @return bool
		 */
		private function isAbsoluteUrl($string) {
			return startsWith($string, self::URL_PROTOCOL_START_SYMBOLS);
		}

		/**
		 * Является ли строка url путем, например /foo/bar/
		 * @param string $string проверяемая строка
		 * @return bool
		 */
		private function isUrlPath($string) {
			return startsWith($string, self::URI_SEPARATOR);
		}

		/**
		 * Является ли строка url запросом, например ?foo=bar
		 * @param string $string проверяемая строка
		 * @return bool
		 */
		private function isUrlQuery($string) {
			return startsWith($string, self::URL_QUERY_START_SYMBOL);
		}

		/**
		 * Является ли строка url фрагментом, например #foo
		 * @param string $string проверяемая строка
		 * @return bool
		 */
		private function isUrlFragment($string) {
			return startsWith($string, self::URL_FRAGMENT_START_SYMBOL);
		}

		/**
		 * Является ли строка url хостом, например foo.bar
		 * @param string $string проверяемая строка
		 * @return bool
		 */
		private function isUrlHost($string) {
			return (bool) preg_match(self::HOST_PATTERN, $string);
		}

		/**
		 * Возвращает сущности-ссылки для проверки
		 * @return \UmiCms\Classes\System\Utils\Links\iEntity[]
		 * @throws \Exception
		 */
		private function getLinks() {
			$state = $this->getState();
			$linkCollection = $this->getLinksCollection();
			return $linkCollection->getCorrectLinks(
				$state->getOffset(),
				$state->getLimit()
			);
		}

		/**
		 * Корректен ли адрес страницы сайта
		 * @param string $url адрес страницы сайта
		 * @return bool
		 */
		private function isUrlBroken($url) {
			return ($this->requestUrlStatus($url) !== self::OK_HTTP_STATUS_CODE);
		}

		/**
		 * Возвращает код статуса ответа веб сервера по адресу страницы сайта
		 * @param string $url адрес страницы сайта
		 * @return int
		 * @throws \RequiredFunctionIsNotExistsException
		 */
		private function requestUrlStatus($url) {
			if (!function_exists('curl_init')) {
				throw new \RequiredFunctionIsNotExistsException('cURL library should be installed');
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::HEAD_HTTP_REQUEST_TYPE);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
			curl_exec($ch);
			$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			return $status;
		}

		/**
		 * Возвращает состояние проверки
		 * @return iState
		 */
		private function getState() {
			if ($this->state === null) {
				$this->setState(
					$this->loadState()
				);
			}

			return $this->state;
		}

		/**
		 * Загружает состояние из реестра
		 * @return iState
		 * @throws \Exception
		 */
		private function loadState() {
			$registry = $this->getRegistry();
			$state = $registry->get(self::REGISTRY_KEY);
			$state = json_decode($state, true);

			if (!is_array($state)) {
				$state = $this->getStartState();
			}

			return new State($state);
		}

		/**
		 * Возвращает начальное состояние проверки по умолчанию
		 * @return array
		 */
		private function getStartState() {
			return [
				iState::COMPLETE_KEY => false,
				iState::LIMIT_KEY => self::CHECKING_LIMIT_BY_ONE_ITERATION,
				iState::OFFSET_KEY => 0
			];
		}
	}
