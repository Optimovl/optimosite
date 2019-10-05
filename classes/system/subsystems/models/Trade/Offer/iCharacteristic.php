<?php
	namespace UmiCms\System\Trade\Offer;

	use \iUmiField as iField;
	use \iUmiObject as iObject;
	use UmiCms\System\Orm\iEntity;
	use \iUmiObjectsCollection as iObjectFacade;

	/**
	 * Интерфейс характеристики торгового предложения
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iCharacteristic extends iEntity {

		/**
		 * Конструктор
		 * @param iField $field поле
		 * @param iObjectFacade $objectFacade фасад объектов
		 */
		public function __construct(iField $field, iObjectFacade $objectFacade);

		/**
		 * Возвращает имя характеристики
		 * @return string
		 */
		public function getName();

		/**
		 * Возвращает заголовок характеристики
		 * @return string
		 */
		public function getTitle();

		/**
		 * Возвращает тип поля характеристики
		 * @return string
		 */
		public function getFieldType();

		/**
		 * Возвращает тип отображения характеристики
		 * @return string
		 * @throws \ErrorException
		 */
		public function getViewType();

		/**
		 * Определяет может ли характеристика иметь несколько значений
		 * @return bool
		 */
		public function isMultiple();

		/**
		 * Возвращает идентификатор связанного справочника
		 * @return int|null
		 */
		public function getGuideId();

		/**
		 * Возвращает значение характеристики
		 * @return mixed
		 */
		public function getValue();

		/**
		 * Возвращает "сырое", то есть неподготовленное значение характеристики
		 * @return mixed
		 */
		public function getRawValue();

		/**
		 * Устанавливает значение характеристики
		 * @param mixed $value значение
		 * @return $this
		 */
		public function setValue($value);

		/**
		 * Определяет задан ли объект данных
		 * @return bool
		 */
		public function hasDataObject();

		/**
		 * Возвращает идентификатор объекта данных
		 * @return int|null
		 */
		public function getDataObjectId();

		/**
		 * Устанавливает объект данных
		 * @param iObject $dataObject объект данных
		 * @return $this
		 */
		public function setDataObject(iObject $dataObject);

		/**
		 * Возвращает поле
		 * @return iField
		 */
		public function getField();

		/**
		 * Возвращает значение поля объекта
		 * @return \iUmiObjectProperty|null
		 */
		public function getProperty();
	}