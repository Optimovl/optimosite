<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Интерфейс источника ссылки
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	interface iSource extends \iUmiCollectionItem {

		/**
		 * Устанавливает идентификатор ссылки
		 * @see \UmiCms\Classes\System\Utils\Links\Entity
		 * @param int $linkId идентификатор ссылки
		 * @return iSource
		 */
		public function setLinkId($linkId);

		/**
		 * Возвращает идентификатор ссылки
		 * @see \UmiCms\Classes\System\Utils\Links\Entity
		 * @return int
		 */
		public function getLinkId();

		/**
		 * Устанавливает место
		 * @param string $place место (адрес шаблона или объекта)
		 * @return iSource
		 */
		public function setPlace($place);

		/**
		 * Возвращает место
		 * @return string
		 */
		public function getPlace();

		/**
		 * Устанавливает тип
		 * @see \UmiCms\Classes\System\Utils\Links\SourceTypes
		 * @param string $type тип
		 * @return iSource
		 */
		public function setType($type);

		/**
		 * Возвращает тип
		 * @return string
		 */
		public function getType();
	}
