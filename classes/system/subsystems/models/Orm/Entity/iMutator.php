<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;

	/**
	 * Интерфейс мутатора свойств сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iMutator {

		/**
		 * Конструктор
		 * @param iMapper $mapper маппер сущности
		 */
		public function __construct(iMapper $mapper);

		/**
		 * Изменяет значение свойства сущности
		 * @param iEntity $entity сущность
		 * @param string $name имя свойства
		 * @param mixed $value значение
		 * @return iEntity
		 * @throws \ErrorException
		 */
		public function mutate(iEntity $entity, $name, $value);

		/**
		 * Изменяет список значений свойств сущности
		 * @param iEntity $entity сущность
		 * @param array $mutationMap карта изменений
		 * @example
		 *
		 * [
		 * 		'name' => 'Foo'
		 * ]
		 *
		 * @return iEntity
		 * @throws \ErrorException
		 */
		public function mutateList(iEntity $entity, array $mutationMap);

		/**
		 * Возвращает список имен свойств
		 * @return string[]
		 */
		public function getPropertyList();
	}