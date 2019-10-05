<?php
	namespace UmiCms\System\Orm\Entity;

	/**
	 * Интерфейс схемы хранения сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iSchema {

		/**
		 * Конструктор
		 * @param iAccessor $accessor аксессор связей сущности
		 */
		public function __construct(iAccessor $accessor);

		/**
		 * Возвращает имя таблицы контейнера
		 * @return string
		 * @throws \ReflectionException
		 */
		public function getContainerName();

		/**
		 * Возвращает имя таблицы связей для обмена данными
		 * @return string
		 * @throws \ReflectionException
		 */
		public function getExchangeName();

		/**
		 * Возвращает список полей сущности, связывающих ее с другими сущностями
		 * @return string[]
		 * @throws \ReflectionException
		 */
		public function getRelationFieldList();

		/**
		 * Возвращает список имен таблиц контейнеров связанных сущностей
		 * @return string[]
		 * @throws \ReflectionException
		 */
		public function getRelatedContainerNameList();

		/**
		 * Возвращает список имен таблиц связей для обмена данными связанных сущностей
		 * @return string[]
		 * @throws \ReflectionException
		 */
		public function getRelatedExchangeNameList();
	}