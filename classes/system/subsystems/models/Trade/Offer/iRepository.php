<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Entity\iRepository as iAbstractRepository;

	/**
	 * Интерфейс репозитория торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iRepository extends iAbstractRepository {

		/**
		 * Возвращает торговое предложение с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iOffer|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список торговых предложений с заданным значением указанного поля
		 * @param string $field имя поля
		 * @param mixed $value значение
		 * @return iOffer[]
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function getListBy($field, $value);

		/**
		 * Сохраняет торговое предложение
		 * @param iOffer|iEntity $offer торговое предложение
		 * @return iOffer
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function save(iEntity $offer);

		/**
		 * Удаляет торговое предложение с заданным идентификатором
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