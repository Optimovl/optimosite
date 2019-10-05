<?php

	/** Управляет шаблонами дизайна (iTemplate) в системе */
	interface iTemplatesCollection {

		/**
		 * Добавляет новый шаблон дизайна и возвращает его идентификатор.
		 *
		 * @param string $filename название файла, который содержит шаблон дизайна
		 *
		 * @param string $title название шаблона
		 *
		 * @param int|bool $domainId идентификатор домена, для которого создается шаблон.
		 * Если не указан, используется домен по умолчанию.
		 *
		 * @param int|bool $langId идентификатор языка, для которого создается шаблон.
		 * Если не указан, используется язык по умолчанию.
		 *
		 * @param bool $isDefault если true, то шаблон станет шаблоном по умолчанию
		 * для своей комбинации домена/языка.
		 *
		 * @return int
		 */
		public function addTemplate(
			$filename,
			$title,
			$domainId = false,
			$langId = false,
			$isDefault = false
		);

		/**
		 * Устанавливает шаблон шаблоном по умолчанию для комбинации домена/языка
		 * и возвращает результат операции.
		 *
		 * @param int $templateId идентификатор шаблона дизайна
		 * @param int|bool $domainId идентификатор домена. Если не указан, берется домен по умолчанию.
		 * @param int|bool $langId идентификатор языка. Если не указан, берется язык по умолчанию.
		 * @return bool
		 */
		public function setDefaultTemplate($templateId, $domainId = false, $langId = false);

		/**
		 * Удаляет шаблон дизайна и возвращает результат операции.
		 * @param int $id идентификатор шаблона дизайна
		 * @return bool
		 */
		public function delTemplate($id);

		/**
		 * Возвращает список всех шаблонов дизайна для комбинации домен/язык
		 * @param int $domainId идентификатор домена
		 * @param int $langId идентификатор языка
		 * @return iTemplate[]
		 */
		public function getTemplatesList($domainId, $langId);

		/**
		 * Возвращает список всех шаблонов дизайна
		 * @return iTemplate[]
		 */
		public function getFullTemplatesList();

		/**
		 * Возвращает шаблон дизайна по умолчанию для комбинации домен/язык.
		 *
		 * Если для комбинации домен/язык нет шаблона по умолчанию,
		 * то первый найденный шаблон с указанным доменом/языком станет шаблоном по умолчанию.
		 *
		 * Возвращает false, если у домена/языка нет ни одного шаблона.
		 *
		 * @param int|bool $domainId идентификатор домена. Если не указан, берется домен по умолчанию.
		 * @param int|bool $langId идентификатор языка. Если не указан, берется язык по умолчанию.
		 * @return iTemplate|bool
		 */
		public function getDefaultTemplate($domainId = false, $langId = false);

		/**
		 * Возвращает текущий шаблон дизайна или false, если нет ни одного шаблона.
		 * @return iTemplate|bool
		 */
		public function getCurrentTemplate();

		/**
		 * Возвращает идентификатор шаблона, соответствующего иерархическому типу (модуль/метод),
		 * или false, если такого шаблона не существует.
		 *
		 * @param string $module модуль
		 * @param string $method метод
		 * @return int|bool
		 */
		public function getHierarchyTypeTemplate($module, $method);

		/**
		 * Возвращает шаблон дизайна по его идентификатору или false, если шаблон не найден.
		 * @param int $id идентификатор шаблона дизайна
		 * @return iTemplate|bool
		 */
		public function getTemplate($id);

		/**
		 * Определяет, существует ли шаблон дизайна с указанным идентификатором
		 * @param int $id идентификатор шаблона дизайна
		 * @return bool
		 */
		public function isExists($id);

		/**
		 * Определяет, существует ли шаблон дизайна с указанным идентификатором
		 * на указанном сайте (домен + язык).
		 * @param int $templateId идентификатор шаблона дизайна
		 * @param int|bool $domainId идентификатор домена. Если не указан, берется домен по умолчанию.
		 * @param int|bool $langId идентификатор языка. Если не указан, берется язык по умолчанию.
		 * @return bool
		 */
		public function isExistsForSite($templateId, $domainId = false, $langId = false);

		/**
		 * Возвращает первый шаблон с заданным именем
		 * @param string $name имя шаблона
		 * @return iTemplate|null
		 */
		public function getFirstByName($name);

		/**
		 * Возвращает список шаблонов с заданным именем
		 * @param string $name имя шаблона
		 * @return iTemplate[]
		 */
		public function getListByName($name);

		/**
		 * Сбрасывает внутренний кэш и заново загружает
		 * список всех шаблонов дизайна в системе из БД.
		 */
		public function clearCache();
	}
