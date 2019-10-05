<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Orm\Entity\iRepository as iAbstractRepository;

	/**
	 * Интерфейс репозитория типов цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	interface iRepository extends iAbstractRepository {

		/**
		 * Возвращает тип цены с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iType|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список типов цен с заданным значением указанного поля
		 * @param string $field имя поля
		 * @param mixed $value значение
		 * @return iType[]
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function getListBy($field, $value);

		/**
		 * Сохраняет тип цены
		 * @param iType|iEntity $type тип цены
		 * @return iType
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function save(iEntity $type);

		/**
		 * Удаляет тип цены с заданным идентификатором
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \databaseException
		 */
		public function delete($id);

		/**
		 * Очищает репозиторий
		 * @return $this
		 * @throws \databaseException
		 */
		public function clear();
	}