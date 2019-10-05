<?php

	use UmiCms\Service;

	/** Класс буфера вывода для ответа на http запрос */
	class HTTPOutputBuffer extends outputBuffer {

		/** @var array $options @inheritdoc */
		protected $options = [
			'quick-edit' => true, // записывать в сессию информацию о редактируемых через EIP страницах, @see $this->end()
			'generation-time' => false // выводить строку времени генерации страницы, @see $this->getCallTime()
		];

		/** @var bool $headersSent заголовки уже были отправлены */
		private $headersSent = false;

		/**
		 * @inheritdoc
		 * @throws coreException
		 * @throws wrongParamException
		 */
		public function send() {
			$buffer = $this->buffer;

			if ($this->isEventsEnabled()) {
				$eventPoint = new umiEventPoint('systemBufferSend');
				$eventPoint->setMode('before');
				$eventPoint->addRef('buffer', $buffer);
				$eventPoint->call();
			}

			$request = Service::Request();

			if ($request->isAdmin() && $request->isHtml()) {
				$buffer = $this->replaceAmpForXsltAdmin($buffer);
			}

			$this->buffer = $buffer;

			$this->sendHeaders();
			echo $this->buffer;
			$this->clear();
		}

		/**
		 * @internal
		 * Отправляет заголовки
		 * @return bool
		 * @throws coreException
		 * @throws wrongParamException
		 */
		public function sendHeaders() {
			if ($this->headersSent) {
				return true;
			}

			if (headers_sent()) {
				return false;
			}

			$this->setDefaultHeaders();
			$this->sendBrowserCacheHeaders();

			if ($this->isEventsEnabled()) {
				$eventPoint = new umiEventPoint('bufferHeadersSend');
				$eventPoint->setMode('before');
				$eventPoint->addRef('buffer', $this);
				$eventPoint->call();
			}

			$this->sendStatusHeader();

			foreach ($this->getHeaderList() as $header => $value) {
				$this->sendHeader($header, $value);
			}

			$this->sendCookies();
			$this->headersSent = true;
		}

		/**
		 * @inheritdoc
		 * @todo вынести umiTemplater::prepareQuickEdit() в обработчик отправки буфера
		 * @throws coreException
		 * @throws wrongParamException
		 */
		public function end() {
			$this->bufferDirectHeaders();
			$this->deleteDirectHeaders();

			if (!DEBUG) {
				$this->reset();
			}

			if ($this->option('quick-edit')) {
				umiTemplater::prepareQuickEdit();
			}

			$callTime = $this->getCallTime();

			if ($callTime !== '') {
				$this->push($this->getCallTime());
			}

			$this->send();
			$this->stop();
		}

		/**
		 * @internal
		 * Проверяет безопасность url
		 * @todo вынести из данного класса, так как не имеет отношения к буфферу вывода
		 * @param string $url url
		 * @return bool
		 */
		public static function checkUrlSecurity($url) {
			return stripos($url, 'javascript:') === false && stripos($url, 'data:') === false &&
				!preg_match('/^\/{2,}/i', $url);
		}

		/** @inheritdoc */
		public function redirect($url, $textStatus = '301 Moved Permanently', $numStatus = 301) {
			$maxLevels = 0;

			while (ob_get_level() && $maxLevels++ < 5) {
				@ob_end_clean();
			}

			$url = urldecode($url);

			if (!self::checkUrlSecurity($url)) {
				$this->status('400 Bad Request');
				$this->sendStatusHeader();
				flush();
				$this->stop();
			}

			$this->status($textStatus);
			$this->sendStatusHeader();

			$uriInfo = parse_url($url);

			if (!isset($uriInfo['scheme']) && !isset($uriInfo['host'])) {
				$url = '/' . ltrim($url, '/');
			}

			$this->sendCookies();
			header('Location: ' . $url, true, $numStatus);
			flush();
			$this->stop();
		}

		/** @inheritdoc */
		public function length() {
			ob_start();
			echo $this->buffer;
			$size = ob_get_length();
			ob_end_clean();
			return $size;
		}

		/**
		 * Устанавливает значения для заголовков по умолчанию, если они не были заданы
		 * @throws wrongParamException
		 */
		protected function setDefaultHeaders() {
			if (!$this->issetHeader('Content-Type')) {
				$this->setHeader('Content-Type', $this->contentType() . '; charset=' . $this->charset());
			}

			if (!$this->issetHeader('Content-Length')) {
				$length = $this->length();

				if ($length) {
					$this->setHeader('Content-Length', (string) $length);
				}
			}

			if (!$this->issetHeader('Date')) {
				$this->setHeader('Date', gmdate('D, d M Y H:i:s') . ' GMT');
			}

			if ($this->isFatalOccurred()) {
				return;
			}

			if (!$this->issetHeader('X-Generated-By')) {
				$this->setHeader('X-Generated-By', 'UMI.CMS');
			}

			if (!$this->issetHeader('X-UA-Compatible') && mb_strpos(getServer('HTTP_USER_AGENT'), 'MSIE')) {
				$this->setHeader('X-UA-Compatible', 'IE=edge');
			}

			$version = (string) Service::RegistrySettings()->getVersion();
			if (!$this->issetHeader('X-CMS-Version') && !empty($version)) {
				$this->setHeader('X-CMS-Version', $version);
			}

			if (!$this->issetHeader('X-XSS-Protection')) {
				$this->setHeader('X-XSS-Protection', '0');
			}
		}

		/** Отправляет заголовок статуса ответа */
		protected function sendStatusHeader() {
			header('HTTP/1.1 ' . $this->status());
			// Some servers close connection when we duplicate status header
			if ((int) mainConfiguration::getInstance()->get('kernel', 'send-additional-status-header')) {
				header('Status: ' . $this->status());
			}
		}

		/**
		 * Отправляет заголовок
		 * @param string $name название
		 * @param string $value значение
		 */
		protected function sendHeader($name, $value) {
			header("{$name}: {$value}");
		}

		/**
		 * Возвращает строку времени генерации страницы, пример:
		 * <!– This page generated in 0.071788 secs by XSLT, SITE MODE –>
		 * @todo: вынести из данного класса
		 * @return string
		 */
		protected function getCallTime() {
			$generationTime = round(microtime(true) - $this->invokeTime, 6);
			$showGenerateTime = (string) mainConfiguration::getInstance()
				->get('kernel', 'show-generate-time');

			if (!$this->option('generation-time') || $showGenerateTime === '0') {
				return '';
			}

			$generatedBy = '';
			$contentGenerator = parent::contentGenerator();
			if (is_string($contentGenerator) && $contentGenerator !== '') {
				$generatedBy = ' by ' . $contentGenerator;
			}

			switch ($this->contentType()) {
				case 'text/html':
				case 'text/xml':
					return "<!-- This page generated in {$generationTime} secs{$generatedBy} -->";

				case 'application/javascript':
				case 'text/javascript':
					return "/* This page generated in {$generationTime} secs{$generatedBy} */";

				default:
					return '';
			}
		}

		/**
		 * Подменяет в выводе &amp; на &. У xslt если особенность - подменять все знаки амперсанда на сущность.
		 * Это, в частности мешает выводу эмодзи.
		 * @param string $buffer
		 * @return string
		 */
		private function replaceAmpForXsltAdmin($buffer) {
			return str_replace('&amp;', '&', $buffer);
		}

		/**
		 * Помещает заголовки, отправленные напрямую, в буффер
		 * @throws wrongParamException
		 */
		private function bufferDirectHeaders() {
			foreach (headers_list() as $header) {
				list($name, $value) = explode(':', $header, 2);
				$this->setHeader(trim($name), trim($value));
			}
		}

		/** Удаляет заголовки, отправленные напрямую, из очереди на отправку */
		private function deleteDirectHeaders() {
			@header_remove();
		}

		/** Отправляет куки */
		private function sendCookies() {
			$cookiesResponsePool = Service::CookieJar()
				->getResponsePool();

			foreach ($cookiesResponsePool->getList() as $cookie) {
				setcookie(
					$cookie->getName(),
					$cookie->getValue(),
					$cookie->getExpirationTime(),
					$cookie->getPath(),
					$cookie->getDomain(),
					$cookie->isSecure(),
					$cookie->isForHttpOnly()
				);

				$cookiesResponsePool->remove($cookie->getName());
			}
		}

		/** Отправляет заголовки для кеширования браузером */
		private function sendBrowserCacheHeaders() {
			Service::BrowserCache()
				->process();
		}

		/** @deprecated */
		public function header($name, $value = false) {
			if ($value === false) {
				$this->unsetHeader($name);
				return null;
			}

			$this->setHeader($name, $value);
			return $value;
		}

		/**
		 * @deprecated
		 * @param mixed $data
		 */
		public function printJson($data) {
			Service::Response()
				->printJson($data);
		}

		/** @deprecated */
		public function getHTTPRequestBody() {
			return Service::Request()->getRawBody();
		}
	}
