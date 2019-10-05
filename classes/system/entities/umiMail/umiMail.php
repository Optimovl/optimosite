<?php

	use UmiCms\Service;
	use UmiCms\Mail\EngineFactory;

	/** Класс для отправки писем. */
	class umiMail implements iUmiMail {

		private $template;

		private $is_commited = false;

		private $is_sended = false;

		private $subject = '';

		private $from_email = 'no_reply@no_reply.ru';

		private $from_name = 'No_address';

		private $files = [];

		/** @var array Список получателей */
		private $recipients = [];

		private $reply_to = [];

		private $copy = [];

		private $hidden_copy = [];

		private $priority;

		private $mess_body;

		private $content;

		private $sTxtBody;

		private $arrHeaders = [];

		private $arrContentImages = [];

		private $arrAttachmentsImages = [];

		private $arrAttachments = [];

		private static $arrImagesCache = [];

		private static $arrAttachmentsCache = [];

		/** @var string $engine код средства доставки писем */
		private $engine;

		/** @var array $sendResult результат отправки письма */
		private $sendResult = [];

		/** @var bool $innerImgToAttach флаг конвертаци картинок из контента во вложение */
		private $innerImgToAttach = true;

		/** @var string $additionalParameters дополнительные параметры отправки писем */
		private $additionalParameters = '';

		/** @var bool $parseContent флаг необходимости разбора тела письма */
		private $parseContent;

		/** @const string SECTION секция настроек писем */
		const SECTION = 'mail';

		/** @const string ENGINE опция средства доставки писем */
		const ENGINE = 'engine';

		/** @const string DEFAULT_PARSE_CONTENT опция парсинга писем */
		const DEFAULT_PARSE_CONTENT = 'default.parse.content';

		/**
		 * Конструктор.
		 * @param string $template
		 * @param string|null $engine
		 * @throws coreException
		 */
		public function __construct($template = 'default', $engine = null) {
			$this->template = $template;
			$this->priority = 'normal';

			$config = mainConfiguration::getInstance();

			if (!is_string($engine)) {
				$engine = (string) $this->getEngine();
			}

			if (empty($engine)) {
				$engine = 'phpMail';
			}

			$this->engine = $engine;

			$innerImgToAttachment = $config->get('kernel', 'inner-img-to-attachment');

			if ($innerImgToAttachment !== null) {
				$this->innerImgToAttach = (bool) $innerImgToAttachment;
			}

			$this->setNeedToParseContent($this->getContentFlag());
		}

		/** @inheritdoc */
		public function isNeedToParseContent() {
			return $this->parseContent;
		}

		/** @inheritdoc */
		public function setNeedToParseContent($flag = true) {
			$this->parseContent = (bool) $flag;
			return $this;
		}

		/**
		 * Возвращает результат отправленного письма.
		 * @throws coreException в случае, если письмо не было отправлено
		 * @return array()
		 */
		public function getSendResult() {
			if (!$this->is_sended) {
				throw new coreException('Mail was not send.');
			}

			return $this->sendResult;
		}

		/**
		 * Добавляет получателя в общий список.
		 * @param string $email e-mail получателя
		 * @param bool|string $name имя получателя
		 * @return bool false - если в $email некорректный адрес, true в противном случае
		 */
		public function addRecipient($email, $name = false) {
			$emailList = explode(',', $email);

			$emailList = array_filter($emailList, function ($email) {
				return self::checkEmail($email);
			});

			if (empty($emailList)) {
				return false;
			}

			$name = is_string($name) ? trim($name) : '';

			foreach ($emailList as $email) {
				$recipient = [trim($email), $name];

				if (!in_array($recipient, $this->recipients)) {
					$this->recipients[] = $recipient;
				}
			}

			return true;
		}

		/** @inheritdoc */
		public function hasRecipients() {
			return !empty($this->recipients);
		}

		/**
		 * Добавляет получателя в копию.
		 * @param string $email e-mail получателя
		 * @param bool|string $name имя получателя
		 * @return bool false - если в $email некорректный адрес, true в противном случае
		 */
		public function addCopy($email, $name = false) {
			if (self::checkEmail($email)) {
				$copy = ($name ? trim($name) . ' ' : '') . '<' . trim($email) . '>';
				if (!in_array($copy, $this->copy)) {
					$this->copy[] = $copy;
				}
				return true;
			}

			return false;
		}

		/**
		 * Добавляет получателя в скрытую копию.
		 * @param string $email e-mail получателя
		 * @param bool|string $name имя получателя
		 * @return bool false - если в $email некорректный адрес, true в противном случае
		 */
		public function addHiddenCopy($email, $name = false) {
			if (self::checkEmail($email)) {
				$copy = ($name ? trim($name) . ' ' : '') . '<' . trim($email) . '>';
				if (!in_array($copy, $this->hidden_copy)) {
					$this->hidden_copy[] = $copy;
				}
				return true;
			}

			return false;
		}

		/**
		 * Добавляет получателя ответа.
		 * @param string $email e-mail получателя
		 * @param bool|string $name имя получателя
		 * @return bool false - если в $email некорректный адрес, true в противном случае
		 */
		public function addReplyTo($email, $name = false) {
			if (self::checkEmail($email)) {
				$reply_to = ($name ? trim($name) . ' ' : '') . '<' . trim($email) . '>';
				if (!in_array($reply_to, $this->reply_to)) {
					$this->reply_to[] = $reply_to;
				}
				return true;
			}

			return false;
		}

		/**
		 * Устанавливает отправителя.
		 * @param string $email e-mail отправителя
		 * @param bool|string $name имя отправителя
		 * @return bool false - если в $email некорректный адрес, true в противном случае
		 */
		public function setFrom($email, $name = false) {
			if (self::checkEmail($email)) {
				$this->from_email = $email;
				$this->from_name = str_replace(['"', "'"], ["\\\"", "\\'"], $name);
				return true;
			}

			return false;
		}

		/** Возвращает тему письма */
		public function getSubject() {
			return $this->subject;
		}

		/**
		 * Устанавливает тему письма.
		 * @param string $subject тема письма
		 */
		public function setSubject($subject) {
			$this->subject = (string) str_replace("\n", ' ', str_replace("\r", '', $subject));
		}

		/**
		 * Возвращает текст письма
		 * @return mixed
		 */
		public function getContent() {
			return $this->content;
		}

		/**
		 * Устанавливает текст письма, заменяя макросы значениями.
		 * @param string $contentString текст письма
		 * @param bool $parseTplMacros парсить ли tpl макросы
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function setContent($contentString, $parseTplMacros = true) {
			$this->content = (string) $contentString;
			$this->content = str_replace('&#037;', '%', $this->content);

			if ($parseTplMacros) {
				$this->content = def_module::parseTPLMacroses($this->content);
			}
		}

		/**
		 * Устанавливает текст письма, не производя никакую обработку.
		 * @param string $sTxtContent текст письма
		 */
		public function setTxtContent($sTxtContent) {
			$this->sTxtBody = (string) $sTxtContent;
		}

		/**
		 * Устанавливает приоритет письма.
		 * @param string $level приоритет
		 */
		public function setPriorityLevel($level = 'normal') {
			switch ($level) {
				case 'highest':
					$this->priority = '1 (Highest)';
					break;
				case 'hight':
					$this->priority = '2 (Hight)';
					break;
				case 'normal':
					$this->priority = '3 (Normal)';
					break;
				case 'low':
					$this->priority = '4 (Low)';
					break;
				case 'lowest':
					$this->priority = '5 (Lowest)';
					break;
				default:
					$this->priority = '3 (Normal)';
					break;
			}
		}

		/**
		 * Устанавливает уровень важности. Зарезервировано, не используется.
		 * @param string $level уровень важности
		 */
		public function setImportanceLevel($level = 'normal') {
			//TODO
		}

		/** Выставляет флаг, что письмо обработано. */
		public function commit() {
			$this->is_commited = true;
		}

		private function addHTMLImage($sImagePath, $sCType = 'image/jpeg') {
			$sRealPath = $sImagePath;
			if (mb_strtolower(mb_substr($sImagePath, 0, 7)) !== 'http://') {
				if (!file_exists($sImagePath)) {
					if (isset($_SERVER['SERVER_NAME'])) {
						$host = $_SERVER['SERVER_NAME'];
						$domain = null;
					} else {
						$domain = Service::DomainCollection()
							->getDefaultDomain();
						$host = $domain->getHost();
					}
					$sRealPath = getSelectedServerProtocol($domain) . '://' . $host . '/' . ltrim($sImagePath, '/');
				}
			}

			if (isset(self::$arrImagesCache[$sRealPath])) {
				$this->arrAttachmentsImages[$sRealPath] = self::$arrImagesCache[$sRealPath];
				$this->arrContentImages[$sImagePath] = $sRealPath;
				return true;
			}

			if (($sImageData = @file_get_contents($sRealPath)) !== false) {
				$sBaseName = basename($sRealPath);
				$this->arrAttachmentsImages[$sRealPath] = [
					'name' => $sBaseName,
					'path' => $sImagePath,
					'data' => $sImageData,
					'content-type' => $sCType,
					'sizes' => @getimagesize($sImagePath),
					'cid' => md5(uniqid(mt_rand(), true))
				];
				self::$arrImagesCache[$sRealPath] = $this->arrAttachmentsImages[$sRealPath];
				$this->arrContentImages[$sImagePath] = $sRealPath;
				return true;
			}

			return false;
		}

		private function addAttachment($sPath, $sCType = 'application/octet-stream') {
			if (isset(self::$arrAttachmentsCache[$sPath])) {
				$this->arrAttachments[$sPath] = self::$arrAttachmentsCache[$sPath];
				return true;
			}

			$sBaseName = basename($sPath);
			if (($sFileData = @file_get_contents($sPath)) !== false) {
				$this->arrAttachments[$sPath] = [
					'name' => $sBaseName,
					'path' => $sPath,
					'data' => $sFileData,
					'content-type' => $sCType,
					'disposition' => 'attachment',
					'cid' => md5(uniqid(mt_rand(), true))
				];
				self::$arrAttachmentsCache[$sPath] = $this->arrAttachments[$sPath];
				return true;
			}

			return false;
		}

		/** Очищает список прикрепленных файлов. */
		public static function clearFilesCache() {
			self::$arrAttachmentsCache = [];
			self::$arrImagesCache = [];
		}

		/**
		 * Устанавливает заголовки письма
		 * @param array $arrXHeaders новые заголовки
		 * @param boolean $bOverwrite true - переписывать уже установленные совпадающие заголовки
		 * @return array текущие заголовки (после установки)
		 */
		public function getHeaders($arrXHeaders = [], $bOverwrite = false) {
			$arrHeaders = [];
			$arrHeaders['MIME-Version'] = '1.0';
			$arrHeaders = array_merge($arrHeaders, $arrXHeaders);

			$this->arrHeaders =
				$bOverwrite ? array_merge($this->arrHeaders, $arrHeaders) : array_merge($arrHeaders, $this->arrHeaders);

			return $this->encodeHeaders($this->arrHeaders);
		}

		private function encodeHeaders($arrHeaders) {
			$arrResult = [];

			foreach ($arrHeaders as $sHdrName => $sHdrVal) {
				$arrHdrVals = preg_split("/(\s)/", $sHdrVal, -1, PREG_SPLIT_DELIM_CAPTURE);
				$sPrevVal = '';
				$sEncHeader = '';
				foreach ($arrHdrVals as $sHdrVal) {
					if (!trim($sHdrVal)) {
						$sPrevVal .= $sHdrVal;
						continue;
					}

					$sHdrVal = $sPrevVal . $sHdrVal;
					$sPrevVal = '';

					$sQPref = $sQSuff = '';
					if (mb_substr($sHdrVal, 0, 1) == '"') {
						$sHdrVal = mb_substr($sHdrVal, 1);
						$sQPref = '"';
					}
					if (mb_substr($sHdrVal, -1, 1) == '"') {
						$sHdrVal = mb_substr($sHdrVal, 0, -1);
						$sQSuff = '"';
					}

					if (preg_match('/[\x80-\xFF]{1}/', $sHdrVal)) {
						$sHdrVal = iconv_mime_encode(
							$sHdrName,
							$sHdrVal,
							[
								'input-charset' => 'UTF-8',
								'output-charset' => 'UTF-8',
								'line-break-chars' => umiMimePart::UMI_MIMEPART_CRLF
							]
						);
						$sHdrVal = preg_replace('/^' . preg_quote($sHdrName, '/') . "\:\ /", '', $sHdrVal);
					}
					$sEncHeader .= $sQPref . $sHdrVal . $sQSuff;
				}
				$arrResult[$sHdrName] = $sEncHeader;
			}

			return $arrResult;
		}

		/**
		 * Разбирает тело письма:
		 * 1) Применяет шаблон;
		 * 2) Прикрепляет внутренние изображения к письму;
		 * 3) Заменяет относительные ссылки на абсолютные;
		 * @return string
		 * @throws coreException
		 * @throws publicException
		 * @throws ErrorException
		 */
		private function parseContent() {
			if (mainConfiguration::getInstance()->get('system', 'use-old-templater') && getRequest('scheme') !== null) {
				unset($_REQUEST['scheme']);
			}

			try {
				list($template) = def_module::loadTemplatesForMail('mail/' . $this->template, 'body');
			} catch (Exception $e) {
				$template = "%header%\n%content%";
			}

			$variables = [
				'header' => $this->subject,
				'content' => $this->content
			];

			$content = def_module::parseTemplateForMail($template, $variables);

			if ($this->innerImgToAttach) {
				$this->attachInnerImages($content);
			}

			// convert relative links to absolute
			$host = Service::DomainDetector()->detectHost();
			$content = preg_replace('#(href)\s*=\s*(["\']?)?(/([^\s"\']+))#i', '$1=$2http://' . $host . '$3', $content);

			if ($this->innerImgToAttach) {
				foreach ($this->arrContentImages as $sImagePath => $sRealPath) {
					if (!isset($this->arrAttachmentsImages[$sRealPath])) {
						continue;
					}

					$arrImgInfo = $this->arrAttachmentsImages[$sRealPath];
					$arrSearchReg = [
						'/(\s)((?i)src|background|href(?-i))\s*=\s*(["\']?)' . preg_quote($sImagePath, '/') . '\3/',
						'/(?i)url(?-i)\(\s*(["\']?)' . preg_quote($sImagePath, '/') . '\1\s*\)/'
					];
					$arrReplace = [
						'\1\2=\3cid:' . $arrImgInfo['cid'] . '\3',
						'url(\1cid:' . $arrImgInfo['cid'] . '\2)'
					];
					$content = preg_replace($arrSearchReg, $arrReplace, $content);
				}
			}

			return $content;
		}

		/**
		 * Добавляет к письму приложенные изображения, встреченные в его контенте
		 * @param $content
		 */
		private function attachInnerImages($content) {
			$imageUrlListFirstPart = [];
			$firstPattern = '#<\w+[^>]+\s((?i)src|background|href(?-i))\s*=\s*(["\']?)?([\w\?=\.\-_:\/]+.(jpeg|jpg|gif|png|bmp))\2#i';

			if (preg_match_all($firstPattern, $content, $matches)) {
				$imageUrlListFirstPart = isset($matches[3]) ? $matches[3] : [];
			}

			$imageUrlListSecondPart = [];
			$secondPattern = '#(?i)url(?-i)\(\s*(["\']?)([\w\.\-_:\/]+.(jpeg|jpg|gif|png|bmp))\1\s*\)#';

			if (preg_match_all($secondPattern, $content, $matches)) {
				$imageUrlListSecondPart = isset($matches[2]) ? $matches[2] : [];
			}

			$imageUrlList = array_unique(array_merge($imageUrlListFirstPart, $imageUrlListSecondPart));

			foreach ($imageUrlList as $imageUrl) {
				$this->addHTMLImage($imageUrl);
			}
		}

		/**
		 * Выполняет отправку сформированного письма.
		 * @return bool false - в случае ошибки или true в любом другом случае
		 * @throws coreException
		 * @throws publicException
		 * @throws ErrorException
		 */
		public function send() {
			if ($this->is_sended) {
				return true;
			}

			if ($this->content == '') {
				return false;
			}

			$this->arrHeaders['From'] = ($this->from_name ? "$this->from_name " : '') . '<' . $this->from_email . '>';
			$this->arrHeaders['X-Mailer'] = 'UMI.CMS';
			if (umiCount($this->reply_to)) {
				$this->arrHeaders['Reply-To'] = implode(', ', $this->reply_to);
			}

			if (umiCount($this->copy)) {
				$this->arrHeaders['Cc'] = implode(', ', $this->copy);
			}
			if (umiCount($this->hidden_copy)) {
				$this->arrHeaders['Bcc'] = implode(', ', $this->hidden_copy);
			}
			$this->arrHeaders['X-Priority'] = $this->priority;

			$content = $this->isNeedToParseContent() ? $this->parseContent() : $this->content;

			/** @var umiFile $oFile */
			foreach ($this->files as $oFile) {
				$this->addAttachment($oFile->getFilePath());
			}

			$bNeedAttachments = (bool) umiCount($this->arrAttachments);
			$bNeedHtmlImages = (bool) umiCount($this->arrAttachmentsImages);
			$bNeedHtmlBody = (bool) mb_strlen($content);
			$bNeedTxtBody = (bool) mb_strlen($this->sTxtBody);
			$bOnlyTxtBody = !$bNeedHtmlBody && (bool) mb_strlen($content);

			$oMainPart = new umiMimePart('', []);
			switch (true) {
				case $bOnlyTxtBody && !$bNeedAttachments:
					$oMainPart = $oMainPart->addTextPart($this->sTxtBody);
					break;

				case !$bNeedHtmlBody && !$bNeedTxtBody && $bNeedAttachments:
					$oMainPart = $oMainPart->addMixedPart();
					foreach ($this->arrAttachments as $arrAtthInfo) {
						$oMainPart->addAttachmentPart($arrAtthInfo);
					}
					break;

				case $bOnlyTxtBody && $bNeedAttachments:
					$oMainPart = $oMainPart->addMixedPart();
					$oMainPart->addTextPart($this->sTxtBody);
					foreach ($this->arrAttachments as $arrAtthInfo) {
						$oMainPart->addAttachmentPart($arrAtthInfo);
					}
					break;

				case $bNeedHtmlBody && !$bNeedHtmlImages && !$bNeedAttachments:
					$oMainPart = $oMainPart->addMixedPart();
					if ($bNeedTxtBody) {
						$oAlternativePart = $oMainPart->addAlternativePart();
						$oAlternativePart->addTextPart($this->sTxtBody);
						$oAlternativePart->addHtmlPart($content);
					} else {
						$oMainPart = $oMainPart->addHtmlPart($content);
					}
					break;

				case $bNeedHtmlBody && $bNeedHtmlImages && !$bNeedAttachments:
					$oMainPart = $oMainPart->addRelatedPart();
					if ($bNeedTxtBody) {
						$oAlternativePart = $oMainPart->addAlternativePart();
						$oAlternativePart->addTextPart($this->sTxtBody);
						$oAlternativePart->addHtmlPart($content);
					} else {
						$oMainPart->addHtmlPart($content);
					}
					foreach ($this->arrAttachmentsImages as $arrImgInfo) {
						$oMainPart->addHtmlImagePart($arrImgInfo);
					}
					break;

				case $bNeedHtmlBody && !$bNeedHtmlImages && $bNeedAttachments:
					$oMainPart = $oMainPart->addMixedPart();
					if ($bNeedTxtBody) {
						$oAlternativePart = $oMainPart->addAlternativePart();
						$oAlternativePart->addTextPart($this->sTxtBody);
						$oAlternativePart->addHtmlPart($content);
					} else {
						$oMainPart->addHtmlPart($content);
					}
					foreach ($this->arrAttachments as $arrAtthInfo) {
						$oMainPart->addAttachmentPart($arrAtthInfo);
					}
					break;

				case $bNeedHtmlBody && $bNeedHtmlImages && $bNeedAttachments:
					$oMainPart = $oMainPart->addMixedPart();
					if ($bNeedTxtBody) {
						$oAlternativePart = $oMainPart->addAlternativePart();
						$oAlternativePart->addTextPart($this->sTxtBody);
						$oRelPart = $oAlternativePart->addRelatedPart();
					} else {
						$oRelPart = $oMainPart->addRelatedPart();
					}
					$oRelPart->addHtmlPart($content);
					foreach ($this->arrAttachmentsImages as $arrImgInfo) {
						$oRelPart->addHtmlImagePart($arrImgInfo);
					}
					foreach ($this->arrAttachments as $arrAtthInfo) {
						$oMainPart->addAttachmentPart($arrAtthInfo);
					}
					break;
			}

			$arrEncodedPart = $oMainPart->encodePart();
			$this->mess_body = $arrEncodedPart['body'];

			$arrHeaders = $this->getHeaders($arrEncodedPart['headers'], true);
			$sHeaders = '';

			foreach ($arrHeaders as $sHdrName => $sHdrVal) {
				$sHeaders .= $sHdrName . ': ' . $sHdrVal . umiMimePart::UMI_MIMEPART_CRLF;
			}

			foreach ($this->recipients as $recnt) {
				$sRecipientName = trim(str_replace("\n", ' ', $recnt[1]));
				$recipientEmail = trim($recnt[0]);

				if ($recipientEmail === '') {
					continue;
				}

				$sMailTo = $recipientEmail;

				if ($sRecipientName !== '') {
					$sMailTo = iconv_mime_encode(
						'',
						$recnt[1],
						[
							'input-charset' => 'UTF-8',
							'output-charset' => 'UTF-8',
							'line-break-chars' => ''
						]
					);
					$sMailTo = ltrim($sMailTo, ' :');
					$sMailTo .= ' <' . $recipientEmail . '>';
				}

				$sSubject = '';

				if ($this->subject !== '') {
					$sSubject = iconv_mime_encode(
						'',
						$this->subject,
						[
							'input-charset' => 'UTF-8',
							'output-charset' => 'UTF-8',
							'line-break-chars' => ''
						]
					);
					$sSubject = ltrim($sSubject, ' :');
				}

				$message = $this->mess_body;
				$parameters = $this->getAdditionalParameters();

				$oEventPoint = new umiEventPoint('core_sendmail');
				$oEventPoint->setParam('to', $sMailTo);
				$oEventPoint->setParam('subject', $sSubject);
				$oEventPoint->setParam('body', $message);
				$oEventPoint->setParam('headers', $sHeaders);
				$oEventPoint->setParam('parameters', $parameters);
				$oEventPoint->setParam('mail', $this);

				$oEventPoint->addRef('to', $sMailTo);
				$oEventPoint->addRef('subject', $sSubject);
				$oEventPoint->addRef('body', $message);
				$oEventPoint->addRef('headers', $sHeaders);
				$oEventPoint->addRef('parameters', $parameters);

				$oEventPoint->setMode('before');
				$oEventPoint->call();

				$this->sendResult = $this->internalMail($sMailTo, $sSubject, $message, $sHeaders, $parameters);

				$oEventPoint->setMode('after');
				$oEventPoint->call();
			}

			return $this->is_sended = true;
		}

		/**
		 * Возвращает дополнительные параметры отправки письма
		 * @return string
		 */
		public function getAdditionalParameters() {
			return $this->additionalParameters;
		}

		/**
		 * Устанавливает дополнительные параметры отправки письма
		 * @param string $parameters
		 * @return $this
		 * @throws InvalidArgumentException
		 */
		public function setAdditionalParameters($parameters) {
			if (!is_string($parameters) || empty($parameters)) {
				throw new InvalidArgumentException('Incorrect parameters given, params value must be not empty string.');
			}

			$this->additionalParameters = $parameters;
			return $this;
		}

		/**
		 * Отправляет письмо и возвращает результат отправки.
		 * @param string $mailTo
		 * @param string $subject
		 * @param string $message
		 * @param string $headers
		 * @param string $parameters
		 * @return array
		 * @throws coreException
		 */
		private function internalMail($mailTo, $subject, $message, $headers, $parameters) {
			try {
				$transport = EngineFactory::get($this->engine);
			} catch (Exception $exception) {
				umiExceptionHandler::report($exception);
				$transport = EngineFactory::createDefault();
			}

			$isSent = $transport->setSubject($subject)
				->setMessage($message)
				->setHeaders($headers)
				->setParameters($parameters)
				->send($mailTo);

			return [
				'mailTo' => $mailTo,
				'subject' => $subject,
				'message' => $message,
				'headers' => $headers,
				'isOk' => $isSent
			];
		}

		/**
		 * Прикрепляет файл к письму
		 * @param iUmiFile $file прикрепляемый файл
		 * @return bool true - в случае успеха (если файл еще не прикреплен)
		 */
		public function attachFile(iUmiFile $file) {
			if (!in_array($file, $this->files) && $file->isExists()) {
				$this->files[] = $file;
				return true;
			}
		}

		/**
		 * Деструктор.
		 * @throws coreException
		 * @throws publicException
		 * @throws ErrorException
		 */
		public function __destruct() {
			if ($this->is_commited && !$this->is_sended) {
				$this->send();
			}
		}

		/**
		 * Проверяет валидность e-mail адреса
		 * @param string $email адрес
		 * @return boolean true - валидный, false - не валидный
		 */
		public static function checkEmail($email) {
			if (!is_string($email) || empty($email)) {
				return false;
			}

			return (bool) preg_match("/.+\@.+\..+/", $email);
		}

		/**
		 * Возвращает имя средства доставки письма
		 * @return string
		 * @throws coreException
		 */
		private function getEngine() {
			$registryPath = $this->getCustomPrefix() . self::ENGINE;

			return $this->isUseCustomSettings()
				? Service::Registry()->get($registryPath)
				: $this->getConfigMailValue(self::ENGINE);
		}

		/**
		 * Возвращает флаг парсинга контенте
		 * @return bool
		 * @throws coreException
		 */
		private function getContentFlag() {
			$registryPath = $this->getCustomPrefix() . 'disable-parse';

			return $this->isUseCustomSettings()
				? !Service::Registry()->get($registryPath)
				: $this->getConfigMailValue(self::DEFAULT_PARSE_CONTENT);
		}

		/**
		 * Возвращает значение директивы секции [mail] config.ini
		 * @param string $name имя директивы
		 * @return string
		 */
		private function getConfigMailValue($name) {
			return mainConfiguration::getInstance()->get(self::SECTION, $name);
		}

		/**
		 * Определяет использовать ли настройки для конкретного сайта и языковой версии
		 * @return bool
		 * @throws coreException
		 */
		private function isUseCustomSettings() {
			return (bool) Service::Registry()
				->get($this->getCustomPrefix() . 'use-custom-settings');
		}

		/**
		 * Возвращает префикс пути к настройкам почты на данном домене и языковой версии
		 * @return string
		 * @throws coreException
		 */
		private function getCustomPrefix() {
			$domainId = Service::DomainDetector()->detectId();
			$languageId = Service::LanguageDetector()->detectId();

			return "//settings/mail/$domainId/$languageId/";
		}
	}
