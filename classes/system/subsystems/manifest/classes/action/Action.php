<?php

	/** Абстрактный родительский класс для всех реализаций команд */
	abstract class Action implements iAction {

		/**
		 * @var array $params параметры для замены плейсхолдеров
		 *
		 * [
		 *      'name' => 'value
		 * ]
		 */
		protected $params = [];

		/** @var string $name название команды (строковой идентификатор) */
		protected $name;

		/** @var iAtomicOperationCallback|null $callback обработчик хода выполнения манифеста */
		protected $callback;

		/** @inheritdoc */
		abstract public function execute();

		/** @inheritdoc */
		abstract public function rollback();

		/** @inheritdoc */
		public function __construct($name, array $params = []) {
			$this->name = (string) $name;
			$this->params = $params;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function getTitle() {
			return getLabel('label-' . trimNameSpace($this->getName()));
		}

		/** @inheritdoc */
		public function setCallback(iAtomicOperationCallback $callback) {
			$this->callback = $callback;
		}

		/**
		 * Возвращает значение параметра по его названию
		 * @param string $name название
		 * @return mixed
		 */
		protected function getParam($name) {
			return isset($this->params[$name]) ? $this->params[$name] : null;
		}

		/**
		 * Выполняет sql запрос и возвращает его результат
		 * @param string $sql текст запроса
		 * @return IQueryResult
		 * @throws databaseException
		 */
		protected function mysql_query($sql) {
			$connection = ConnectionPool::getInstance()
				->getConnection();

			try {
				$result = $connection->queryResult($sql);
			} catch (databaseException $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			return $result;
		}

		/**
		 * Рекурсивно создает директорию
		 * @param string $filePath путь до создаваемой директории
		 * @throws Exception если директорию не удалось создать
		 */
		protected function createDirectory($filePath) {
			umiDirectory::requireFolder($filePath, CURRENT_WORKING_DIR);

			$directory = new umiDirectory($filePath);

			if ($directory->getIsBroken()) {
				throw new Exception("Can't create directory: " . $directory->getPath());
			}
		}

		/**
		 * Рекурсивно удаляет директорию
		 * @param string $path путь до директории
		 */
		protected function removeDirectory($path) {
			$directory = new umiDirectory($path);
			if ($directory->getIsBroken()) {
				return;
			}

			$directory->deleteRecursively();
		}

		/**
		 * Возвращает обработчик хода выполнения
		 * @return iAtomicOperationCallback
		 * @throws Exception
		 */
		protected function getCallback() {
			if (!$this->callback instanceof iAtomicOperationCallback) {
				throw new Exception('You should set iAtomicOperationCallback before use it');
			}

			return $this->callback;
		}
	}
