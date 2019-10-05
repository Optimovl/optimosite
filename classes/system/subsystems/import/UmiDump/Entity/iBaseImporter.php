<?php
	namespace UmiCms\System\Import\UmiDump\Entity;

	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Интерфейс базового импортера сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity
	 */
	interface iBaseImporter {

		/** @var string INSTALL_ONLY_FLAG флаг необновляемой сущности */
		const INSTALL_ONLY_FLAG = 'install_only';

		/**
		 * Конструктор
		 * @param \DOMXPath $parser парсер импортируемого дампа
		 * @param iSourceIdBinder $relations
		 * @internal param int $sourceId идентификатор ресурса
		 */
		public function __construct(\DOMXPath $parser, iSourceIdBinder $relations);

		/**
		 * Импортирует все сущности
		 * @return array результат импорта
		 */
		public function import();

		/**
		 * Устанавливает идентификатор домена, в который будут принудительно импортированы
		 * все сущности, связанные с доменами:
		 *
		 * 1) Шаблоны дизайна;
		 * 2) Объектные типы;
		 * 3) Значения полей типа "Ссылка на домен";
		 * 4) Значения полей типа "Ссылка на список доменов";
		 * 5) Иерархические элементы (страницы);
		 * 6) Почтовые уведомления;
		 *
		 * @param int $id идентификатор домена
		 * @return $this
		 */
		public function setForcedDomainId($id);

		/**
		 * Устанавливает функцию обратного вызова для изменения путей файлов
		 * @param callable $callback
		 * @return $this
		 */
		public function setChangeFilePathCallback(callable $callback);

		/**
		 * Возвращает связывателя идентификаторов импортируемых сущностей с системными
		 * @return iSourceIdBinder
		 */
		public function getSourceIdBinder();

		/**
		 * Записывает в журнал о создании сущности и обновляет счетчик
		 * @param string $id идентификатор сущности
		 */
		public function logCreated($id);

		/**
		 * Записывает в журнал об обновлении сущности и обновляет счетчик
		 * @param string $id идентификатор сущности
		 */
		public function logUpdated($id);

		/**
		 * Записывает в журнал ошибок сообщение об ошибке
		 * @param string $message сообщение об ошибке
		 */
		public function logError($message);

		/**
		 * Возвращает результат импорта
		 * @return array
		 */
		public function getResult();
	}