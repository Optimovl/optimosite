<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber\Steps;

	/**
	 * Class ObjectsNames шаг сбора ссылок из имен объектов
	 * @package UmiCms\Classes\System\Utils\Links\Grabber\Steps
	 */
	class ObjectsNames extends Step implements \iUmiDataBaseInjector {

		use \tUmiDataBaseInjector;

		/** @var int $offset смещение результатов выборки */
		protected $offset;

		/** @var int $limit ограничение на количество результатов выборки */
		protected $limit;

		/** @const string STEP_NAME имя шага */
		const STEP_NAME = 'ObjectsNames';

		/** @const string OFFSET_KEY ключ смещения результатов выборки */
		const OFFSET_KEY = 'offset';

		/** @const string OFFSET_KEY ключ ограничения на количество результатов выборки */
		const LIMIT_KEY = 'limit';

		/**
		 * @const string ITERATION_ITEMS_NUMBER_DEFAULT_LIMIT ограничение на количество обрабатываемых строк в бд
		 * за одну итерациию сбора шага по умолчанию
		 */
		const ITERATION_ITEMS_NUMBER_DEFAULT_LIMIT = 150;

		/** @inheritdoc */
		public function getName() {
			return self::STEP_NAME;
		}

		/** @inheritdoc */
		public function getStartStateStructure() {
			return [
				self::OFFSET_KEY => 0,
				self::LIMIT_KEY => self::ITERATION_ITEMS_NUMBER_DEFAULT_LIMIT,
				self::COMPLETE_KEY => false,
			];
		}

		/** @inheritdoc */
		public function setState(array $state) {
			if (!isset($state[self::OFFSET_KEY])) {
				throw new \wrongParamException('Cant detect offset');
			}

			$offset = $state[self::OFFSET_KEY];

			if (!isset($state[self::LIMIT_KEY])) {
				throw new \wrongParamException('Cant detect limit');
			}

			$limit = $state[self::LIMIT_KEY];

			if (!isset($state[self::COMPLETE_KEY])) {
				throw new \wrongParamException('Cant detect complete status');
			}

			$completeStatus = $state[self::COMPLETE_KEY];

			$this->setOffset($offset)
				->setLimit($limit)
				->setCompleteStatus($completeStatus);
			return $this;
		}

		/** @inheritdoc */
		public function grab() {
			if ($this->isComplete()) {
				return $this;
			}

			$limit = (int) $this->getLimit();
			$offset = (int) $this->getOffset();

			$connection = $this->getConnection();
			$sql = <<<SQL
SELECT
	`id`,
	`name`
FROM
	`cms3_objects`
WHERE
	`name` IS NOT NULL
LIMIT
	$offset, $limit;
SQL;
			$result = $connection->queryResult($sql);
			$objectNamesLinks = [];

			if ($result->length() == 0) {
				$this->setResult($objectNamesLinks)
					->setCompleteStatus(true);
			}

			foreach ($result as $row) {
				$name = trim($row['name']);
				$urls = $this->parseUrlsFromString($name);

				if (umiCount($urls) === 0) {
					continue;
				}

				$id = $row['id'];
				$linkToEdit = $this->getObjectEditLinkByObjectId($id);
				$objectNamesLinks[$linkToEdit] = $urls;
			}

			return $this->setResult($objectNamesLinks)
				->setOffset($offset + $limit);
		}

		/** @inheritdoc */
		public function getState() {
			return [
				self::OFFSET_KEY => (int) $this->getOffset(),
				self::LIMIT_KEY => (int) $this->getLimit(),
				self::COMPLETE_KEY => (bool) $this->isComplete(),
			];
		}

		/**
		 * Устанавливает смещение результата выборки
		 * @param int $offset смещение результата выборки
		 * @return $this
		 * @throws \wrongParamException
		 */
		protected function setOffset($offset) {
			if (!is_numeric($offset)) {
				throw new \wrongParamException('Wrong offset given');
			}
			$this->offset = (int) $offset;
			return $this;
		}

		/**
		 * Возвращает смещение результата выборки
		 * @return int
		 * @throws \RequiredPropertyHasNoValueException
		 */
		protected function getOffset() {
			if ($this->offset === null) {
				throw new \RequiredPropertyHasNoValueException('You should set offset first');
			}

			return $this->offset;
		}

		/**
		 * Устанавливает ограничение на количество результатов выборки
		 * @param int $limit ограничение на количество результатов выборки
		 * @return $this
		 * @throws \wrongParamException
		 */
		protected function setLimit($limit) {
			if (!is_numeric($limit) || $limit === 0) {
				throw new \wrongParamException('Wrong limit given');
			}
			$this->limit = (int) $limit;
			return $this;
		}

		/**
		 * Возвращает ограничение на количество результатов выборки
		 * @return int
		 * @throws \RequiredPropertyHasNoValueException
		 */
		protected function getLimit() {
			if ($this->limit === null) {
				throw new \RequiredPropertyHasNoValueException('You should set limit first');
			}

			return $this->limit;
		}

		/**
		 * Возвращает ссылку на редактирование объека
		 * @param int $objectId идентификатор объекта
		 * @return string
		 */
		protected function getObjectEditLinkByObjectId($objectId) {
			return '/admin/data/guide_item_edit/' . $objectId . '/';
		}
	}
