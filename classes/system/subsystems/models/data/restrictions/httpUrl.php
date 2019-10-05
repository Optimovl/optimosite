<?php

	class httpUrlRestriction extends baseRestriction implements iNormalizeInRestriction {

		public function validate($value, $objectId = false) {
			$value = (string) $value;
			return $value === '' || preg_match("/^(https?:\/\/)?([A-z\.]+)/", $value);
		}

		public function normalizeIn($value, $objectId = false) {
			$value = (string) $value;

			if ($value !== '' && !preg_match("/^https?:\/\//", $value)) {
				$value = getSelectedServerProtocol() . '://' . $value;
			}

			return $value;
		}
	}

