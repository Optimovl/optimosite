<?php

	interface iUmiMimePart {

		public function __construct($sBody, $arrParams);

		public static function quotedPrintableEncode($sData, $iMaxLineSize = 76);

		public function addMixedPart();

		public function addAlternativePart();

		public function addRelatedPart();

		public function addTextPart($sText);

		public function addHtmlPart($sHtml);

		public function addHtmlImagePart($arrImgData);

		public function addAttachmentPart($arrAttachmentData);

		public function encodePart();
	}
