<?php

	namespace UmiCms\System\Cache\Key\Validator;

	use UmiCms\System\Cache\Key\Validator;

	/**
	 * Валидатор ключей кеша по белому списку
	 * @package UmiCms\System\Cache\Key\Validator
	 */
	class WhiteList extends Validator {

		/** @inheritdoc */
		public function isValid($key) {
			return $this->isOnWhiteList($key);
		}
	}
