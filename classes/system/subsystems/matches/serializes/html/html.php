<?php

	use UmiCms\Service;

	class htmlSerialize extends baseSerialize {

		const signature = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

		public function execute($xmlString, $params) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');

			$this->sendHTTPHeaders($params);

			$buffer->push($this->removeSignature($xmlString));
			$buffer->end();
		}

		private function removeSignature($str) {
			$l = mb_strlen(self::signature);

			if (mb_substr($str, 0, $l) === self::signature) {
				return mb_substr($str, $l, mb_strlen($str) - $l);
			}

			return $str;
		}
	}
