<?php

	use UmiCms\Service;

	/**
	 * @method void equals(mixed $val)
	 * @method void notequals(mixed $val)
	 * @method void ilike(mixed $val)
	 * @method void like(mixed $val)
	 * @method void more(mixed $val)
	 * @method void eqmore(mixed $val)
	 * @method void less(mixed $val)
	 * @method void eqless(mixed $val)
	 * @method void isnull(bool $val = true)
	 * @method void isnotnull(bool $val = true)
	 * @version 1.0
	 */
	abstract class selectorWhereProp {

		protected $value;

		protected $mode;

		protected $searchInRelatedObject = true;

		protected $modes = [
			'equals',
			'notequals',
			'ilike',
			'like',
			'more',
			'eqmore',
			'less',
			'eqless',
			'between',
			'isnull',
			'isnotnull'
		];

		public function __call($method, $args) {
			$method = mb_strtolower($method);

			if (!in_array($method, $this->modes)) {
				throw new selectorException("This property doesn't support \"{$method}\" method");
			}

			$value = umiCount($args) ? $args[0] : null;

			if ($value instanceof iUmiEntinty) {
				$value = $value->getId();
			}

			if (isset($this->fieldIdList)) {
				/** @var selectorWhereProp|selectorWhereFieldProp $this */
				$fieldIdList = $this->getFieldIdList();
				$fieldId = array_shift($fieldIdList);

				/** @var iUmiField $field */
				$field = selector::get('field')->id($fieldId);
				$restrictionId = $field->getRestrictionId();

				if ($restrictionId) {
					$restriction = baseRestriction::get($restrictionId);
					if ($restriction instanceof iNormalizeInRestriction) {
						$value = $restriction->normalizeIn($value);
					}
				}

				$isString = is_string($value);
				$relatedMode = $this->searchInRelatedObject;

				if ($field->getDataType() === 'relation' && $isString && $relatedMode && $field->getGuideId()) {
					$guideId = $field->getGuideId();
					$obj = selector::get('object')->id($value);

					$sel = new selector('objects');
					$sel->types('object-type')->id($guideId);

					/** @var iUmiObjectType $usersType */
					$usersType = selector::get('object-type')
						->name('users', 'user');
					/**
					 * Обработка фильтра по особенному полю "customer_id" заказа.
					 * Особенность данного поля в следующем:
					 *
					 * 1) Оно прикреплено к справочнику пользователей;
					 * 2) Может хранить в себе как id пользователя, так и id покупателя (из другого справочника);
					 */
					if ($field->getName() == 'customer_id' && $field->getGuideId() == $usersType->getId()) {
						/** @var iUmiObjectType $customerType */
						$customerType = selector::get('object-type')
							->name('emarket', 'customer');
						$sel->types('object-type')->id($customerType->getId());
					}

					if (is_numeric($value) && $obj instanceof iUmiObject && ($obj->getTypeId() == $guideId)) {
						$sel->where('id')->equals($value);
					} else {
						$sel->where('*')->ilike($value);
					}

					if (umiCount($sel->result()) > 0) {
						$value = $sel->result();
					}
				}

				if ($field->getDataType() === 'date' && $isString) {
					$date = new umiDate;
					$date->setDateByString(trim($value, ' %'));
					$value = $date->getDateTimeStamp();
				}
			}

			$this->value = $value;
			$this->mode = $method;
		}

		public function between($start, $end) {
			return $this->__call('between', [[$start, $end]]);
		}

		public function __get($prop) {
			if (isset($this->$prop)) {
				return $this->$prop;
			}

			return null;
		}

		/** @inheritdoc */
		public function __set($name, $value) {
			if (property_exists($this, $name)) {
				$this->$name = $value;
			}

			return $this;
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $prop имя свойства
		 * @return bool
		 */
		public function __isset($prop) {
			return property_exists(get_class($this), $prop);
		}
	}

	class selectorWhereSysProp extends selectorWhereProp {

		public $name;

		public function __construct($name) {
			$this->name = $name;
		}
	}

	class selectorWhereFieldProp extends selectorWhereProp {

		protected $fieldIdList;

		public function __construct(array $fieldIdList, $searchInRelatedObject = true) {
			$this->fieldIdList = $fieldIdList;
			$this->searchInRelatedObject = is_array($searchInRelatedObject) ? true : (bool) $searchInRelatedObject;
		}

		/**
		 * Возвращает список идентификаторов полей
		 * @return array
		 */
		public function getFieldIdList() {
			return $this->fieldIdList;
		}
	}

	/**
	 * Вспомогательный класс для селектора.
	 * Используется для фильтрации страниц по иерархии от корня на нужную глубину.
	 *
	 * Примеры использования:
	 *
	 * 1. Найти всех детей страницы с id 40:
	 * $pages->where('hierarchy')->page(40);
	 *
	 * 2. Найти всех потомков страницы с id 40 на три уровня в глубину:
	 * $pages->where('hierarchy')->page(40)->level(3);
	 */
	class selectorWhereHierarchy {

		/** @var int идентификатор корневой страницы */
		protected $elementId;

		/** @var int глубина поиска детей от корня */
		protected $level;

		/** @var int уровень корневой страницы в иерархии сайта */
		protected $selfLevel;

		/**
		 * Указать корень поиска страниц
		 * @param int $elementId идентификатор корневой страницы
		 * @return $this
		 * @throws selectorException
		 */
		public function page($elementId) {
			$hierarchy = umiHierarchy::getInstance();

			if (!is_numeric($elementId)) {
				$elementId = $hierarchy->getIdByPath($elementId);
			}

			if (!is_numeric($elementId)) {
				throw new selectorException(__METHOD__ . ': elementId expected to be numeric');
			}

			$this->elementId = (int) $elementId;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT level FROM cms3_hierarchy_relations WHERE child_id = {$this->elementId}";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$selfLevel = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$selfLevel = (int) array_shift($fetchResult);
			}

			$this->selfLevel = $selfLevel;
			$this->level = $selfLevel + 1;

			return $this;
		}

		/**
		 * Указать глубину поиска детей от корня
		 * @param int $level глубина
		 * @throws selectorException
		 */
		public function level($level = 1) {
			if (!is_numeric($this->selfLevel)) {
				throw new selectorException(__METHOD__ . ': selfLevel expected to be numeric');
			}

			$this->level = ($level == 0) ? 0 : $this->selfLevel + (int) $level;
		}

		/**
		 * @param $prop
		 * @return mixed
		 */
		public function __get($prop) {
			return $this->$prop;
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $prop имя свойства
		 * @return bool
		 */
		public function __isset($prop) {
			return property_exists(get_class($this), $prop);
		}

		/**
		 * @deprecated
		 * @param int $level
		 */
		public function childs($level = 1) {
			$this->level($level);
		}
	}

	class selectorWherePermissions {

		protected $level = 0x1, $owners = [], $isSv;

		public function __construct() {
			$permissions = permissionsCollection::getInstance();
			$auth = Service::Auth();
			$userId = $auth->getUserId();

			$this->isSv = $permissions->isSv();
			if (!$this->isSv) {
				$this->owners = [$userId];
				$user = umiObjectsCollection::getInstance()->getObject($userId);
				if ($user) {
					$this->owners = array_merge($this->owners, $user->groups);
				}
			}
		}

		public function level($level) {
			$this->level = (int) $level;
		}

		public function owners($owners) {
			if (is_array($owners)) {
				foreach ($owners as $owner) {
					$this->owners($owner);
				}
			} else {
				$this->addOwner($owners);
			}
			return $this;
		}

		public function __get($prop) {
			return $this->$prop;
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $prop имя свойства
		 * @return bool
		 */
		public function __isset($prop) {
			return property_exists(get_class($this), $prop);
		}

		protected function addOwner($ownerId) {
			if (in_array($ownerId, $this->owners)) {
				return;
			}

			$permissions = permissionsCollection::getInstance();

			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($ownerId);

			if ($object instanceof iUmiObject) {
				if ($permissions->isSv($ownerId)) {
					$this->isSv = true;
					return;
				}

				$this->owners[] = $ownerId;

				if ($object->groups) {
					$this->owners = array_merge($this->owners, $object->groups);
				}
			}
		}
	}
