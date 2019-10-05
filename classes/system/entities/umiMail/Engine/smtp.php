<?php

	namespace UmiCms\Mail\Engine;

	use UmiCms\Mail;
	use UmiCms\Service;
	use PHPMailer\PHPMailer;
	use UmiCms\System\Solution\iRegistry as iRegistry;

	/**
	 * Класс средства отправки писем через smtp
	 * @package UmiCms\Mail\Engine
	 */
	class smtp extends Mail\Engine {

		/** @var \iConfiguration $config конфигурация */
		private $config;

		/** @var iRegistry $registry реестр */
		private $registry;

		/** @var PHPMailer\PHPMailer $mailer библиотека отправки писем */
		private $mailer;

		/** @var \iUmiLogger $logger логгер */
		private $logger;

		/** @const string SECTION секция настроек писем */
		const SECTION = 'mail';

		/** @const string TIMEOUT опция таймаута подключения */
		const TIMEOUT = 'smtp.timeout';

		/** @const string USE_VERP опция необходимости генерации VERP адрес при отправке */
		const USE_VERP = 'smtp.use-verp';

		/** @const string HOST опция хоста подключения */
		const HOST = 'smtp.host';

		/** @const string PORT опция порта подключения */
		const PORT = 'smtp.port';

		/** @const string ENCRYPTION опция шифрования подключения */
		const ENCRYPTION = 'smtp.encryption';

		/** @const string AUTH опция необходимости авторизации */
		const AUTH = 'smtp.auth';

		/** @const string USER опция логина */
		const USER = 'smtp.user-name';

		/** @const string PASSWORD опция пароля */
		const PASSWORD = 'smtp.password';

		/** @const string DEBUG опция отладки */
		const DEBUG = 'smtp.debug';

		/**
		 * Конструктор
		 * @param \iConfiguration|null $config конфигурация
		 * @param iRegistry|null $registry реестр
		 * @throws \Exception
		 */
		public function __construct($config = null, $registry = null) {
			$this->config = ($config instanceof \iConfiguration)
				? $config
				: \mainConfiguration::getInstance();

			$this->registry = ($registry instanceof iRegistry)
				? $registry
				: Service::Registry();

			$this->initLogger()
				->initMailer();
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function send($address) {
			$transport = $this->getTransport();

			if (!$transport instanceof PHPMailer\SMTP) {
				return $this->handleError('Подключение не было инициализировано');
			}

			if (!$transport->connected()) {
				return $this->handleError('Подключение по smtp неактивно');
			}

			if (!$this->validateSender($transport)) {
				return $this->handleError('Передан некорректный адрес отправителя');
			}

			if (!$this->validateRecipient($transport, $address)) {
				return $this->handleError('Не задано ни одного корректного получателя письма');
			}

			$message = $this->buildMessage($address);

			if (!$transport->data($message)) {
				return $this->handleError('Не удалось отправить сообщение');
			}

			$transport->reset();

			return true;
		}

		/**
		 * Помещает сообщение в журнал
		 * @param string $message сообщение
		 * @param null $level не используется, необходим для поддержки формата сторонней библиотеки
		 * @return $this
		 */
		public function log($message, $level = null) {
			if (!is_string($message) || empty($message)) {
				return $this;
			}

			$logger = $this->getLogger();
			$logger->log($message);
			$logger->save();
			return $this;
		}

		/** Деструктор */
		public function __destruct() {
			$transport = $this->getTransport();

			if ($transport instanceof PHPMailer\SMTP) {
				$this->closeConnection($transport);
			}
		}

		/**
		 * Проверяет отправителя письма
		 * @param PHPMailer\SMTP $transport
		 * @return bool
		 */
		protected function validateSender(PHPMailer\SMTP $transport) {
			$mailFrom = $this->getAddressFromHeader($this->getHeaders());
			return (bool) $transport->mail($mailFrom);
		}

		/**
		 * Проверяет получателя письма
		 * @param PHPMailer\SMTP $transport
		 * @param string $address адрес в формате: имя <почтовый ящик>
		 * @return bool
		 */
		protected function validateRecipient(PHPMailer\SMTP $transport, $address) {
			$recipientList = $this->getRecipientList($address);

			foreach ($recipientList as $key => $recipient) {
				if (!is_string($recipient) || !$transport->recipient($recipient)) {
					unset($recipientList[$key]);
				}
			}

			if (empty($recipientList)) {
				return false;
			}

			return true;
		}

		/**
		 * Закрывает соединение
		 * @param PHPMailer\SMTP $transport
		 */
		protected function closeConnection(PHPMailer\SMTP $transport) {
			$transport->quit();
			$transport->close();
		}

		/**
		 * Возвращает список адресатов (адресат, копию, скрытую копию) для отправки письма
		 * @param string $address адрес в формате: имя <почтовый ящик>
		 * @return array
		 */
		protected function getRecipientList($address) {
			$recipientList = array_merge(
				(array) $this->getAddressFromHeader($address),
				$this->getAddressListFromHeaderString('/Cc: .+\s/'), // Адреса для копий писем
				$this->getAddressListFromHeaderString('/Bcc: .+\s/') // Адреса для скрытыйх копий писем
			);

			return array_unique($recipientList);
		}

		/**
		 * Возвращает список адресов из заголовков письма
		 * @param string $pattern правило для регулярного выражения, по которому можно получить строку из заголовков
		 * @return string[]
		 */
		protected function getAddressListFromHeaderString($pattern) {
			$addressList = [];
			preg_match($pattern, $this->getHeaders(), $matches);

			if (!isset($matches[0])) {
				return $addressList;
			}

			$copyHeaderList = explode(',', $matches[0]);

			foreach ($copyHeaderList as $copyHeader) {
				$address = $this->getAddressFromHeader($copyHeader);

				if ($address === null) {
					continue;
				}

				$addressList[] = $address;
			}

			return $addressList;
		}

		/**
		 * Возвращает адрес из заголовка
		 * @param string $header заголовок с адресом в формате: <адрес>
		 * @return string|null
		 */
		protected function getAddressFromHeader($header) {
			if (contains($header, '<')) {
				preg_match('/<(.*)>/', $header, $matches);
				return isset($matches[1]) ? $matches[1] : null;
			}

			return $header;
		}

		/**
		 * Формирует сообщение для отправки
		 * @param string $address адрес в формате: имя <почтовый ящик>
		 * @return string
		 */
		protected function buildMessage($address) {
			$headers = $this->getHeaders() .
				$this->buildHeader('To', $address) .
				$this->buildHeader('Subject', $this->getSubject());

			return $headers . "\r\n" . $this->getMessage();
		}

		/**
		 * Формирует заголовок сообщения
		 * @param string $name название заголовка
		 * @param string $value значение заголовка
		 * @return string
		 */
		protected function buildHeader($name, $value) {
			return "$name: $value" . "\r\n";
		}

		/**
		 * Инициализирует средство отправки писем
		 * @return smtp|bool
		 * @throws \Exception
		 */
		protected function initMailer() {
			$useExceptions = $this->isDebugEnabled();

			$mailer = new PHPMailer\PHPMailer($useExceptions);
			$mailer->SMTPDebug = $this->getDebugLevel();
			$mailer->Debugoutput = [$this, 'log'];
			$mailer->Host = $this->getHost();
			$mailer->Port = $this->getPort();
			$mailer->Timeout = $this->getTimeout();
			$mailer->do_verp = $this->needToUseDoVerp();

			if ($this->isAuthRequired()) {
				$mailer->SMTPAuth = true;
				$mailer->Username = $this->getUserName();
				$mailer->Password = $this->getPassword();
			}

			if ($this->isAutoEncryption()) {
				$mailer->SMTPAutoTLS = true;
			} else {
				$mailer->SMTPSecure = $this->getEncryption();
			}

			if (!$mailer->smtpConnect()) {
				return $this->handleError('Не удалось произвести подключение по smtp');
			}

			$this->mailer = $mailer;
			return $this;
		}

		/**
		 * Обрабатывает ошибку.
		 * Кидает исключение или пишет сообщение в лог, в зависимости от режима отладки.
		 * @param string $message сообщение об ошибке
		 * @return bool
		 * @throws \Exception
		 */
		protected function handleError($message) {
			if ($this->isDebugEnabled()) {
				throw new \Exception($message);
			}

			try {
				$this->log($message);
			} catch (\Exception $exception) {
				//nothing
			}

			return false;
		}

		/**
		 * Инициализирует логгер
		 * @return $this
		 */
		protected function initLogger() {
			$directory = new \umiDirectory($this->getLogDirectoryPath());

			if ($directory->getIsBroken()) {
				$directory::requireFolder($directory->getPath());
			}

			$this->logger = new \umiLogger($directory->getPath());
			return $this;
		}

		/**
		 * Возвращает логгер
		 * @return \iUmiLogger|null
		 */
		protected function getLogger() {
			return $this->logger;
		}

		/**
		 * Возвращает средство отправки писем
		 * @return PHPMailer\SMTP
		 */
		protected function getTransport() {
			return ($this->mailer instanceof PHPMailer\PHPMailer) ? $this->mailer->getSMTPInstance() : null;
		}

		/**
		 * Возвращает хост для подключения по smtp
		 * @return string
		 * @throws \coreException
		 */
		protected function getHost() {
			return (string) $this->getSettingsValue(self::HOST);
		}

		/**
		 * Возвращает порт для подключения по smtp
		 * @return int
		 * @throws \coreException
		 */
		protected function getPort() {
			return (int) $this->getSettingsValue(self::PORT);
		}

		/**
		 * Возвращает таймаунт подключения по smtp
		 * @return int
		 * @throws \coreException
		 */
		protected function getTimeout() {
			return (int) $this->getSettingsValue(self::TIMEOUT);
	}

		/**
		 * Определяет необходима ли авторизация для подключения по smtp
		 * @return bool
		 * @throws \coreException
		 */
		protected function isAuthRequired() {
			return (bool) $this->getSettingsValue(self::AUTH);
		}

		/**
		 * Возвращат имя для подключения по smtp
		 * @return string
		 * @throws \coreException
		 */
		protected function getUserName() {
			return (string) $this->getSettingsValue(self::USER);
		}

		/**
		 * Возвращат пароль для подключения по smtp
		 * @return string
		 * @throws \coreException
		 */
		protected function getPassword() {
			return (string) $this->getSettingsValue(self::PASSWORD);
		}

		/**
		 * Определяет включен ли режим отладки
		 * @return bool
		 * @throws \coreException
		 */
		protected function isDebugEnabled() {
			return (bool) $this->getSettingsValue(self::DEBUG);
		}

		/**
		 * Определяет включено ли автоматической определение типа шифрования
		 * @return bool
		 * @throws \coreException
		 */
		protected function isAutoEncryption() {
			return $this->getEncryption() == 'auto';
		}

		/**
		 * Возвращат тип шифрования для подключения по smtp
		 * @return string (tls|ssl|auto)
		 * @throws \coreException
		 */
		protected function getEncryption() {
			return (string) $this->getSettingsValue(self::ENCRYPTION);
		}

		/**
		 * Определяет нужно ли генерировать VERP адрес при отправке
		 * @link https://en.wikipedia.org/wiki/Variable_envelope_return_path
		 * @link http://www.postfix.org/VERP_README.html Postfix VERP info
		 * @return bool
		 * @throws \coreException
		 */
		protected function needToUseDoVerp() {
			return (bool) $this->getSettingsValue(self::USE_VERP);
		}

		/**
		 * Возвращает глубину отладки smtp подключения
		 * @return int
		 * @throws \coreException
		 */
		protected function getDebugLevel() {
			return $this->isDebugEnabled() ? PHPMailer\SMTP::DEBUG_LOWLEVEL : PHPMailer\SMTP::DEBUG_OFF;
		}

		/**
		 * Возвращает значение настройки smtp
		 * @param string $name имя настройки
		 * @return string
		 * @throws \coreException
		 */
		protected function getSettingsValue($name) {
			$registry = $this->registry;
			$prefix = $this->getCustomPrefix();

			return $registry->get($prefix . 'use-custom-settings')
				? $registry->get($prefix . $name)
				: $this->config->get(self::SECTION, $name);
		}

		/**
		 * Возвращает префикс пути к опциям smtp на данном домене и языковой версии
		 * @return string
		 * @throws \coreException
		 */
		protected function getCustomPrefix() {
			$domainId = Service::DomainDetector()->detectId();
			$languageId = Service::LanguageDetector()->detectId();

			return "//settings/mail/$domainId/$languageId/";
		}

		/**
		 * Возвращает путь до директории с журналом
		 * @return string
		 */
		protected function getLogDirectoryPath() {
			return $this->config->includeParam('sys-log-path') . '/smtp/';
		}
	}
