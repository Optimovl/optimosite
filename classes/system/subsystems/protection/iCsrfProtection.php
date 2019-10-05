<?php
	namespace UmiCms\System\Protection;

	use UmiCms\Classes\System\Utils\Idn\iConverter;
	use UmiCms\System\Hierarchy\Domain\iDetector;
	use UmiCms\System\Session\iSession;
	use UmiCms\System\Events\iEventPointFactory;

	/**
	 * Интерфейс защиты от csrf атак
	 * @package UmiCms\System\Protection
	 */
	interface iCsrfProtection {

		/**
		 * Конструктор
		 * @param iSession $session контейнер сессии
		 * @param iDetector $domainDetector определитель домена
		 * @param iConverter $idnConverter Idn конвертер
		 * @param \iDomainsCollection $domainCollection коллекция доменов
		 * @param iEventPointFactory $eventPointFactory фабрика событий
		 * @param iHashComparator $hashComparator сравнитель хэшей
		 */
		public function __construct(
			iSession $session,
			iDetector $domainDetector,
			iConverter $idnConverter,
			\iDomainsCollection $domainCollection,
			iEventPointFactory $eventPointFactory,
			iHashComparator $hashComparator
		);

		/** Возвращает новое значение csrf-токена */
		public function generateToken();

		/**
		 * Производит проверку совпадения токенов
		 * @param string $token проверяемый CSRF-токен
		 * @return bool true в случае успешного прохождения проверки
		 * @throws CsrfException в случае неудачного прохождения проверки
		 * @throws \coreException если невозможно получить текущий домен
		 */
		public function checkTokenMatch($token);

		/**
		 * Производит проверку заголовка Origin
		 * @param string $origin проверяемый заголовок Origin
		 * @return bool true в случае успешного прохождения проверки
		 * @throws \InvalidArgumentException если передан пустой заголовок
		 * @throws CsrfException в случае неудачного прохождения проверки
		 * @throws \coreException если невозможно получить текущий домен
		 */
		public function checkOriginCorrect($origin);

		/**
		 * Производит проверку заголовка Referer
		 * @param string $referrer проверяемый заголовок Referer
		 * @return bool true в случае успешного прохождения проверки
		 * @throws CsrfException в случае неудачного прохождения проверки
		 */
		public function checkReferrerCorrect($referrer);
	}