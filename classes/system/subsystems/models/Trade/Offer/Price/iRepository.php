<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Orm\Entity\iRepository as iAbstractRepository;

	/**
	 * Интерфейс репозитория цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	interface iRepository extends iAbstractRepository {

		/**
		 * Возвращает цену с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iPrice|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список цен с заданным значением указанного поля
		 * @param string $field имя поля
		 * @param mixed $value значение
		 * @return iPrice[]
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function getListBy($field, $value);

		/**
		 * Сохраняет цену
		 * @param iPrice|iEntity $price цена
		 * @return iPrice
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function save(iEntity $price);

		/**
		 * Удаляет цену с заданным идентификатором
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