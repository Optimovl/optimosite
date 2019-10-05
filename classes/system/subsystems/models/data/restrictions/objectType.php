<?php

	class objectTypeRestriction extends baseRestriction {

		protected $errorMessage = 'restriction-error-object-type';

		public function validate($value, $objectId = false) {
			$value = (string) $value;
			return $value !== '' ? (selector::get('object-type')->id($value) instanceof iUmiObjectType) : true;
		}
	}

