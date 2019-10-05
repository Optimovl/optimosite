<?php

	/**
	 * Базовый класс для классов, которые реализуют ключевые сущности ядра системы.
	 * Реализует основные интерфейсы, которые должна поддерживать любая сущность.
	 */
	abstract class umiEntinty implements iUmiEntinty {

		/** @var int $id идентификатор сущности */
		protected $id;

		/** @var bool $is_updated флаг "обновлен" */
		protected $is_updated = false;

		/** @var bool нужно ли производить сохранение изменений сущности в деструкторе */
		protected $savingInDestructor;

		/** @var string $store_type тип кешируемой сущности */
		protected $store_type = 'entity';

		/**
		 * Конструктор сущности, должен вызываться из коллекций
		 * @param int $id идентификатор сущности
		 * @param array|bool $row массив значений, который может быть передан для оптимизации
		 * @param bool $instantLoad нужно ли произвести немедленную загрузку данных
		 * @param bool $savingInDestructor нужно ли сохранять изменения сущности в деструкторе
		 * @throws privateException
		 */
		public function __construct($id, $row = false, $instantLoad = true, $savingInDestructor = true) {
			$this->setId($id)
				->setSavingInDestructor($savingInDestructor);
			$this->is_updated = false;

			if ($instantLoad && $this->loadInfo($row) === false) {
				throw new privateException("Failed to load info for {$this->store_type} with id {$id}");
			}
		}

		/**
		 * Запрещает клонирование
		 * @throws coreException
		 */
		public function __clone() {
			throw new coreException('umiEntinty must not be cloned');
		}

		/** Деструктор сущности проверят, были ли внесены изменения. Если да, то они сохраняются */
		public function __destruct() {
			if (!$this->getSavingInDestructor()) {
				return;
			}

			$this->commit();
		}

		/** @inheritdoc */
		public function getId() {
			return $this->id;
		}

		/**
		 * Изменяет id сущности
		 * @param int $id новый id сущности
		 * @return $this
		 */
		protected function setId($id) {
			$this->id = (int) $id;
			return $this;
		}

		/** @inheritdoc */
		public function getIsUpdated() {
			return $this->is_updated;
		}

		/** @inheritdoc */
		public function setIsUpdated($isUpdated = true) {
			$this->is_updated = (bool) $isUpdated;
		}

		/**
		 * Инициализирует сущность переданными данными или данными из БД
		 * @param array|bool $row полный набор свойств объекта или false
		 * @return bool
		 */
		abstract protected function loadInfo($row = false);

		/**
		 * Сохраняет в БД информацию о сущности
		 * @return bool
		 */
		abstract protected function save();

		/** @inheritdoc */
		public function commit() {
			if (!$this->getIsUpdated()) {
				return false;
			}

			$res = $this->save();
			$this->setIsUpdated(false);

			return $res;
		}

		/** @inheritdoc */
		public function update() {
			$res = $this->loadInfo();
			$this->setIsUpdated(false);
			return $res;
		}

		/** @inheritdoc */
		public static function filterInputString($string) {
			return ConnectionPool::getInstance()
				->getConnection()
				->escape($string);
		}

		/**
		 * Magic method
		 * @return string id объекта
		 */
		public function __toString() {
			return (string) $this->getId();
		}

		/** @inheritdoc */
		public function getStoreType() {
			return $this->store_type;
		}

		/** @inheritdoc */
		public function translateLabel($label) {
			$str = startsWith($label, 'i18n::') ? getLabel(mb_substr($label, 6)) : getLabel($label);
			return $str === null ? $label : $str;
		}

		/** @inheritdoc */
		public function setSavingInDestructor($flag = true) {
			$this->savingInDestructor = $flag;
			return $this;
		}

		/**
		 * Определяет поддержку сохранения измененной сущности на этапе ее удаления из памяти
		 * @return bool
		 */
		protected function getSavingInDestructor() {
			return $this->savingInDestructor;
		}

		/**
		 * Возвращает ключ строковой константы, если она определена, либо саму строку
		 * @param string $str строка, для которых нужно определить ключ
		 * @param string $pattern префикс ключа, используется внутри системы
		 * @return string ключ константы, либо параметр $str, если такого значение нет в списке констант
		 */
		protected function translateI18n($str, $pattern = '') {
			$label = ulangStream::getI18n($str, $pattern);
			return $label === null ? $str : $label;
		}

		/** @deprecated */
		public function beforeSerialize() {
		}

		/** @deprecated */
		public function afterSerialize() {
		}

		/** @deprecated */
		public function afterUnSerialize() {
		}
	}
