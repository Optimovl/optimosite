<?php

	/**
	 * Классы, дочерние от класса baseRestriction отвечают за валидацию полей.
	 * В таблицу `cms3_object_fields` добавилось поле `restriction_id`
	 * Список рестрикшенов хранится в таблице `cms3_object_fields_restrictions`:
	 * +----+------------------+-------------------------------+---------------+
	 * | id | class_prefix     | title                         | field_type_id |
	 * +----+------------------+-------------------------------+---------------+
	 * | 1  | email            | i18n::restriction-email-title | 4             |
	 * +----+------------------+-------------------------------+---------------+
	 *
	 * При модификации значения поля, которое связано с restriction'ом, загружается этот restriction,
	 * В метод validate() передается значение. Если метод вернет true, работа продолжается,
	 * если false, то получаем текст ошибки и делаем errorPanic() на предыдущую страницу.
	 */
	abstract class baseRestriction {

		protected $errorMessage = 'restriction-error-common',
			$id, $title, $classPrefix, $fieldTypeId;

		/**
		 * Загружает restriction
		 * @param int $restrictionId id рестрикшена
		 * @return baseRestriction|bool потомок класса baseRestriction
		 */
		final public static function get($restrictionId) {
			static $cache;

			if (isset($cache[$restrictionId]) && $cache[$restrictionId] instanceof baseRestriction) {
				return $cache[$restrictionId];
			}

			$restrictionId = (int) $restrictionId;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT `class_prefix`, `title`, `field_type_id`
FROM `cms3_object_fields_restrictions`
WHERE `id` = '{$restrictionId}'
SQL;
			$result = $connection->queryResult($sql);

			if ($result->length() == 0) {
				return false;
			}

			$result->setFetchType(IQueryResult::FETCH_ROW);
			list($classPrefix, $title, $fieldTypeId) = $result->fetch();

			$className = $classPrefix . 'Restriction';

			if (!class_exists($className)) {
				$filePath =
					CURRENT_WORKING_DIR . '/classes/system/subsystems/models/data/restrictions/' . $classPrefix . '.php';

				if (!is_file($filePath)) {
					return false;
				}

				require $filePath;
			}

			if (!class_exists($className)) {
				return false;
			}

			$restriction = new $className($restrictionId, $classPrefix, $title, $fieldTypeId);

			if (!$restriction instanceof baseRestriction) {
				return false;
			}

			return $cache[$restrictionId] = $restriction;
		}

		/**
		 * Возвращает список ограничений поля
		 * @return baseRestriction[]
		 */
		final public static function getList() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT `id` FROM `cms3_object_fields_restrictions`';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$restrictions = [];

			foreach ($result as $row) {
				$restriction = self::get(array_shift($row));

				if (!$restriction instanceof baseRestriction) {
					continue;
				}

				$restrictions[] = $restriction;
			}

			return $restrictions;
		}

		/**
		 * Добавить новый restriction
		 * @param string $classPrefix название класса рестрикшена
		 * @param string $title название рестрикшена
		 * @param int $fieldTypeId id типа полей, для которого допустим этот рестрикшен
		 * @return int|bool id созданного рестрикшена, либо false
		 */
		final public static function add($classPrefix, $title, $fieldTypeId) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$classPrefix = $connection->escape($classPrefix);
			$title = $connection->escape($title);
			$fieldTypeId = (int) $fieldTypeId;

			$sql = <<<SQL
INSERT INTO `cms3_object_fields_restrictions`
	(`class_prefix`, `title`, `field_type_id`)
	VALUES ('{$classPrefix}', '{$title}', '{$fieldTypeId}')
SQL;

			$connection->query($sql);
			return $connection->insertId();
		}

		/**
		 * Провалидировать значение поля
		 * @param mixed &$value валидируемое значение поля
		 * @param int|bool $objectId
		 * @return bool результат валидации
		 */
		abstract public function validate($value, $objectId = false);

		/**
		 * Получить текст сообщения об ошибке
		 * @return string сообщение об ошибке
		 */
		public function getErrorMessage() {
			return getLabel($this->errorMessage);
		}

		/**
		 * Получить название рестрикшена
		 * @return string название рестрикшена
		 */
		public function getTitle() {
			return getLabel($this->title);
		}

		/**
		 * Получить префикс класса рестрикшена
		 * @return string префикс класса рестрикшена
		 */
		public function getClassName() {
			return $this->classPrefix;
		}

		/**
		 * Получить id рестрикшена
		 * @return int id рестрикшена
		 */
		public function getId() {
			return $this->id;
		}

		public function getFieldTypeId() {
			return $this->fieldTypeId;
		}

		public static function find($classPrefix, $fieldTypeId) {
			$restrictions = self::getList();

			foreach ($restrictions as $restriction) {
				if ($restriction->getClassName() == $classPrefix && $restriction->getFieldTypeId() == $fieldTypeId) {
					return $restriction;
				}
			}
		}

		/**
		 * Конструктор
		 * @param $id
		 * @param $classPrefix
		 * @param $title
		 * @param $fieldTypeId
		 */
		protected function __construct($id, $classPrefix, $title, $fieldTypeId) {
			$this->id = (int) $id;
			$this->classPrefix = $classPrefix;
			$this->title = $title;
			$this->fieldTypeId = (int) $fieldTypeId;
		}
	}

	interface iNormalizeInRestriction {

		public function normalizeIn($value, $objectId = false);
	}

	interface iNormalizeOutRestriction {

		public function normalizeOut($value, $objectId = false);
	}

