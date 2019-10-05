<?php

	/**
	 * Класс, представляющий контейнер для хранения данных
	 * "изменения" модуля "Резвервирование"
	 */
	class backupChange {

		/** @var $id int ID изменения */
		private $id;

		/** @var $createTime int unix timestamp создания изменения */
		private $createTime;

		/** @var $module string имя модуля, в котором произошло изменение */
		private $module;

		/** @var $method string имя метода, при вызове которого произошло изменение */
		private $method;

		/** @var $elementId int ID страницы, для которой было создано изменение */
		private $elementId;

		/** @var $data string данные изменения */
		private $data;

		/** @var $userId int ID пользователя, у которого было создано изменение */
		private $userId;

		/** @var $isActive bool активность изменения */
		private $isActive;

		/**
		 * @param int $id ID изменения
		 * @param int $createTime unix timestamp создания изменения
		 * @param string $module имя модуля, в котором произошло изменение
		 * @param string $method имя метода, при вызове которого произошло изменение
		 * @param int $elementId ID страницы, для которой было создано изменение
		 * @param string $data данные изменения
		 * @param int $userId ID пользователя, у которого было создано изменение
		 * @param bool $isActive активность изменения
		 */
		public function __construct($id, $createTime, $module, $method, $elementId, $data, $userId, $isActive) {
			$this->id = (int) $id;
			$this->createTime = (int) $createTime;
			$this->module = (string) $module;
			$this->method = (string) $method;
			$this->elementId = (int) $elementId;
			$this->data = (string) $data;
			$this->userId = (int) $userId;
			$this->isActive = (bool) $isActive;
		}

		/**
		 * Возвращает значение инициализированного свойства объекта
		 * @param string $name имя свойства
		 * @return mixed
		 */
		public function __get($name) {
			if (isset($this->$name)) {
				return $this->$name;
			}
			return null;
		}

		/**
		 * Проверяет существование свойства у класса
		 * @param string $name имя свойства
		 * @return bool
		 */
		public function __isset($name) {
			return property_exists(get_class($this), $name);
		}

		/**
		 * Устанавливает значение для объявленного свойства объекта
		 * @param string $name имя свойства
		 * @param mixed $value новое значение свойства
		 */
		public function __set($name, $value) {
			if (property_exists(get_class($this), $name) &&
				$this->$name !== $value) {

				$this->$name = $value;
			}
		}

	}

