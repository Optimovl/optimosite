<?php

	interface iUmiObjectsExpiration {

		/**
		 * Возвращает лимит для выборки просроченных объектов
		 * @return int
		 */
		public function getLimit();

		public function isExpirationExists($objectId);

		public function getExpiredObjectsByTypeId($typeId, $limit = 50);

		public function update($objectId, $expires = false);

		public function add($objectId, $expires = false);

		public function clear($objectId);
	}
