<?php
	namespace UmiCms\System\Protection;

	/**
	 * Интерфейс сравнителя хэшей
	 * @package UmiCms\System\Protection
	 */
	interface iHashComparator {

		/**
		 * Сравнивает два хэша на идентичность
		 * @param string $knownHash хэш, с которым будет производиться сравнение
		 * @param string $userHash проверяемый хэш
		 * @return bool
		 */
		public function equals($knownHash, $userHash);
	}