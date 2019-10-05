<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Интерфейс коллекции источников ссылок
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	interface iSourcesCollection extends \iUmiCollection {

		/**
		 * Создает источник ссылки по идентификатору ссылки и месту источника
		 * @param int $linkId идентификатор ссылки @see UmiCms\Classes\System\Utils\Links\Entity
		 * @param string $place место источника (адрес шаблона или объекта)
		 * @return iSource
		 */
		public function createByLinkIdAndPlace($linkId, $place);

		/**
		 * Экспортирует источники ссылки по ее идентификатору
		 * @param int $linkId идентификатор ссылки @see UmiCms\Classes\System\Utils\Links\Entity
		 * @return array
		 */
		public function exportByLinkId($linkId);
	}
