<?php

	/** Класс-обертка для внутреннего представления типа данных "Дата" */
	class umiDate implements iUmiDate {

		public $timestamp;

		public static $defaultFormatString = DEFAULT_DATE_FORMAT;

		/**
		 * Конструктор
		 * @param int|bool $timestamp Количество секунд с начала эпохи Unix (TimeStamp)
		 */
		public function __construct($timestamp = false) {
			if ($timestamp === false) {
				$timestamp = $this->getCurrentTimeStamp();
			}
			$this->setDateByTimeStamp($timestamp);
		}

		/**
		 * Возвращяет текущий Time Stamp
		 * @return Int Time Stamp
		 */
		public function getCurrentTimeStamp() {
			if (isset($_SERVER['REQUEST_TIME'])) {
				return $_SERVER['REQUEST_TIME'];
			}

			return time();
		}

		/**
		 * Возвращает Time Stamp для сохраненной даты
		 * @return Int Time Stamp
		 */
		public function getDateTimeStamp() {
			return (int) $this->timestamp;
		}

		/**
		 * Возвращает сохраненную дату в отформатированом виде
		 * @param string|bool $formatString Форматная строка (см. описание функции date на php.net)
		 * @return string отформатированная дата
		 */
		public function getFormattedDate($formatString = false) {
			if ($formatString === false) {
				$formatString = self::$defaultFormatString;
			}
			return date($formatString, $this->timestamp);
		}

		/**
		 * Устанавливает дату по Time Stamp
		 * @param int $timestamp Time Stamp желаемой даты
		 * @return bool false - если $timestamp не число, true - в противном случае
		 */
		public function setDateByTimeStamp($timestamp) {
			if (!is_numeric($timestamp) || $timestamp === 0) {
				return false;
			}
			$this->timestamp = (int) $timestamp;
			return true;
		}

		/**
		 * Устанавливает дату по переданой строке
		 * @param string $dateString Строка с датой
		 * @return bool true - если переданная строка может быть интерпретирована, как дата, false - в противном случае
		 */
		public function setDateByString($dateString) {
			$dateString = umiObjectProperty::filterInputString($dateString);
			$timestamp = $dateString !== '' ? self::getTimeStamp($dateString) : 0;
			return $this->setDateByTimeStamp($timestamp);
		}

		/**
		 * Преобразует строку с датой в Time Stamp
		 * @param string $dateString Строка с датой
		 * @return Int Time Stamp
		 */
		public static function getTimeStamp($dateString) {
			return toTimeStamp($dateString);
		}

		public function __toString() {
			return $this->getFormattedDate();
		}
	}

