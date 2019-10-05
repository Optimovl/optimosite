<?php

	use UmiCms\Service;

	abstract class baseSerialize implements iBaseSerialize {

		public static $called = [];

		final public static function serializeDocument($type, $buffer, $params) {
			$serializer = self::loadSerializer($type);
			return $serializer->execute($buffer, $params);
		}

		abstract public function execute($xmlString, $params);

		protected static function loadSerializer($type) {
			$filename = SYS_KERNEL_PATH . "subsystems/matches/serializes/{$type}/{$type}.php";
			if (is_file($filename)) {
				require $filename;

				$serializerClassName = mb_strtolower($type) . 'Serialize';

				if (class_exists($serializerClassName)) {
					return new $serializerClassName();
				}

				throw new coreException("Class {$serializerClassName} doesn't exsits");
			}

			throw new coreException("Can't load serializer of type \"{$type}\"");
		}

		protected function sendHTTPHeaders($params) {
			if (!is_array($params)) {
				throw new coreException('First argument must be an array in sendHTTPHeaders()');
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$headers = getArrayKey($params, 'headers');

			if (is_array($headers)) {
				foreach ($headers as $name => $value) {
					$value = (string) $value;

					if (mb_strtolower($name) == 'content-type') {
						$buffer->contentType($value);
						continue;
					}

					$buffer->setHeader($name, $value);
				}
			}
		}
	}
