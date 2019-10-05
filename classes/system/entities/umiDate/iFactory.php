<?php

	namespace UmiCms\Classes\System\Entities\Date;

	/**
	 * Интерфейс фабрики дат
	 * @package UmiCms\Classes\System\Entities\Date
	 */
	interface iFactory {

		/**
		 * Создает текущую дату
		 * @return \iUmiDate
		 */
		public function create();

		/**
		 * Создает дату по временной метке
		 * @param int $timeStamp временная метка
		 * @return \iUmiDate
		 * @throws \RuntimeException
		 */
		public function createByTimeStamp($timeStamp);

		/**
		 * Создает дату по строковому представлению даты
		 * @param string $dateString строковое представление даты
		 * @return \iUmiDate
		 * @throws \RuntimeException
		 */
		public function createByDateString($dateString);
	}
