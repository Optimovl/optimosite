<?php

	use UmiCms\Service;
	use UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory\Injector as SourceIdBinderInjector;

	/** Коллекция переменных шаблонов для писем */
	class MailVariablesCollection implements
		iUmiDataBaseInjector,
		iUmiService,
		iUmiCollection,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tUmiService;
		use tCommonCollection;
		use tUmiConstantMapInjector;
		use tClassConfigManager;
		use SourceIdBinderInjector;

		/** @var string класс элементов коллекции */
		private $collectionItemClass = 'MailVariable';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'MailVariables',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false
				],
				[
					'name' => 'TEMPLATE_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'VARIABLE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
			],
		];

		/**
		 * @inheritdoc
		 * @throws Exception
		 */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/**
		 * Создает переменную в шаблоне уведомлений
		 * @param string $name имя переменной
		 * @param int $templateId идентификатор шаблона
		 * @return iUmiCollectionItem
		 * @throws Exception
		 */
		public function createVariable($name, $templateId) {
			$map = $this->getMap();
			return $this->create([
				$map->get('VARIABLE_FIELD_NAME') => $name,
				$map->get('TEMPLATE_ID_FIELD_NAME') => $templateId,
			]);
		}

		/**
		 * Удаляет переменную в шаблоне уведомлений
		 * @param string $name имя переменной
		 * @param int $templateId идентификатор шаблона
		 * @return bool
		 * @throws Exception
		 */
		public function deleteVariable($name, $templateId) {
			$map = $this->getMap();
			return $this->delete([
				$map->get('VARIABLE_FIELD_NAME') => $name,
				$map->get('TEMPLATE_ID_FIELD_NAME') => $templateId,
			]);
		}

		/**
		 * Возвращает все переменные шаблона уведомления
		 * @param int $id идентификатор шаблона уведомления
		 * @return iUmiCollectionItem[]|MailVariable[]
		 * @throws Exception
		 */
		public function getByTemplateId($id) {
			return $this->get([
				$this->getMap()->get('TEMPLATE_ID_FIELD_NAME') => $id
			]);
		}

		/**
		 * @inheritdoc
		 * @throws Exception
		 */
		public function updateRelatedId(array $properties, $sourceId) {

			if (isset($properties['template_id'])) {
				$entityRelations = $this->getSourceIdBinderFactory()
					->create($sourceId);

				/** @var MailTemplatesCollection $mailTemplates */
				$mailTemplates = Service::get('MailTemplates');
				$table = $mailTemplates->getMap()
					->get('EXCHANGE_RELATION_TABLE_NAME');

				$oldGroupId = $properties['template_id'];
				$newGroupId = $entityRelations->getInternalId($oldGroupId, $table);

				if ($newGroupId !== null) {
					$properties['template_id'] = $newGroupId;
				}
			}

			return $properties;
		}
	}
