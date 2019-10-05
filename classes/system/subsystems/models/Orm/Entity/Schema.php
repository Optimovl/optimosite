<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\Entity\Relation\Accessor\tInjector as tRelationAccessorInjector;

	/**
	 * Абстрактный класс схемы хранения сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Schema implements iSchema {

		use tRelationAccessorInjector;

		/** @var string CONTAINER_PREFIX префикс имени таблицы контейнера */
		const CONTAINER_PREFIX = 'cms3';

		/** @var string EXCHANGE_PREFIX префикс имени таблицы связей обмена данными */
		const EXCHANGE_PREFIX = 'cms3_import';

		/** @var string POSTFIX постфикс имени таблиц */
		const POSTFIX = 'list';

		/** @var string SEPARATOR разделитель в именах таблиц */
		const SEPARATOR = '_';

		/** @inheritdoc */
		public function __construct(iAccessor $accessor) {
			$this->setRelationAccessor($accessor);
		}

		/** @inheritdoc */
		public function getContainerName() {
			return $this->buildName(self::CONTAINER_PREFIX);
		}

		/** @inheritdoc */
		public function getExchangeName() {
			return $this->buildName(self::EXCHANGE_PREFIX);
		}

		/** @inheritdoc */
		public function getRelationFieldList() {
			$propertyList = array_filter($this->getRelationAccessor()->getPropertyList(), function($field) {
				return !contains($field, 'collection');
			});

			$suffix = $this->getRelationFieldSuffix();
			$fieldList = [];

			foreach ($propertyList as $index => $property) {
				$field = sprintf('%s%s', $property, $suffix);
				$fieldList[$field] = $field;
			}

			return $fieldList;
		}

		/** @inheritdoc */
		public function getRelatedContainerNameList() {
			$originalList = $this->buildRelatedNameList(self::CONTAINER_PREFIX);
			$customList = $this->getRelatedContainerCustomNameList();
			return array_merge($originalList, $customList);
		}

		/** @inheritdoc */
		public function getRelatedExchangeNameList() {
			$originalList = $this->buildRelatedNameList(self::EXCHANGE_PREFIX);
			$customList = $this->getRelatedExchangeCustomNameList();
			return array_merge($originalList, $customList);
		}

		/**
		 * Возвращает список пользовательских имен таблиц контейнеров связанных сущностей
		 * @return array
		 */
		protected function getRelatedContainerCustomNameList() {
			return [];
		}

		/**
		 * Возвращает список имен таблиц связей для обмена данными связанных сущностей
		 * @return array
		 */
		protected function getRelatedExchangeCustomNameList() {
			return [];
		}

		/**
		 * Возвращает корневое пространство имен сущности
		 * @return string
		 */
		abstract protected function getNameSpaceRoot();

		/**
		 * Возврашает суффик имени связующего поля
		 * @return string
		 */
		protected function getRelationFieldSuffix() {
			return sprintf('%sid', self::SEPARATOR, 'id');
		}

		/**
		 * Возвращает пространство имени сущности
		 * @return string
		 * @throws \ReflectionException
		 */
		protected function getNameSpace() {
			$reflection = new \ReflectionClass($this);
			return $reflection->getNamespaceName();
		}

		/**
		 * Формирует список имен таблиц связанных сущностей
		 * @param string $prefix префикс имени таблицы
		 * @return string[]
		 * @throws \ReflectionException
		 */
		protected function buildRelatedNameList($prefix) {
			$suffix = $this->getRelationFieldSuffix();
			$containerNameList = [];

			foreach ($this->getRelationFieldList() as $field) {
				$nameList = $this->getNameSpacePartList();

				$relatedClass =  str_replace($suffix, '', $field);

				if (in_array($relatedClass, $nameList)) {
					$nameList = [];
				}

				$nameList[] = str_replace($suffix, '', $field);
				$containerName = $this->buildName($prefix, $nameList);
				$containerNameList[$field] = $containerName;
			}

			return $containerNameList;
		}

		/**
		 * Формирует имя таблицы
		 * @param string $prefix префикс
		 * @param string[] $namePartList части имени сущности
		 * @return string
		 * @throws \ReflectionException
		 */
		protected function buildName($prefix, array $namePartList = []) {
			$namePartList = (isEmptyArray($namePartList)) ? $this->getNameSpacePartList() : $namePartList;
			$partList = array_merge([$prefix], $namePartList);
			$partList = array_merge($partList, [self::POSTFIX]);
			return implode(self::SEPARATOR, $partList);
		}

		/**
		 * Возвращает части пространства имен
		 * @return string[]
		 * @throws \ReflectionException
		 */
		protected function getNameSpacePartList() {
			$trimmedNamespace = str_replace($this->getNameSpaceRoot(), '', $this->getNameSpace());
			$loverNamespace = mb_convert_case($trimmedNamespace, MB_CASE_LOWER);
			return explode('\\', $loverNamespace);
		}
	}