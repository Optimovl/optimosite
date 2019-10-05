<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;

	/**
	 * Интерфейс фабрики сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param iEntity $dummyEntity шаблонная сущность
		 */
		public function __construct(iEntity $dummyEntity);

		/**
		 * Создает сущность
		 * @return iEntity
		 * @throws \Exception
		 */
		public function create();
	}