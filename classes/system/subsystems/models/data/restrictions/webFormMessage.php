<?php

	/** Виртуальное поле сообщения вебформы */
	class webFormMessageRestriction extends baseRestriction implements iNormalizeOutRestriction {

		/**
		 * @param mixed $value
		 * @param bool $objectId
		 * @return bool
		 */
		public function validate($value, $objectId = false) {
			return true;
		}

		/**
		 * Возврацает значение для виртуального поля
		 * @param $value
		 * @param bool $objectId
		 * @return array|bool|mixed|string
		 */
		public function normalizeOut($value, $objectId = false) {
			if (!$objectId) {
				return false;
			}

			$object = umiObjectsCollection::getInstance()->getObject($objectId);

			$result = false;
			if ($object instanceof iUmiObject) {
				if (class_exists('webforms')) {
					$wf = new webforms();
					$result = $wf->formatMessage($objectId);
				}
			}
			return $result;
		}

	}
