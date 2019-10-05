<?php
	namespace UmiCms\System\Trade\Stock;

	use UmiCms\System\Trade\iStock;
	use \iUmiObject as iDataObject;

	/**
	 * Интерфейс фабрики складов
	 * @package UmiCms\System\Trade\Stock
	 */
	interface iFactory {

		/** @var string TYPE_GUID гуид типа объектов данных склада */
		const TYPE_GUID = 'emarket-store';

		/**
		 * Создает экземпляр склада
		 * @param iDataObject $dataObject объект данных
		 * @return iStock
		 * @throws \Exception
		 * @throws \ErrorException
		 */
		public function create(iDataObject $dataObject);
	}