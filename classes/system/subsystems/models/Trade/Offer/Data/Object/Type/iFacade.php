<?php
	namespace UmiCms\System\Trade\Offer\Data\Object\Type;

	use \iUmiObjectType as iType;
	use \iUmiObjectTypesCollection as iTypeCollection;

	/**
	 * Интерфейс фасада типов объектов данных торговых предложений
	 * @package UmiCms\System\Trade\Offer\Data\Object\Type
	 */
	interface iFacade {

		/** @var string ROOT_TYPE_GUID гуид корневого типа */
		const ROOT_TYPE_GUID = 'catalog-object';

		/**
		 * Конструктор
		 * @param iTypeCollection $typeCollection коллекция типов
		 */
		public function __construct(iTypeCollection $typeCollection);

		/**
		 * Возвращает тип с заданным идентификатором
		 * @param int $id идентификатор
		 * @return null|iType
		 * @throws \coreException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список типов по списку идентификаторов
		 * @param array $idList список идентификаторов
		 * @return iType[]
		 * @throws \databaseException
		 */
		public function getList(array $idList);

		/**
		 * Создает тип
		 * @param string $name название типа
		 * @return iType
		 * @throws \coreException
		 * @throws \ErrorException
		 */
		public function create($name);

		/**
		 * Возвращает корневой тип
		 * @return iType
		 * @throws \ErrorException
		 */
		public function getRootType();

		/**
		 * Удаляет тип с заданным идентификатором
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \coreException
		 * @throws \publicAdminException
		 * @throws \ErrorException
		 */
		public function delete($id);

		/**
		 * Определяет валидность типа с заданным идентификатором
		 * @param int $id идентификатор
		 * @return bool
		 * @throws \coreException
		 * @throws \ErrorException
		 */
		public function isValid($id);
	}