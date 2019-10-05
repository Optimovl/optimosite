<?php

	use UmiCms\Service;

	class xmlSerialize extends baseSerialize {

		public function execute($xmlString, $params) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');

			$this->sendHTTPHeaders($params);

			$buffer->push($xmlString);
			$buffer->end();
		}
	}
