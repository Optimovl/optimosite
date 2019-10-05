<?php

	namespace UmiCms\System\Cache\Key\Validator;

	use UmiCms\System\Cache\Key\Validator;

	/**
	 * Валидатор ключей кеша по черном списку
	 * @package UmiCms\System\Cache\Key\Validator
	 */
	class BlackList extends Validator {

		/** @inheritdoc */
		public function isValid($key) {
			return !$this->isOnBlackList($key);
		}
	}
