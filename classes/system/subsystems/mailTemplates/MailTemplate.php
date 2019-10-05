<?php

	use UmiCms\Service;
	use UmiCms\System\MailTemplates\Parser;

	/** Шаблон письма */
	class MailTemplate implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var int ID уведомления, которому принадлежит шаблон */
		private $notificationId;

		/** @var string имя шаблона */
		private $name;

		/** @var string тип шаблона */
		private $type;

		/** @var string содержимое шаблона */
		private $content;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId'
				],
				[
					'name' => 'NOTIFICATION_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setNotificationId',
					'getter' => 'getNotificationId'
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName'
				],
				[
					'name' => 'TYPE_FIELD_NAME',
					'required' => true,
					'setter' => 'setType',
					'getter' => 'getType'
				],
				[
					'name' => 'CONTENT_FIELD_NAME',
					'required' => true,
					'setter' => 'setContent',
					'getter' => 'getContent'
				],
			]
		];

		/**
		 * Возвращает ID уведомления, которому принадлежит шаблон
		 * @return int
		 */
		public function getNotificationId() {
			return $this->notificationId;
		}

		/**
		 * Устанавливает ID уведомления, которому принадлежит шаблон
		 * @param int $id новый ID уведомления
		 */
		public function setNotificationId($id) {
			$this->setDifferentValue('notificationId', $id, 'int');
		}

		/**
		 * Возвращает имя шаблона
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * Устанавливает имя шаблона
		 * @param string $name новое имя шаблона
		 */
		public function setName($name) {
			$this->setDifferentValue('name', $name, 'string');
		}

		/**
		 * Возвращает тип шаблона
		 * @return string
		 */
		public function getType() {
			return $this->type;
		}

		/**
		 * Устанавливает имя шаблона
		 * @param string $type новое имя шаблона
		 */
		public function setType($type) {
			$this->setDifferentValue('type', $type, 'string');
		}

		/**
		 * Возвращает содержимое шаблона
		 * @return string
		 */
		public function getContent() {
			return $this->content;
		}

		/**
		 * Устанавливает содержимое шаблона
		 * @param string $content новое содержимое шаблона
		 */
		public function setContent($content) {
			$this->setDifferentValue('content', $content, 'string');
		}

		/**
		 * Возвращает модуль шаблона
		 * @return mixed
		 * @throws Exception
		 */
		public function getModule() {
			return $this->getNotification()->getModule();
		}

		/**
		 * Возвращает уведомление шаблона
		 * @return MailNotification|false
		 * @throws Exception
		 */
		public function getNotification() {
			$mailNotifications = Service::MailNotifications();
			$map = $mailNotifications->getMap();
			$result = $mailNotifications->get([
				$map->get('ID_FIELD_NAME') => $this->getNotificationId()
			]);

			if (umiCount($result) > 0) {
				return array_shift($result);
			}

			return false;
		}

		/**
		 * Добавляет переменную в переменные шаблона
		 * @param string $variable идентификатор переменной
		 * @return false|iUmiCollectionItem
		 * @throws Exception
		 */
		public function addVariable($variable) {
			if ($this->isVariableExist($variable)) {
				return false;
			}

			return $this->getMailVariablesCollection()
				->createVariable($variable, $this->getId());
		}

		/**
		 * Удаляет переменную из переменных шаблона
		 * @param string $variable идентификатор переменной
		 * @return bool
		 * @throws Exception
		 */
		public function deleteVariable($variable) {
			return $this->getMailVariablesCollection()
				->deleteVariable($variable, $this->getId());
		}

		/**
		 * Возвращает список переменных шаблона
		 * @return string[]
		 * @throws Exception
		 */
		public function getVariableList() {
			$variablesObjectList = $this->getMailVariablesCollection()
				->getByTemplateId($this->getId());

			$variableList = [];

			foreach ($variablesObjectList as $variable) {
				$variableList[] = $variable->getName();
			}

			return $variableList;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws Exception
		 */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));

			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$notificationIdField = $connection->escape($map->get('NOTIFICATION_ID_FIELD_NAME'));
			$nameField = $connection->escape($map->get('NAME_FIELD_NAME'));
			$typeField = $connection->escape($map->get('TYPE_FIELD_NAME'));
			$contentField = $connection->escape($map->get('CONTENT_FIELD_NAME'));

			$id = $this->getId();
			$notificationId = $connection->escape($this->getNotificationId());
			$type = $connection->escape($this->getType());
			$name = $connection->escape($this->getName());
			$content = $connection->escape($this->getContent());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$notificationIdField` = '$notificationId', `$nameField` = '$name', 
		`$typeField` = '$type', `$contentField` = '$content'
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}

		/**
		 * Возвращает массив с идентифкатором для связанных типов
		 * @return array
		 */
		public function getVariableForRelatedTypeList() {
			return [
				'umi-customer' => [
					'users-user', 'emarket-customer'
				]
			];
		}

		/**
		 * Возвращает обработанное содержимое шаблона, такое, что в нем (содержимом)
		 * заменены вставки идентификаторов полей на конкретные значения.
		 * В шаблоне могут содержаться вложенные шаблоны.
		 *
		 * @param array $params массив идентификаторов полей и их значений
		 * @param iUmiObject[] $objectList Объекты, из которых могут подставляться значения в шаблон
		 * @return mixed
		 * @throws Exception
		 */
		public function parse(array $params = [], array $objectList = []) {
			return (new Parser($this))->parse($params, $objectList);
		}

		/**
		 * Возвращает коллекцию переменных
		 * @return MailVariablesCollection|mixed
		 */
		private function getMailVariablesCollection() {
			return Service::MailVariables();
		}

		/**
		 * Проверяет существует ли переменная в шаблоне
		 * @param string $variable
		 * @return bool
		 * @throws Exception
		 */
		private function isVariableExist($variable) {
			return in_array($variable, $this->getVariableList());
		}

		/**
		 * @deprecated
		 * @see MailTemplate::parse()
		 * @param array $params
		 * @return mixed
		 * @throws Exception
		 */
		public function getProcessedContent(array $params = []) {
			return $this->parse($params);
		}
	}
