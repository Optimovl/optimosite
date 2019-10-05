<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Orm\Entity\iFacade as iAbstractFacade;
	use UmiCms\System\Trade\Offer\Price\Type\iCollection as iTypeCollection;


	/**
	 * Интерфейс фасада типов цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	interface iFacade extends iAbstractFacade {

		/**
		 * Возвращает тип цены по ее идентификатору
		 * @param int $id идентификатор типа
		 * @return iType|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает группу по названию
		 * @param string $name название
		 * @return iType|null
		 */
		public function getByName($name);

		/**
		 * Возвращает список типов цен с заданными идентификаторами
		 * @param int[] $idList список идентификаторов
		 * @return iType[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getList(array $idList);

		/**
		 * Возвращает группу по умолчанию
		 * @return iType|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getDefault();

		/**
		 * Устанавливает тип по умолчанию
		 * @param iType $type тип
		 * @return iEntity|iType
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function setDefault(iType $type);

		/**
		 * Возвращает коллекцию типов цен с заданным значением указанного атрибута
		 * @param string $name имя поля
		 * @param mixed $value значение
		 * @return iTypeCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionBy($name, $value);

		/**
		 * Возвращает коллекцию типов цен со значением указанного атрибута из
		 * заданного списка
		 * @param string $name имя поля
		 * @param array $valueList список значений
		 * @return iTypeCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByValueList($name, array $valueList);

		/**
		 * Формирует коллекцию типов цен
		 * @param array $typeList список типов цен
		 * @return iTypeCollection
		 */
		public function mapCollection(array $typeList);

		/**
		 * Создает тип цены
		 * @param array $attributeList атрибуты типа цены
		 * @return iType
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function create(array $attributeList = []);

		/**
		 * Сохраняет тип цены
		 * @param iEntity|iType $price тип цены
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function save(iEntity $price);

		/**
		 * Удаляет тип цены
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function delete($id);
	}