<?php

	class libXMLErrorException extends coreException {

		public function __construct($libXMLError, $code = 0, $stringCode = '') {
			$this->code = $libXMLError->code;
			$this->message = $libXMLError->message;
			$this->line = $libXMLError->line;
			$this->file = $libXMLError->file;
		}
	}
