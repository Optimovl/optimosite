<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Entity\iRepository as iAbstractRepository;

	/**
	 * Интерфейс репозитория складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	interface iRepository extends iAbstractRepository {

		/**
		 * Возвращает складской остаток с заданным идентификатором
		 * @param int $id идентификатор складского остатка
		 * @return iBalance|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список складских остатков с заданным значением указанного поля
		 * @param string $field имя поля
		 * @param mixed $value значение
		 * @return iBalance[]
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function getListBy($field, $value);

		/**
		 * Сохраняет складской остаток
		 * @param iEntity|iBalance $balance складской остаток
		 * @return iBalance
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function save(iEntity $balance);

		/**
		 * Удаляет складской остаток с заданным идентификатором
		 * @param int $id идентификатор складского остатка
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function delete($id);

		/**
		 * Очищает репозиторий
		 * @throws \databaseException
		 */
		public function clear();
	}