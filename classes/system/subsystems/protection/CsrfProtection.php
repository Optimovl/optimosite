<?php

	namespace UmiCms\System\Protection;

	use UmiCms\Classes\System\Utils\Idn\iConverter;
	use UmiCms\System\Hierarchy\Domain\iDetector;
	use UmiCms\System\Session\iSession;
	use UmiCms\System\Events\iEventPointFactory;

	/**
	 * Класс защиты от CSRF
	 * CSRF - "Cross Site Request Forgery" — "Межсайтовая подделка запроса"
	 * Class CsrfProtection
	 * @example
	 *
	 * use UmiCms\System\Protection\CsrfProtection;
	 *
	 * try {
	 *    $csrfProtection = \UmiCms\Service::CsrfProtection();
	 *    $csrfProtection->checkOriginCorrect('example.com');
	 *    $csrfProtection->checkTokenMatch('some_token');
	 *
	 *    // Проверка пройдена успешно
	 *  } catch (\UmiCms\System\Protection\CsrfException $e) {
	 *    // Проверка провалена
	 *  }
	 *
	 * @package UmiCms\System\Protection
	 */
	class CsrfProtection implements iCsrfProtection {

		/** @const ключ в сессии, в значении которого хранится токен */
		const TOKEN_KEY = 'csrf_token';

		/** @var iSession объект работы с сессией */
		private $session;

		/** @var iDetector $domainDetector определитель домена */
		private $domainDetector;

		/** @var iConverter $idnConverter Idn конвертер */
		private $idnConverter;

		/** @var \iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/** @var iEventPointFactory $eventPointFactory фабрика событий */
		private $eventPointFactory;

		/** @var iHashComparator $hashComparator сравнитель хэшей */
		private $hashComparator;

		/** @var string токен, с которым будет производиться сравнение для защиты от CSRF */
		private $token;

		/** @inheritdoc */
		public function __construct(
			iSession $session,
			iDetector $domainDetector,
			iConverter $idnConverter,
			\iDomainsCollection $domainCollection,
			iEventPointFactory $eventPointFactory,
			iHashComparator $hashComparator
		) {
			$this->session = $session;
			$this->domainDetector = $domainDetector;
			$this->idnConverter = $idnConverter;
			$this->domainCollection = $domainCollection;
			$this->eventPointFactory = $eventPointFactory;
			$this->hashComparator = $hashComparator;
		}

		/** @inheritdoc */
		public function generateToken() {
			return md5(mt_rand() . microtime());
		}

		/** @inheritdoc */
		public function checkTokenMatch($token) {
			$isMatch = $this->getHashComparator()
				->equals($this->getToken(), $token);
			return $this->checkCondition($isMatch);
		}

		/** @inheritdoc */
		public function checkOriginCorrect($origin) {
			$this->checkNotEmpty($origin);
			$origin = $this->removeProtocol($origin);

			$originList = [
				$origin,
				$this->getIdnConverter()->decode($origin)
			];

			$domain = $this->getCurrentDomain();
			$host = $domain->getHost();
			$hasHostInHeader = in_array($host, $originList);

			if (!$hasHostInHeader) {
				foreach ($domain->getMirrorsList() as $mirror) {
					$hasHostInHeader = in_array($mirror->getHost(), $originList);

					if ($hasHostInHeader) {
						$host = $mirror->getHost();
						break;
					}
				}
			}

			return $this->checkCondition($host && $hasHostInHeader);
		}

		/** @inheritdoc */
		public function checkReferrerCorrect($referrer) {
			$host = $this->getHostFromReferrer($referrer);

			$checkReferrerResult = null;
			$event = $this->getEventPointFactory()->create('checkReferrerCorrect', 'before');
			$event->addRef('checkReferrerResult', $checkReferrerResult)
				->setParam('hostFromReferrer', $host)
				->call();

			if (is_bool($checkReferrerResult)) {
				return $checkReferrerResult;
			}

			$this->checkNotEmpty($host);

			$domainNames = [];
			$domainNames[] = $host;
			$domainNames[] = 'www.' . $host;
			$domainNames[] = (string) preg_replace('/(:\d+)/', '', $host);

			$domainsCollection = $this->getDomainCollection();

			foreach ($domainNames as $domainName) {
				if (is_numeric($domainsCollection->getDomainId($domainName))) {
					return true;
				}
			}

			$this->checkCondition(false);
		}

		/**
		 * Убирает протокол из url
		 * @param string $url
		 * @return string
		 */
		private function removeProtocol($url) {
			return preg_replace('|(^https?:\/\/)|', '', $url);
		}

		/**
		 * Возвращает текущий CSRF-токен
		 * @return string
		 * @throws \coreException если токен не определен
		 */
		private function getToken() {
			if ($this->token === null) {
				$this->loadToken();
			}

			if (!$this->token) {
				throw new \coreException('CSRF token cannot be empty');
			}

			return $this->token;
		}

		/**
		 * Возвращает зависимость в виде объекта, который работает с сессией
		 * @return iSession
		 */
		private function getSession() {
			return $this->session;
		}

		/**
		 * Загружает токен из хранилища
		 * @return bool|mixed|null|string
		 */
		private function loadToken() {
			$session = $this->getSession();
			$this->token = $session->get(self::TOKEN_KEY);
			return $this->token;
		}

		/**
		 * Определяет домен из заголовка HTTP_REFERER
		 * @param string $referrer заголовок
		 * @return string
		 */
		private function getHostFromReferrer($referrer) {
			preg_match('|^http(?:s)?:\/\/(?:www\.)?([^\/]+)|ui', $referrer, $matches);

			if (isset($matches[1]) && umiCount($matches[1]) == 1) {
				return $matches[1];
			}

			return '';
		}

		/**
		 * Возвращает текущий домен системы
		 * @return \iDomain текущий домен системы
		 * @throws \coreException если невозможно получить текущий домен
		 */
		private function getCurrentDomain() {
			return $this->getDomainDetector()->detect();
		}

		/**
		 * Возвращает определитель домена
		 * @return iDetector
		 */
		private function getDomainDetector() {
			return $this->domainDetector;
		}

		/**
		 * Возвращает Idn конвертер
		 * @return iConverter
		 */
		private function getIdnConverter() {
			return $this->idnConverter;
		}

		/**
		 * Возвращает коллекцию доменов
		 * @return \iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}

		/**
		 * Возвращает фабрику событий
		 * @return iEventPointFactory
		 */
		private function getEventPointFactory() {
			return $this->eventPointFactory;
		}

		/**
		 * Возвращает сравнителя хэшей
		 * @return iHashComparator
		 */
		private function getHashComparator() {
			return $this->hashComparator;
		}

		/**
		 * Производит проверку условия
		 * @param bool $condition условие
		 * @return bool true если условие истинно
		 * @throws CsrfException если условие ложно
		 */
		private function checkCondition($condition) {
			if (!$condition) {
				throw new CsrfException('Csrf protection check failed');
			}

			return true;
		}

		/**
		 * Проверяет, что переданный аргумент не пустой
		 * @param string $string строка
		 * @throws \InvalidArgumentException
		 */
		private function checkNotEmpty($string) {
			if (!$string) {
				throw new \InvalidArgumentException("Empty argument {$string}");
			}
		}
	}
