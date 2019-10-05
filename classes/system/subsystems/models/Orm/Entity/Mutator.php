<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\Mapper\tInjector as tMapperInjector;

	/**
	 * Абстрактный класс мутатора свойств сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Mutator implements iMutator {

		use tMapperInjector;

		/** @inheritdoc */
		public function __construct(iMapper $mapper) {
			$this->setMapper($mapper);
		}

		/** @inheritdoc */
		public function mutate(iEntity $entity, $name, $value) {
			$schema = $this->getSchema($name);
			return $this->mutateBySchema($entity, $schema, $value);
		}

		/** @inheritdoc */
		public function mutateList(iEntity $entity, array $mutationMap) {

			foreach ($this->getSchemaList() as $name => $schema) {

				if (!isset($mutationMap[$name])) {
					continue;
				}

				$this->mutate($entity, $name, $mutationMap[$name]);
			}

			return $entity;
		}

		/** @inheritdoc */
		public function getPropertyList() {
			return array_keys($this->getSchemaList());
		}

		/**
		 * Возвращает схему доступа к свойству
		 * @param string $name имя свойства
		 * @return array
		 * @throws \ErrorException
		 */
		abstract protected function getSchema($name);

		/**
		 * Возвращает список схем доступа
		 * @return array
		 */
		abstract protected function getSchemaList();

		/**
		 * Изменяет значение свойства по его схеме доступа
		 * @param iEntity $entity сущность
		 * @param array $schema схема доступа свойства
		 * @param mixed $value значение свойства
		 * @return iEntity
		 */
		abstract protected function mutateBySchema(iEntity $entity, array $schema, $value);
	}