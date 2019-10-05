<?php

	class xsltOnlyException extends publicException {

		public function __construct($message = '', $code = 0, $stringCode = '') {
			parent::__construct(getLabel('error-only-xslt-method'));
		}
	}
