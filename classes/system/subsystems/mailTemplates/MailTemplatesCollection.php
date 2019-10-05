<?php

	use UmiCms\Service;
	use UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory\Injector as SourceIdBinderInjector;

	/** Коллекция шаблонов для писем */
	class MailTemplatesCollection implements
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
		private $collectionItemClass = 'MailTemplate';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'MailTemplates',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false
				],
				[
					'name' => 'NOTIFICATION_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TYPE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'CONTENT_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				]
			],
			'create-prepare-instancing-callback' => 'unEscapeStringValues'
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
		 * Обработчик метода tCommonCollection::create()#create-prepare-instancing-callback.
		 * Подменяет экранированные строковые данные оригинальными
		 * @param array $fields имена полей
		 * @param array $values значения полей
		 * @param array $fieldsConfig настройки полей
		 * @param array $params оригинальные значения полей
		 * @return array
		 * @throws Exception
		 */
		public function unEscapeStringValues(array $fields, array $values, array $fieldsConfig, array $params) {
			$map = $this->getMap();

			foreach ($fieldsConfig as $fieldConfig) {
				$fieldType = $map->get($fieldConfig['type']);
				$fieldName = $map->get($fieldConfig['name']);

				if ($fieldType === $map->get('STRING_FIELD_TYPE') && isset($values[$fieldName]) &&
					isset($params[$fieldName])) {
					$values[$fieldName] = $params[$fieldName];
				}
			}

			return $values;
		}

		/**
		 * Создает шаблон для писем
		 * @param string $name имя шаблона
		 * @param string $content содержимое шаблона
		 * @return iUmiCollectionItem|MailTemplate
		 * @throws Exception
		 */
		public function createTemplate($name, $content) {
			$map = $this->getMap();
			return $this->create([
				$map->get('NAME_FIELD_NAME') => $name,
				$map->get('CONTENT_FIELD_NAME') => $content,
			]);
		}

		/**
		 * Возвращает шаблон по его имени. Если шаблон с указанным именем не существует - создает его.
		 * @param string $name имя шаблона
		 * @param string $content содержимое шаблона
		 * @return iUmiCollectionItem|MailTemplate|null
		 * @throws Exception
		 */
		public function getCreateTemplate($name, $content = '') {
			$template = $this->getByName($name);
			$itemClass = $this->getCollectionItemClass();

			if ($template instanceof $itemClass) {
				return $template;
			}

			return $this->createTemplate($name, $content);
		}

		/**
		 * Возвращает шаблон по его имени
		 * @param string $name имя шаблона
		 * @return MailTemplate|null
		 * @throws Exception
		 */
		public function getByName($name) {
			return $this->getBy($this->getMap()->get('NAME_FIELD_NAME'), $name);
		}

		/**
		 * Возвращает все шаблоны уведомления
		 * @param int $id идентификатор уведомления
		 * @return iUmiCollectionItem[]|MailTemplate[]
		 * @throws Exception
		 */
		public function getByNotificationId($id) {
			return $this->get([
				$this->getMap()->get('NOTIFICATION_ID_FIELD_NAME') => $id
			]);
		}

		/**
		 * @inheritdoc
		 * @throws Exception
		 */
		public function updateRelatedId(array $properties, $sourceId) {

			if (isset($properties['notification_id'])) {
				$entityRelations = $this->getSourceIdBinderFactory()
					->create($sourceId);

				/** @var MailNotificationsCollection $mailNotifications */
				$mailNotifications = Service::get('MailNotifications');
				$table = $mailNotifications->getMap()
					->get('EXCHANGE_RELATION_TABLE_NAME');

				$oldGroupId = $properties['notification_id'];
				$newGroupId = $entityRelations->getInternalId($oldGroupId, $table);

				if ($newGroupId !== null) {
					$properties['notification_id'] = $newGroupId;
				}
			}

			return $properties;
		}
	}
