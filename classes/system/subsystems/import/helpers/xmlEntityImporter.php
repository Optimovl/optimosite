<?php

	use UmiCms\Service;
	use UmiCms\System\Orm\Entity\iExchange;
	use UmiCms\System\Import\UmiDump\Entity\iBaseImporter;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/** Класс базового импортера сущностей */
	class xmlEntityImporter implements iBaseImporter {

		/** @var DOMXPath объект для осуществления XPath-запросов */
		private $parser;

		/**
		 * @var iSourceIdBinder $relations экземпляр класса для создания связей импортируемых сущностей
		 * с уже существующими в системе сущностями
		 */
		private $relations;

		/** @var array $result результат импорта */
		private $result;

		/** @var int|null $forcedDomainId принудительный идентификатор домена @see setForcedDomainId() */
		private $forcedDomainId;

		/** @var callback $changeFilePathCallback функция обратного вызова для изменения путей файлов */
		private $changeFilePathCallback;

		/** @inheritdoc */
		public function __construct(DOMXPath $parser, iSourceIdBinder $relations) {
			$this->parser = $parser;
			$this->relations = $relations;
			$this->result = [
				'created' => 0,
				'updated' => 0,
				'errors' => [],
				'log' => [],
			];
		}

		/** @inheritdoc */
		public function import() {
			/** @var array $nodeList */
			$nodeList = $this->parser->evaluate('/umidump/entities/entity');

			foreach ($nodeList as $entityNode) {
				try {
					$this->importEntity($entityNode);
				} catch (Exception $e) {
					$this->logError($e->getMessage());
				}
			}

			return $this->getResult();
		}

		/** @inheritdoc */
		public function setForcedDomainId($id) {
			$this->forcedDomainId = $id;
			return $this;
		}

		/** @inheritdoc */
		public function setChangeFilePathCallback(callable $callback) {
			$this->changeFilePathCallback = $callback;
			return $this;
		}

		/** @inheritdoc */
		public function getSourceIdBinder() {
			return $this->relations;
		}

		/** @inheritdoc */
		public function logCreated($id) {
			$this->result['log'][] = getLabel('label-entity') . " ({$id}) " . getLabel('label-has-been-created-f');
			$this->result['created'] += 1;
			return $this;
		}

		/** @inheritdoc */
		public function logUpdated($id) {
			$this->result['log'][] = getLabel('label-entity') . " ({$id}) " . getLabel('label-has-been-updated-f');
			$this->result['updated'] += 1;
			return $this;
		}

		/** @inheritdoc */
		public function logError($message) {
			$this->result['errors'][] = $message;
			return $this;
		}

		/** @inheritdoc */
		public function getResult() {
			return $this->result;
		}

		/**
		 * Импортирует отдельную сущность
		 * @param DOMElement $entityNode узел сущности
		 * @return $this
		 * @throws importException
		 * @throws databaseException
		 * @throws Exception
		 */
		private function importEntity(DOMElement $entityNode) {
			$id = $this->getRequiredAttribute($entityNode, 'id');
			$serviceName = $this->getRequiredAttribute($entityNode, 'service');
			$module = $entityNode->getAttribute('module');

			if (is_string($module) && !empty($module)) {
				cmsController::getInstance()
					->getModule($module);
			}

			$service = $this->getService($serviceName);
			$properties = $this->getEntityProperties($entityNode);
			$installOnly = $entityNode->getAttribute('install-only');

			if ($service instanceof iExchange) {
				$properties['id'] = $id;
				$properties[self::INSTALL_ONLY_FLAG] = $installOnly;
				/** @var iExchange $service */
				$service->importOne($properties, $this);
				return $this;
			}

			/** @var iUmiCollection|iUmiConstantMapInjector $service */
			$table = $service->getMap()->get('EXCHANGE_RELATION_TABLE_NAME');
			$entityId = $this->getEntityId($id, $table);
			$properties = $service->updateRelatedId($properties, $this->getSourceIdBinder()->getSourceId());
			$forcedDomainId = $this->getForcedDomainId();
			$changeFilePathCallback = $this->getChangeFilePathCallback();

			foreach ($properties as $key => $value) {
				if ($key === 'domain_id' && $forcedDomainId) {
					$properties[$key] = $forcedDomainId;
				}

				if ($key === 'image' && is_callable($changeFilePathCallback)) {
					$properties[$key] = call_user_func_array($changeFilePathCallback, [$value]);
				}
			}

			if ($entityId) {
				if ($installOnly) {
					return $this;
				}

				$properties['id'] = $entityId;
				$this->update($id, $properties, $service);
			} else {
				$this->create($id, $properties, $service);
			}

			return $this;
		}

		/**
		 * Возвращает значение обязательного атрибута сущности
		 * @param DOMElement $entityNode узел сущности
		 * @param string $key название атрибута
		 * @return mixed
		 * @throws importException
		 */
		private function getRequiredAttribute($entityNode, $key) {
			$attribute = $entityNode->getAttribute($key);
			if (!$attribute) {
				throw new importException(getLabel('error-no-entity-attribute', false, $key));
			}
			return $attribute;
		}

		/**
		 * Возвращает сервис, отвественный за импорт сущности
		 * @param string $name название сервиса
		 * @return iUmiCollection|iUmiConstantMapInjector|iExchange
		 * @throws Exception
		 */
		private function getService($name) {
			return Service::get($name);
		}

		/**
		 * Возвращает идентификатор уже существующей в системе сущности
		 * @param string $id идентификатор импортируемой сущности
		 * @param string $table название таблицы связей
		 * @return mixed
		 * @throws databaseException
		 */
		private function getEntityId($id, $table) {
			return $this->getSourceIdBinder()->getInternalId($id, $table);
		}

		/**
		 * Обновляет свойства сущности с id, указанном в массиве свойств.
		 * Если сущность с таким id не будет найдена, система создаст новую сущность.
		 * @param string $id идентификатор импортируемой сущности, указанный в файле импорта
		 * @param array $properties свойства сущности
		 * @param iUmiCollection|iUmiConstantMapInjector $collection коллекция сущностей
		 */
		private function update($id, $properties, $collection) {
			$result = $collection->import([$properties]);
			$map = $collection->getMap();
			$createdKey = $map->get('CREATED_COUNTER_KEY');
			$updatedKey = $map->get('UPDATED_COUNTER_KEY');
			$errorsKey = $map->get('IMPORT_ERRORS_KEY');

			if ($result[$createdKey]) {
				$this->logCreated($id);
			} elseif ($result[$updatedKey]) {
				$this->logUpdated($id);
			}

			/** @var array $errorList */
			$errorList = $result[$errorsKey];

			foreach ($errorList as $errorMessage) {
				$this->logError($errorMessage);
			}
		}

		/**
		 * Создает новую сущность с указанными свойствами
		 * @param string $id идентификатор импортируемой сущности, указанный в файле импорта
		 * @param array $properties свойства сущности
		 * @param iUmiCollection|iUmiConstantMapInjector $collection коллекция сущностей
		 * @throws databaseException
		 */
		private function create($id, $properties, $collection) {
			$entity = $collection->create($properties);
			$table = $collection->getMap()->get('EXCHANGE_RELATION_TABLE_NAME');
			$this->getSourceIdBinder()->defineRelation($id, $entity->getId(), $table);
			$this->logCreated($id);
		}

		/**
		 * Возвращает все свойства сущности в формате [name => value, ...]
		 * Формат импортируемой сущности:
		 *
		 * <entity id="1" service="redirects">
		 *   <source>test-source</source>
		 *   <target>test-target</target>
		 *   <status>301</status>
		 *   <made_by_user>1</made_by_user>
		 * </entity>
		 *
		 * @param DOMElement $entityNode узел сущности
		 * @return array
		 */
		private function getEntityProperties(DOMElement $entityNode) {
			$propertyList = [];
			$propertyNodeList = $entityNode->childNodes;

			foreach ($propertyNodeList as $propertyNode) {
				if (!$propertyNode instanceof DOMElement) {
					continue;
				}

				$value = ($propertyNode->nodeValue === '') ? null : $propertyNode->nodeValue;
				$propertyList[$propertyNode->tagName] = $value;
			}

			return $propertyList;
		}

		/**
		 * Возвращает идентификатор принудительного домена
		 * @return int|null
		 */
		private function getForcedDomainId() {
			return $this->forcedDomainId;
		}

		/**
		 * Возвращает функцию обратного вызова для изменения путей файлов
		 * @return callable|null
		 */
		private function getChangeFilePathCallback() {
			return $this->changeFilePathCallback;
		}
	}
