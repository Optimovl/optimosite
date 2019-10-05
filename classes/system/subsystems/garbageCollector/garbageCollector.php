<?php

	use UmiCms\Service;

	/**
	 * Класс сбора мусора.
	 * Выполняет следующие операции:
	 *
	 * 1) Инвалидирует статический кеш;
	 * 2) Удаляет пустые записи в cms3_object_content;
	 */
	class garbageCollector implements iGarbageCollector {

		/** @var int $maxIterationsCount максимальное количество выполняемых итераций за один запуск */
		protected $maxIterationsCount = 50000;

		/** @var int $executedIterationsCount количество выполненных итераций за текущий запуск */
		protected $executedIterationsCount = 0;

		/** @inheritdoc */
		public function run() {
			return $this->resetExecutedIterationCount()
				->deleteInvalidStaticCache()
				->deleteEmptyObjectProperties();
		}

		/** @inheritdoc */
		public function setMaxIterationCount($maxIterationsCount) {
			$this->maxIterationsCount = (int) $maxIterationsCount;
			return $this;
		}

		/** @inheritdoc */
		public function getMaxIterationCount() {
			return $this->maxIterationsCount;
		}

		/** @inheritdoc */
		public function getExecutedIterationsCount() {
			return $this->executedIterationsCount;
		}

		/**
		 * Удаляет невалидный статический кеш
		 * @return garbageCollector
		 * @throws coreException
		 */
		protected function deleteInvalidStaticCache() {
			$ttl = Service::StaticCache()
				->getTimeToLive();
			$config = mainConfiguration::getInstance();

			return $this->deleteDirectoryIfExpired(
				$config->includeParam('system.static-cache'),
				$ttl
			);
		}

		/**
		 * Удаляет файл, если истекло время его жизни.
		 * Если передана директория удаляет файлы директории, если истекло время их жизни.
		 * @param iUmiDirectory|iUmiFile $item объект файловой системы
		 * @param int $lifeTime время жизни файла
		 * @throws coreException
		 */
		protected function deleteIfExpired($item, $lifeTime = 0) {
			switch (true) {
				case ($item instanceof iUmiDirectory) : {
					$this->deleteDirectoryIfExpired($item->getPath(), $lifeTime);
					break;
				}
				case ($item instanceof iUmiFile) : {
					$this->deleteFileIfExpired($item, $lifeTime);
					break;
				}
				default : {
					throw new coreException('Got unexpected result of type "' . gettype($item) . '"');
				}
			}
		}

		/**
		 * Рекурсивно удаляет файлы директории, если истекло время их жизни.
		 * Сама директория так же удаляется, если в ней нет содержимого.
		 * @param string $directoryPath путь до директории
		 * @param int $lifeTime время жизни файла
		 * @return garbageCollector
		 * @throws coreException
		 * @throws maxIterationsExeededException
		 */
		protected function deleteDirectoryIfExpired($directoryPath, $lifeTime = 0) {
			$dir = new umiDirectory($directoryPath);

			if ($dir->getIsBroken() || !$dir->isReadable()) {
				return $this;
			}

			foreach ($dir as $item) {
				$this->incrementExecutedIterationCount();
				$this->checkMaxIterations();
				$this->deleteIfExpired($item, $lifeTime);
			}

			$dir->deleteEmptyDirectory();

			return $this;
		}

		/**
		 * Проверяет превышение лимита на количество итераций
		 * @return garbageCollector
		 * @throws maxIterationsExeededException
		 */
		protected function checkMaxIterations() {
			$maxIterationCount = $this->getMaxIterationCount();

			if ($this->getExecutedIterationsCount() > $maxIterationCount) {
				throw new maxIterationsExeededException('Maximum iterations count reached: ' . $maxIterationCount);
			}

			return $this;
		}

		/**
		 * Удаляет пустые свойства объектов, то есть пустые записи в таблице cms3_object_content
		 * @return garbageCollector
		 * @throws coreException
		 */
		protected function deleteEmptyObjectProperties() {
			$connection = ConnectionPool::getInstance()->getConnection();

			$sql = <<<SQL
DELETE FROM `cms3_object_content`
WHERE
	`int_val` IS NULL AND
	`varchar_val` IS NULL AND
	`text_val` IS NULL AND
	`rel_val` IS NULL AND
	`tree_val` IS NULL AND
	`float_val` IS NULL
SQL;

			$connection->queryResult($sql);

			if ($connection->errorOccurred()) {
				throw new coreException($connection->errorDescription($sql));
			}

			return $this;
		}

		/**
		 * Обнуляет количество выполненных итераций
		 * @return garbageCollector
		 */
		protected function resetExecutedIterationCount() {
			return $this->setExecutedIterationCount(0);
		}

		/**
		 * Увеличивает количество выполненых операций на 1
		 * @return garbageCollector
		 */
		protected function incrementExecutedIterationCount() {
			$this->setExecutedIterationCount(
				$this->getExecutedIterationsCount() + 1
			);
			return $this;
		}

		/**
		 * Устанавливает количество выполненных итераций
		 * @param int $executedIterationCount количество выполненных итераций
		 * @return garbageCollector
		 */
		protected function setExecutedIterationCount($executedIterationCount) {
			$this->executedIterationsCount = $executedIterationCount;
			return $this;
		}

		/**
		 * Удаляет файл, если истекло время его жизни
		 * @param iUmiFile $file файл
		 * @param int $lifeTime время жизни
		 * @return $this
		 */
		protected function deleteFileIfExpired(iUmiFile $file, $lifeTime) {
			if ($file->getModifyTime() <= (time() - $lifeTime)) {
				$file->delete();
			}
			return $this;
		}
	}
