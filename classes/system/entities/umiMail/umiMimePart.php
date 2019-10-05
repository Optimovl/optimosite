<?php

	class umiMimePart implements iUmiMimePart {

		const UMI_MIMEPART_CRLF = "\n";

		const UMI_MIMEPART_CHARSET = 'utf-8';

		private $arrHeaders = [];

		private $sBody;

		private $sEncoding = '7bit';

		private $arrSubParts = [];

		private $sEncBody = '';

		public function __construct($sBody, $arrParams) {
			// def header
			$this->arrHeaders['Content-Type'] = 'text/plain; charset=' . self::UMI_MIMEPART_CHARSET . '';

			$this->sBody = $sBody;
			foreach ($arrParams as $sParamName => $sParamValue) {
				switch ($sParamName) {
					case 'content-type':
						$this->arrHeaders['Content-Type'] =
							$sParamValue . (isset($arrParams['charset']) ? '; charset=' . $arrParams['charset'] . '' : '');
						break;
					case 'encoding':
						$this->sEncoding = $sParamValue;
						$this->arrHeaders['Content-Transfer-Encoding'] = $sParamValue;
						break;
					case 'cid':
						$this->arrHeaders['Content-ID'] = '<' . $sParamValue . '>';
						break;
					case 'disposition':
						$this->arrHeaders['Content-Disposition'] =
							$sParamValue . (isset($arrParams['']) ? '; filename="' . $dfilename . '"' : '');
				}
			}
		}

		/**
		 * @param $sBody
		 * @param $arrParams
		 * @return umiMimePart
		 */
		private function addSubPart($sBody, $arrParams) {
			$oNewPart = new self($sBody, $arrParams);
			$this->arrSubParts[] = $oNewPart;
			return $oNewPart;
		}

		/** @return umiMimePart */
		public function addMixedPart() {
			return $this->addSubPart('', ['content-type' => 'multipart/mixed']);
		}

		/** @return umiMimePart */
		public function addAlternativePart() {
			return $this->addSubPart('', ['content-type' => 'multipart/alternative']);
		}

		/** @return umiMimePart */
		public function addRelatedPart() {
			return $this->addSubPart('', ['content-type' => 'multipart/related']);
		}

		/**
		 * @param string $sText
		 * @return umiMimePart
		 */
		public function addTextPart($sText) {
			$arrParams = [];
			$arrParams['content-type'] = 'text/plain';
			$arrParams['encoding'] = '7bit';
			$arrParams['charset'] = self::UMI_MIMEPART_CHARSET;
			return $this->addSubPart($sText, $arrParams);
		}

		/**
		 * @param string $sHtml
		 * @return umiMimePart
		 */
		public function addHtmlPart($sHtml) {
			$arrParams = [];
			$arrParams['content-type'] = 'text/html';
			$arrParams['encoding'] = 'base64';
			$arrParams['charset'] = self::UMI_MIMEPART_CHARSET;
			return $this->addSubPart($sHtml, $arrParams);
		}

		/**
		 * @param array $arrImgData
		 * @return umiMimePart
		 */
		public function addHtmlImagePart($arrImgData) {
			$arrParams = [];
			$arrParams['content-type'] =
				(isset($arrImgData['content-type']) ? $arrImgData['content-type'] : 'image/jpeg') . '; ' . 'name="' .
				(isset($arrImgData['name']) ? $arrImgData['name'] : 'undefined.jpg') . '"';
			$arrParams['encoding'] = 'base64';
			$arrParams['disposition'] = 'inline';
			$arrParams['dfilename'] = isset($arrImgData['name']) ? $arrImgData['name'] : 'undefined.jpg';
			$arrParams['cid'] = isset($arrImgData['cid']) ? $arrImgData['cid'] : '';
			return $this->addSubPart(isset($arrImgData['data']) ? $arrImgData['data'] : '', $arrParams);
		}

		/**
		 * @param array $arrAttachmentData
		 * @return umiMimePart
		 */
		public function addAttachmentPart($arrAttachmentData) {
			$arrParams = [];
			$arrParams['dfilename'] = isset($arrAttachmentData['name']) ? $arrAttachmentData['name'] : 'undefined.jpg';
			$arrParams['encoding'] = isset($arrAttachmentData['encoding']) ? $arrAttachmentData['encoding'] : 'base64';
			$arrParams['content-type'] =
				(isset($arrAttachmentData['content-type']) ? $arrAttachmentData['content-type'] : 'image/jpeg') .
				'; ' . 'name="' .
				(isset($arrAttachmentData['name']) ? $arrAttachmentData['name'] : 'undefined.jpg') .
				'"';

			$arrParams['disposition'] = 'attachment';
			return $this->addSubPart(isset($arrAttachmentData['data']) ? $arrAttachmentData['data'] : '', $arrParams);
		}

		public static function quotedPrintableEncode($sData, $iMaxLineSize = 76) {
			$sResult = '';

			$arrLines = preg_split("/\r?\n/", $sData);
			$sEscape = '=';

			foreach ($arrLines as $sLine) {
				$arrLine = preg_split('||', $sLine, -1, PREG_SPLIT_NO_EMPTY);

				$sEncLine = '';

				for ($iI = 0; $iI < umiCount($arrLine); $iI++) {
					$sChar = $arrLine[$iI];
					$iDec = ord($sChar);

					if (($iDec == 32) AND ($iI == (umiCount($arrLine) - 1))) {
						$sChar = '=20';
					} elseif (($iDec == 9) AND ($iI == (umiCount($arrLine) - 1))) {
						$sChar = '=09';
					} elseif ($iDec == 9) {
						;
					} elseif (($iDec == 61) OR ($iDec < 32) OR ($iDec > 126)) {
						$sChar = $sEscape . mb_strtoupper(sprintf('%02s', dechex($iDec)));
					} elseif (($iDec == 46) AND ($sEncLine == '')) {
						//Bug #9722: convert full-stop at bol
						//Some Windows servers need this, won't break anything (cipri)
						$sChar = '=2E';
					}

					if ((mb_strlen($sEncLine) + mb_strlen($sChar)) >= $iMaxLineSize) {
						$sResult .= $sEncLine . $sEscape . self::UMI_MIMEPART_CRLF;
						$sEncLine = '';
					}
					$sEncLine .= $sChar;
				}
				$sResult .= $sEncLine . self::UMI_MIMEPART_CRLF;
			}

			return mb_substr($sResult, 0, -1 * mb_strlen(self::UMI_MIMEPART_CRLF)); // Don't want last crlf
		}

		private static function encodeData($sData, $sEncoding = '7bit') {
			$sResult = '';

			switch ($sEncoding) {
				case 'quoted-printable':
					$sResult = self::quotedPrintableEncode($sData);
					break;
				case 'base64':
					$sResult = rtrim(chunk_split(base64_encode($sData), 76, self::UMI_MIMEPART_CRLF));
					break;
				//case '8bit':
				//case '7bit':
				default:
					$sResult = $sData;
					break;
			}

			return $sResult;
		}

		public function encodePart() {

			if (umiCount($this->arrSubParts)) {
				mt_srand((double) microtime() * 1000000);
				$sBoundary = '=_' . md5(mt_rand() . microtime());

				$this->arrHeaders['Content-Type'] .= ';' . self::UMI_MIMEPART_CRLF . "\t" . 'boundary="' . $sBoundary . '"';
				$arrSubParts = [];
				for ($iI = 0; $iI < umiCount($this->arrSubParts); $iI++) {
					//
					$arrHdrs = [];
					$oNextPart = $this->arrSubParts[$iI];
					if ($oNextPart instanceof self) {
						$arrNextPart = $oNextPart->encodePart();
						foreach ($arrNextPart['headers'] as $sName => $sValue) {
							$arrHdrs[] = $sName . ': ' . $sValue;
						}
						$arrSubParts[] =
							implode(self::UMI_MIMEPART_CRLF, $arrHdrs) . self::UMI_MIMEPART_CRLF . self::UMI_MIMEPART_CRLF .
							$arrNextPart['body'];
					}
				}

				$this->sEncBody =
					'--' . $sBoundary . self::UMI_MIMEPART_CRLF .
					implode('--' . $sBoundary . self::UMI_MIMEPART_CRLF, $arrSubParts) .
					'--' . $sBoundary . '--' . self::UMI_MIMEPART_CRLF . self::UMI_MIMEPART_CRLF;
			} else {
				$this->sEncBody = self::encodeData($this->sBody, $this->sEncoding) . self::UMI_MIMEPART_CRLF;
			}
			return ['body' => $this->sEncBody, 'headers' => $this->arrHeaders];
		}
	}
