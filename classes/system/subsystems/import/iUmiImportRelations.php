<?php

	/**
	 * Интерфейс класса управления связями между идентификаторами импортированных/импортируемых системных объектов
	 * и их идентификаторами из внешних источников.
	 *
	 * Для каждой импортируемой сущности сохраняется ее оригинальный идентификатор, чтобы при повторном импорте
	 * данных из заданного внешнего источника сущности были обновлены, а не созданы вновь.
	 *
	 * Например, из 1С приходит <товар> с <ид> равным "42907251-d287-11de-9943-000fea605ee9".
	 * UMI.CMS создает объект umiHierarchyElement с идентификатором id = 3242,
	 * и записывает в таблицу cms3_import_relations следующую запись:
	 *
	 * source_id    old_id                               new_id
	 * 123          42907251-d287-11de-9943-000fea605ee9 3242
	 *
	 * Когда в следующий раз из 1С опять придет <товар> с <ид> равным "42907251-d287-11de-9943-000fea605ee9",
	 * то заново его создавать UMI.CMS не будет, система просто обновит страницу с id = 3242.
	 */
	interface iUmiImportRelations extends iSingleton {

		/**
		 * Возвращает идентификатор внешнего источника по его названию
		 * @param string $name название внешнего источника
		 * @return int|bool
		 */
		public function getSourceId($name);

		/**
		 * Возвращает имя внешнего источника по его идентификатору
		 * @param int $id идентификатор внешнего источника
		 * @return string|bool
		 */
		public function getSourceName($id);

		/**
		 * Создает новый внешний источник и возвращает его идентификатор.
		 * Если источник с заданным названием уже существует, то его дубль создан не будет.
		 * @param string $name название внешнего источника
		 * @return int|bool
		 */
		public function addNewSource($name);

		/**
		 * Удаляет внешний источник с заданным идентификатором
		 * @param int $id идентфикатор внешнего источника
		 * @return $this
		 */
		public function deleteSource($id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами страницы.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор страницы, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор страницы, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldIdRelation($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами объекта.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setObjectIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор объекта, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewObjectIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор объекта, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldObjectIdRelation($sourceId, $id);

		/**
		 * Определяет связан ли импортированный объект с другими внешними источниками,
		 * то есть обновлялся или создавался ли он в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isObjectRelatedToAnotherSource($sourceId, $id);

		/**
		 * Возвращает идентификатор ресурса по внутреннему идентификатору объекта
		 * @param int $id внутренний идентификатор объекта
		 * @return int|null
		 */
		public function getSourceIdByObjectId($id);

		/**
		 * Возвращает идентификатор ресурса по внутреннему идентификатору шаблона
		 * @param int $id внутренний идентификатор шаблона
		 * @return int|null
		 */
		public function getSourceIdByTemplateId($id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами объектного типа данных.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setTypeIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор объектного типа данных, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewTypeIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор объектного типа данных, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldTypeIdRelation($sourceId, $id);

		/**
		 * Определяет связан ли импортированный тип с другими внешними источниками,
		 * то есть обновлялся или создавался ли оно в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isTypeRelatedToAnotherSource($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами поля.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $typeId идентификатор объектного типа данных, с которым связано поле
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setFieldIdRelation($sourceId, $typeId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор поля, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $typeId идентификатор объектного типа данных, с которым связано поле
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewFieldId($sourceId, $typeId, $extId);

		/**
		 * Возвращает внешний идентификатор поля, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $typeId идентификатор объектного типа данных, с которым связано поле
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldFieldName($sourceId, $typeId, $id);

		/**
		 * Определяет связано ли импортированное поле с другими внешними источниками,
		 * то есть обновлялось или создавалось ли оно в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isFieldRelatedToAnotherSource($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами группы полей.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $typeId идентификатор объектного типа данных, с которым связана группа
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setGroupIdRelation($sourceId, $typeId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор группы полей, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $typeId идентификатор объектного типа данных, с которым связано поле
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewGroupId($sourceId, $typeId, $extId);

		/**
		 * Возвращает внешний идентификатор группы полей, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $typeId идентификатор объектного типа данных, с которым связано поле
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldGroupName($sourceId, $typeId, $id);

		/**
		 * Определяет связана ли импортированная группа с другими внешними источниками,
		 * то есть обновлялась или создавалась ли она в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isGroupRelatedToAnotherSource($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами домена.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setDomainIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор домена, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewDomainIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор домена, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldDomainIdRelation($sourceId, $id);

		/**
		 * Определяет связан ли импортированный домен с другими внешними источниками,
		 * то есть обновлялся или создавался ли он в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isDomainRelatedToAnotherSource($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами зеркала домена.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setDomainMirrorIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор зеркала домена, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewDomainMirrorIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор зеркала домена, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldDomainMirrorIdRelation($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами языка.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setLangIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор языка, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewLangIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор языка, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldLangIdRelation($sourceId, $id);

		/**
		 * Определяет связан ли импортированный язык с другими внешними источниками,
		 * то есть обновлялся или создавался ли он в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isLangRelatedToAnotherSource($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами шаблона.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setTemplateIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор шаблона, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewTemplateIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор шаблона, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldTemplateIdRelation($sourceId, $id);

		/**
		 * Определяет связан ли импортированный шаблон с другими внешними источниками,
		 * то есть обновлялся или создавался ли он в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isTemplateRelatedToAnotherSource($sourceId, $id);

		/**
		 * Устанавливает связь между внешним и внутренним идентификаторами ограничения поля.
		 * Старые связи с такими же внутренними или внешними идентификаторами будут удалены.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function setRestrictionIdRelation($sourceId, $extId, $id);

		/**
		 * Возвращает внутренний идентификатор ограничения поля, соответствующий внешнему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param string $extId внешний идентификатор
		 * @return int|bool
		 */
		public function getNewRestrictionIdRelation($sourceId, $extId);

		/**
		 * Возвращает внешний идентификатор ограничения поля, соответствующий внутреннему идентификатору
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return string|bool
		 */
		public function getOldRestrictionIdRelation($sourceId, $id);

		/**
		 * Определяет связано ли импортированное ограничение поля с другими внешними источниками,
		 * то есть обновлялось или создавалось ли он в рамках работы с другими источниками.
		 * @param int $sourceId идентификатор внешнего источника
		 * @param int $id внутренний идентификатор
		 * @return bool
		 */
		public function isRestrictionRelatedToAnotherSource($sourceId, $id);
	}
