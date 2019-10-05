<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\Repository\iHistory;

	/**
	 * Интерфейс репозитория сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iRepository {

		/**
		 * Конструктор
		 * @param \IConnection $connection подключение к бд
		 * @param iHistory $history история репозитория
		 * @param iSchema $schema схема хранения сущности
		 * @param iAccessor $accessor аксессор атрибутов сущности
		 * @param iFactory $factory фабрика сущности
		 * @param iBuilder $builder строитель сущности
		 */
		public function __construct(
			\IConnection $connection,
			iHistory $history,
			iSchema $schema,
			iAccessor $accessor,
			iFactory $factory,
			iBuilder $builder
		);

		/**
		 * Возвращает сущность с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iEntity|null
		 * @throws \databaseException
		 * @throws \ReflectionException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает полный список сущностей
		 * @return iEntity[]
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		public function getAll();

		/**
		 * Возвращает список сущностей с заданными идентификаторами
		 * @param int[] $idList список идентификаторов
		 * @return iEntity[]
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getListByIdList(array $idList);

		/**
		 * Возвращает список сущностей с заданными значениями поля
		 * @param string $name имя поля
		 * @param array $valueList список значений
		 * @return iEntity[]
		 * @throws \ErrorException
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		public function getListByValueList($name, array $valueList);

		/**
		 * Возвращает список сущностей с заданным значением указанного атрибута
		 * @param string $name имя поля
		 * @param mixed $value значение
		 * @return iEntity[]
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getListBy($name, $value);

		/**
		 * Сохраняет сущность
		 * @param iEntity $entity сущность
		 * @return iEntity
		 * @throws \databaseException
		 * @throws \ReflectionException
		 * @throws \ErrorException
		 */
		public function save(iEntity $entity);

		/**
		 * Удаляет сущность с заданным идентификатором
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		public function delete($id);

		/**
		 * Удаляет список сущностей с заданными идентификаторами
		 * @param int[] $idList список идентификаторов
		 * @return $this
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		public function deleteList(array $idList);

		/**
		 * Очищает репозиторий
		 * @return $this
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		public function clear();

		/**
		 * Возвращает историю репозитория
		 * @return iHistory
		 */
		public function getHistory();
	}