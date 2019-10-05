<?php

	namespace UmiCms\Classes\System\Utils\Stub\Settings;

	/**
	 * Абстрактный класс настроек заглушки
	 * @package UmiCms\Classes\System\Utils\Stub\Settings
	 */
	abstract class Settings implements iSettings, \iUmiRegistryInjector{

		use \tUmiRegistryInjector;

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function isDisableRobotIndex() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/robot-stub");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setDisableRobotIndex($isDisable) {
			$this->getRegistry()->set("{$this->getPrefix()}/robot-stub", $isDisable);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getStubContent() {
			$filePath = $this->getStubFilePath();
			return is_file($filePath) ? file_get_contents($filePath) : '';
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setStubContent($content) {
			$filePath = $this->getStubFilePath();

			if (is_file($filePath)) {
				touch($filePath);
			}

			$file = fopen($filePath, 'w+');
			fwrite($file, $content);
			fclose($file);

			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getWhiteList() {
			return $this->getGuideList('ip-whitelist');
		}

		/** @inheritdoc */
		public function addToWhiteList($name) {
			return $this->addToGuide($name, 'ip-whitelist');
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getBlackList() {
			return $this->getGuideList('ip-blacklist');
		}

		/** @inheritdoc */
		public function addToBlackList($name) {
			return $this->addToGuide($name, 'ip-blacklist');
		}

		/** @inheritdoc */
		abstract public function getStubFilePath();

		/** @inheritdoc */
		abstract protected function getPrefix();

		/**
		 * Возвращает список объектов справочника по гуиду типа данных
		 * @param string $guid
		 * @return array
		 * @throws \coreException
		 */
		protected function getGuideList($guid) {
			$umiObjectsCollection = \umiObjectsCollection::getInstance();
			$itemList = $umiObjectsCollection->getGuidedItems($this->getGuideId($guid));
			$ipList = [];

			foreach ($itemList as $id => $item) {
				$object = $umiObjectsCollection->getById($id);

				if (!$object instanceof \iUmiObject) {
					continue;
				}

				if ($this->validateObject($object)) {
					$ipList[$id] = $item;
				}
			}

			return $ipList;
		}

		/**
		 * Валидирует объект
		 * @param \iUmiObject $object
		 * @return bool
		 */
		abstract protected function validateObject(\iUmiObject $object);

		/**
		 * Добавляет объект в справочник и возвращает его идентификатор
		 * @param string $name имя объекта
		 * @param string $guid гуид справочника
		 * @return int|bool
		 */
		abstract protected function addToGuide($name, $guid);

		/**
		 * Создает объект в справочнике и возвращает его идентификатор
		 * @param string $name имя объекта
		 * @param string $guid гуид справочника
		 * @return bool|int
		 */
		protected function createObject($name, $guid) {
			return \umiObjectsCollection::getInstance()
				->addObject($name, $this->getGuideId($guid));
		}

		/**
		 * Возвращает идентификатор справочника
		 * @param string $guid гуид справочника
		 * @return bool|int
		 */
		protected function getGuideId($guid) {
			$type = \umiObjectTypesCollection::getInstance()
				->getTypeByGUID($guid);
			return ($type instanceof \iUmiObjectType) ? $type->getId() : false;
		}

		/**
		 * Используется ли настройка в config.ini
		 * @param string $group группа конфига
		 * @param string $directive директива конфига
		 * @return bool
		 */
		abstract protected function isUseConfigDirective($group, $directive);

		/**
		 * Изменяет настройку в config.ini
		 * @param bool $isUse флаг переключения
		 * @param string $group группа конфига
		 * @param string $directive директива конфига
		 * @return $this
		 */
		abstract protected function setConfigDirective($isUse, $group, $directive);
	}