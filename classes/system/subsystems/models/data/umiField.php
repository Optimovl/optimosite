<?php

	/** Класс поля */
	class umiField extends umiEntinty implements iUmiField {

		/** @const int количество свойств поля, необходимое для корректного инстанцирования */
		const INSTANCE_ATTRIBUTE_COUNT = 16;

		/** @var string строковой идентификатор (GUID) */
		private $name;

		/** @var string наименование, может содержать языковую константу */
		private $title;

		/** @var int идентификатор типа */
		private $fieldTypeId;

		/** @var bool является ли поля обязательным для заполнения */
		private $isRequired;

		/** @var bool индексируется ли значение поля для поиска */
		private $isIndexed = true;

		/** @var bool индексируется ли значение поля для фильтров */
		private $isFiltered = true;

		/**
		 * @var bool является ли поле важным для отображения (такие поля по умолчанию отображаются
		 * в формах редактирования и создания административной панели).
		 */
		private $isImportant;

		/** @var string|null текст подсказки, может содержать языковую константу */
		private $tip;

		/** @var int идентификатор ограничения */
		private $restrictionId;

		/**
		 * @var int|null идентификатор привязанного справочника
		 * (для полей типа "relation" и "optioned")
		 */
		private $guideId;

		/**
		 * @var bool является ли поле системным,
		 * то есть доступ к нему предоставляется только средствами api
		 */
		private $isSystem;

		/**
		 * @var bool является ли поле видимым на сайте
		 * (ограниченно используется только для tpl шаблонизатора)
		 */
		private $isVisible;

		/** @var bool является ли поле заблокированным (не используется) */
		private $isLocked;

		/** @var bool является ли поле наследуемым (не используется) */
		private $isInheritable;

		/** @var bool является ли поле сортируемым (не используется) */
		private $isSortable = false;

		/** @var string тип сохраняемой сущности для кеширования */
		protected $store_type = 'field';

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			if (!is_string($name) || empty($name)) {
				throw new wrongParamException(getLabel('label-incorrect-field-name'));
			}

			$name = umiHierarchy::convertAltName($name, '_');

			if ($this->getName() != $name) {
				$name = umiObjectProperty::filterInputString($name);
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		/** @inheritdoc */
		public function setTitle($title) {
			if (!is_string($title) || empty($title)) {
				throw new wrongParamException(getLabel('label-incorrect-field-title'));
			}

			if ($this->getTitle() != $title) {
				$title = $this->translateI18n($title, 'field-');
				$title = umiObjectProperty::filterInputString($title);
				$this->title = $title;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getIsVisible() {
			return $this->isVisible;
		}

		/** @inheritdoc */
		public function setIsVisible($flag) {
			$flag = (bool) $flag;

			if ($this->getIsVisible() != $flag) {
				$this->isVisible = $flag;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getFieldTypeId() {
			return $this->fieldTypeId;
		}

		/** @inheritdoc */
		public function setFieldTypeId($id) {
			$escapedId = (int) $id;

			if (!is_numeric($id) || $escapedId === 0) {
				throw new wrongParamException(getLabel('label-incorrect-field-type'));
			}

			if ($this->getFieldTypeId() != $escapedId) {
				$this->fieldTypeId = $escapedId;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getGuideId() {
			return $this->guideId;
		}

		/** @inheritdoc */
		public function hasGuide() {
			return ($this->guideId !== null);
		}

		/** @inheritdoc */
		public function setGuideId($guideId) {
			$guideId = is_numeric($guideId) ? (int) $guideId : null;

			if ($this->getGuideId() != $guideId) {
				$this->guideId = $guideId;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getIsInSearch() {
			return $this->isIndexed;
		}

		/** @inheritdoc */
		public function setIsInSearch($flag) {
			$flag = (bool) $flag;

			if ($this->getIsInSearch() != $flag) {
				$this->isIndexed = $flag;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getIsInFilter() {
			return $this->isFiltered;
		}

		/** @inheritdoc */
		public function setIsInFilter($flag) {
			$flag = (bool) $flag;

			if ($this->getIsInFilter() != $flag) {
				$this->isFiltered = $flag;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getTip() {
			return $this->translateLabel($this->tip);
		}

		/** @inheritdoc */
		public function setTip($tip) {
			if (!is_string($tip) || empty($tip)) {
				return;
			}

			if ($this->getTip() != $tip) {
				$tip = $this->translateI18n($tip, 'field-');
				$tip = umiObjectProperty::filterInputString($tip);
				$this->tip = $tip;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function isImportant() {
			return $this->isImportant;
		}

		/** @inheritdoc */
		public function setImportanceStatus($flag = false) {
			$flag = (bool) $flag;

			if ($this->isImportant() != $flag) {
				$this->isImportant = $flag;
				$this->setIsUpdated();
			}
		}

		/**
		 * Определяет является ли поле обязательным для заполнения
		 * @return bool
		 */
		public function getIsRequired() {
			return $this->isRequired;
		}

		/**
		 * Устанавливает, что поле является обязательным для заполнения
		 * @param bool $flag да/нет
		 */
		public function setIsRequired($flag = false) {
			$flag = (bool) $flag;

			if ($this->getIsRequired() != $flag) {
				$this->isRequired = $flag;
				$this->setIsUpdated();
			}
		}

		/**
		 * Возвращает идентификатор ограничения поля
		 * @return int
		 */
		public function getRestrictionId() {
			return $this->restrictionId;
		}

		/**
		 * Устанавливает идентификатор ограничения поля
		 * @param int|bool $id идентификатор ограничения
		 */
		public function setRestrictionId($id = false) {
			$id = (int) $id;

			if ($this->getRestrictionId() != $id) {
				$this->restrictionId = $id;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function removeRestriction() {
			$this->setRestrictionId();
			return $this;
		}

		/**
		 * Определяет является ли поле системным.
		 * @return bool
		 */
		public function getIsSystem() {
			return $this->isSystem;
		}

		/**
		 * Устанавливает, что поле является системным.
		 * @param bool $flag статуса поля
		 */
		public function setIsSystem($flag = false) {
			$flag = (bool) $flag;

			if ($this->getIsSystem() != $flag) {
				$this->isSystem = $flag;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getDataType() {
			return $this->getFieldType()->getDataType();
		}

		/** @inheritdoc */
		public function isMultiple() {
			return $this->getFieldType()->getIsMultiple();
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) != self::INSTANCE_ATTRIBUTE_COUNT) {
				$connection = ConnectionPool::getInstance()
					->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT `id`, `name`, `title`, `is_locked`, `is_inheritable`, `is_visible`, `field_type_id`, `guide_id`, `in_search`, 
	`in_filter`, `tip`, `is_required`, `sortable`, `is_system`, `restriction_id`, `is_important` 
		FROM `cms3_object_fields` WHERE `id` = $escapedId LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 16) {
				return false;
			}

			list(
				$id, $name, $title, $isLocked, $isInheritable, $isVisible,
				$fieldTypeId, $guideId, $isIndexed, $isFiltered, $tip, $isRequired,
				$sortable, $isSystem, $restrictionId, $isImportant
				) = $row;

			$this->id = (int) $id;
			$this->name = $name;
			$this->title = $title;
			$this->isLocked = (bool) $isLocked;
			$this->isInheritable = (bool) $isInheritable;
			$this->isVisible = (bool) $isVisible;
			$this->fieldTypeId = (int) $fieldTypeId;
			$this->guideId = $guideId;
			$this->isIndexed = (bool) $isIndexed;
			$this->isFiltered = (bool) $isFiltered;
			$this->tip = (string) $tip;
			$this->isRequired = (bool) $isRequired;
			$this->isSortable = (bool) $sortable;
			$this->isSystem = (bool) $isSystem;
			$this->restrictionId = (int) $restrictionId;
			$this->isImportant = (bool) $isImportant;

			return true;
		}

		/** @inheritdoc */
		protected function save() {
			$name = (string) $this->name;
			$title = (string) $this->title;
			$isLocked = (int) $this->isLocked;
			$isInheritable = (int) $this->isInheritable;
			$isVisible = (int) $this->isVisible;
			$typeId = (int) $this->fieldTypeId;
			$guideId = $this->guideId ? (int) $this->guideId : 'NULL';
			$isIndexed = (int) $this->isIndexed;
			$isFiltered = (int) $this->isFiltered;
			$tip = (string) $this->tip;
			$isRequired = (int) $this->isRequired;
			$isSortable = (int) $this->isSortable;
			$restrictionId = (int) $this->restrictionId;
			$isSystem = (int) $this->isSystem;
			$restrictionSql = $restrictionId ? ", restriction_id = '{$restrictionId}'" : ', restriction_id = NULL';
			$isImportant = (int) $this->isImportant();
			$escapedId = (int) $this->getId();

			$sql = <<<SQL
UPDATE `cms3_object_fields`
SET `name` = '$name', `title` = '$title', `is_locked` = $isLocked, `is_inheritable` = $isInheritable, 
	`is_visible` = $isVisible, `field_type_id` = $typeId, `guide_id` = $guideId, `in_search` = $isIndexed, 
		`in_filter` = $isFiltered, `tip` = '$tip', `is_required` = $isRequired, `sortable` = $isSortable, 
			`is_system` = $isSystem $restrictionSql, `is_important` = $isImportant 
WHERE 
	`id` = $escapedId
SQL;
			ConnectionPool::getInstance()
				->getConnection()
				->query($sql);

			return true;
		}

		/** @deprecated */
		public function isNumber() {
			return $this->getFieldType()
				->isNumber();
		}

		/**
		 * @deprecated
		 * @return bool
		 */
		public function getIsLocked() {
			return $this->isLocked;
		}

		/**
		 * @deprecated
		 * @param bool $flag
		 */
		public function setIsLocked($flag) {
			$flag = (bool) $flag;

			if ($this->getIsLocked() != $flag) {
				$this->isLocked = $flag;
				$this->setIsUpdated();
			}
		}

		/**
		 * @deprecated
		 * @return bool
		 */
		public function getIsInheritable() {
			return $this->isInheritable;
		}

		/**
		 * @deprecated
		 * @param bool $flag
		 */
		public function setIsInheritable($flag) {
			$flag = (bool) $flag;

			if ($this->getIsInheritable() != $flag) {
				$this->isInheritable = $flag;
				$this->setIsUpdated();
			}
		}

		/**
		 * @deprecated
		 * @return bool
		 */
		public function getIsSortable() {
			return $this->isSortable;
		}

		/**
		 * @deprecated
		 * @param bool $flag
		 */
		public function setIsSortable($flag = false) {
			$flag = (bool) $flag;

			if ($this->getIsSortable() != $flag) {
				$this->isSortable = $flag;
				$this->setIsUpdated();
			}
		}

		/** @deprecated  */
		public function getFieldType() {
			return umiFieldTypesCollection::getInstance()->getFieldType($this->fieldTypeId);
		}
	}
