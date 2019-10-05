<?php

	namespace UmiCms\Manifest\Migrate\Field;

	use UmiCms\Service;
	use UmiCms\System\Data\Object\Property\iRepository;
	use UmiCms\System\Data\Object\Property\Value\iMigration;

	/**
	 * Класс команды переноса значений полей с идентификаторами доменов в хранилище для поля типа "Ссылка на домен"
	 * @package UmiCms\Manifest\Migrate\Field
	 */
	class MoveValueAction extends \IterableAction {

		/**
		 * @var array $targetList список целей миграции
		 *
		 * [
		 * 		[
		 *			'type-guid' => 'Гуид типа, в котором содержится поле',
		 *			'field' => 'Системное название поля',
		 *			'from' => 'Исходный тип данных поля',
		 * 		]
		 * ]
		 *
		 */
		private $targetList = [];

		/** @const int LIMIT ограничение на количество полей, обрабатываемых за одну итерацию */
		const LIMIT = 150;

		/** @const int OFFSET смещение количества полей для одной итерации (статично, так как исходные поля удаляются */
		const OFFSET = 0;

		/**
		 * @inheritdoc
		 * @throws \RuntimeException
		 */
		public function execute() {
			return $this->processTargetList([$this->getMigration(), 'migrate']);
		}

		/** @inheritdoc */
		public function rollback() {
			try {
				return $this->processTargetList([$this->getMigration(), 'rollback']);
			} catch (\Exception $exception) {
				return $this;
			}
		}

		/**
		 * Загружает список целей миграции
		 * @throws \RuntimeException
		 */
		protected function loadTargetList() {
			$targetList = $this->getParam('target');

			if (!is_array($targetList)) {
				throw new \RuntimeException('Incorrect target list given: ' . var_export($targetList, true));
			}

			return $this->setTargetList($targetList);
		}

		/**
		 * Возвращает идентификатор поля из цели миграции
		 * @param array $target цель миграции
		 *
		 * [
		 *		 'type-guid' => 'Гуид типа, в котором содержится поле',
		 *		 'field' => 'Системное название поля',
		 *		 'from' => 'Исходный тип данных поля',
		 * ]
		 *
		 * @return int
		 * @throws \RuntimeException
		 */
		protected function getFieldIdByTarget(array $target) {
			if (!isset($target['type-guid'], $target['field'], $target['from'])) {
				throw new \RuntimeException('Incorrect target given: ' . var_export($target, true));
			}

			$type = $this->getTypeCollection()
				->getTypeByGUID($target['type-guid']);

			if (!$type instanceof \iUmiObjectType) {
				throw new \RuntimeException('Incorrect type guid given: ' . var_export($target['type-guid'], true));
			}

			return (int) $type->getFieldId($target['field']);
		}

		/**
		 * Возвращает миграциию типов полей
		 * @return iMigration
		 */
		protected function getMigration() {
			return Service::get('ObjectPropertyValueDomainIdMigration');
		}

		/**
		 * Устанавливает список целей миграции
		 * @param array $targetList список целей миграции
		 *
		 * [
		 * 		[
		 *			'type-guid' => 'Гуид типа, в котором содержится поле',
		 *			'field' => 'Системное название поля',
		 *			'from' => 'Исходный тип данных поля',
		 * 		]
		 * ]
		 *
		 * @return $this
		 */
		protected function setTargetList(array $targetList) {
			$this->targetList = $targetList;
			return $this;
		}

		/**
		 * Обрабатывает список целей миграции с помощью заданного обработчика
		 * @param callable $operation обработчик
		 * @return $this
		 */
		private function processTargetList(callable $operation) {
			$isReady = true;

			foreach ($this->getTargetList() as $index => $target) {
				$isReadyPart = $this->isReadyPart($index);
				$isReady = $isReady && $isReadyPart;

				if ($isReadyPart) {
					continue;
				}

				$allPropertiesProcessed = true;

				foreach ($this->getPropertyList($target) as $property) {
					call_user_func_array($operation, [$property, $target['from']]);
					$allPropertiesProcessed = false;
				}

				if ($allPropertiesProcessed) {
					$this->setPartIsReady($index);
				} else {
					break;
				}
			}

			if ($isReady) {
				$this->setIsReady();
				$this->resetState();
			}

			$this->saveState();
			return $this;
		}

		/**
		 * Возвращает список значений полей объектов
		 * @param array $target цель миграции
		 *
		 * 		[
		 *			'type-guid' => 'Гуид типа, в котором содержится поле',
		 *			'field' => 'Системное название поля',
		 *			'from' => 'Исходный тип данных поля',
		 * 		]
		 *
		 * @return \iUmiObjectProperty[]
		 */
		private function getPropertyList(array $target) {
			$fieldId = $this->getFieldIdByTarget($target);
			$previousDataType = $target['from'];
			return $this->getRepository()
				->getListByFieldId($fieldId, self::LIMIT, self::OFFSET, $previousDataType);
		}

		/**
		 * Возвращает список целей миграции
		 * @return array
		 *
		 * [
		 * 		[
		 *			'type-guid' => 'Гуид типа, в котором содержится поле',
		 *			'field' => 'Системное название поля',
		 *			'from' => 'Исходный тип данных поля',
		 * 		]
		 * ]
		 *
		 */
		private function getTargetList() {
			if (isEmptyArray($this->targetList)) {
				$this->loadTargetList();
			}

			return $this->targetList;
		}

		/**
		 * Устанавливает, что часть миграции была завершена
		 * @param int $index индекс части миграции
		 * @return $this
		 */
		private function setPartIsReady($index) {
			$readinessList = (array) $this->getStatePart('readiness');
			$readinessList[$index] = true;
			return $this->setStatePart('readiness', $readinessList);
		}

		/**
		 * Определяет была ли завершена часть миграции
		 * @param int $index индекс части
		 * @return bool
		 */
		private function isReadyPart($index) {
			$readinessList = (array) $this->getStatePart('readiness');
			return isset($readinessList[$index]) ? (bool) $readinessList[$index] : false;
		}

		/**
		 * Возвращает коллекцию объектных типов
		 * @return \iUmiObjectTypesCollection
		 */
		private function getTypeCollection() {
			return \umiObjectTypesCollection::getInstance();
		}

		/**
		 * Возвращает репозиторий значений полей объектов
		 * @return iRepository
		 */
		private function getRepository() {
			return Service::get('ObjectPropertyRepository');
		}
	}