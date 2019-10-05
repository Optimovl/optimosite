<?php
	namespace UmiCms\System\Orm\Entity;

	/**
	 * Интерфейс маппера сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iMapper {

		/** @var string ID имя столбца в бд для хранения идентификатора */
		const ID = 'id';

		/** @var string ONE_ID_TO_ONE тип связи один идентификатор к одной сущности */
		const ONE_ID_TO_ONE = 'OneIdToOne';

		/** @var string ONE_ID_TO_MANY тип связи один идентификатор к коллекции сущностей */
		const ONE_ID_TO_COLLECTION = 'OneIdToCollection';

		/** @var string ONE_ENTITY_TO_MANY тип связи одна сущность к коллекции сущностей */
		const ONE_ENTITY_TO_COLLECTION = 'OneEntityToCollection';

		/**
		 * Возвращает схему инициализации атрибутов сущности
		 * @return array
		 */
		public function getAttributeSchemaList();

		/**
		 * Возвращает список атрибутов сущности
		 * @return string[]
		 */
		public function getAttributeList();

		/**
		 * Определяет существует ли атрибут
		 * @param string $name
		 * @return bool
		 */
		public function isExistsAttribute($name);

		/**
		 * Возвращает схему инициализации атрибута
		 * @param string $name имя атрибута
		 * @return array
		 * @throws \ErrorException
		 */
		public function getAttributeSchema($name);

		/**
		 * Возвращает схему инициализации связей
		 * @return array
		 */
		public function getRelationSchemaList();

		/**
		 * Возвращает список связей
		 * @return string[]
		 */
		public function getRelationList();

		/**
		 * Определяет существует ли связь
		 * @param string $name
		 * @return bool
		 */
		public function isExistsRelation($name);

		/**
		 * Возвращает схему инициализации связи
		 * @param string $name имя связи
		 * @return array
		 * @throws \ErrorException
		 */
		public function getRelationSchema($name);
	}