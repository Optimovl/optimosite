<?php

	namespace UmiCms\System\Cache\Key\Validator;

	use UmiCms\System\Cache\Key\Validator;

	/**
	 * Валидатор ключей кеша по черному и белому списку.
	 * По умолчанию ключ невалиден, проверка по черному списку имеет больший приоритет.
	 * @package UmiCms\System\Cache\Key\Validator
	 */
	class MixedList extends Validator {

		/** @inheritdoc */
		public function isValid($key) {
			if ($this->isOnBlackList($key)) {
				return false;
			}

			if ($this->isOnWhiteList($key)) {
				return true;
			}

			return false;
		}
	}
