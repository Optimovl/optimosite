<?php

	use UmiCms\Service;
	use UmiCms\System\Trade\iOffer;

	/** Класс xml транслятора (сериализатора) значений полей объектов */
	class umiObjectPropertyWrapper extends translatorWrapper {

		/** @var array $privateProperties список приватных свойств (для них не выводится информация) */
		private $privateProperties = ['user_dock', 'user_settings_data', 'activate_code'];

		/** @var bool $showEmptyFields Нужно ли выводить пустые поля */
		public static $showEmptyFields = false;

		/**
		 * @inheritdoc
		 * @param iUmiObjectProperty $object
		 * @throws Exception
		 * @throws coreException
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует значение поля объекта в массив с разметкой для последующей сериализации в xml
		 * @param iUmiObjectProperty $property значение поля объекта
		 * @return array
		 * @throws Exception
		 * @throws coreException
		 */
		protected function translateData(iUmiObjectProperty $property) {
			$field = $property->getField();

			if (!$field instanceof iUmiField) {
				return [];
			}

			$dataType = $field->getDataType();

			if ($this->isPrivateProperty($field->getName(), $dataType)) {
				return [];
			}

			$value = $property->getValue();

			if ($this->isEmptyValue($value)) {
				return [];
			}

			$isImportant = (int) $field->isImportant();

			$result = [
				'@id' => $field->getId(),
				'@object-id' => $property->getObjectId(),
				'@name' => $field->getName(),
				'@type' => $dataType,
				'title' => $field->getTitle(),
				'@is-important' => "$isImportant"
			];

			$value = $this->translatePropertyValue($field, $property, $value);

			if (isset($value['nodes:value'])) {
				$result += $value;
			} else {
				$result['value'] = $value;
			}

			if ($dataType == 'relation' && $field->getFieldType()->getIsMultiple()) {
				$result['@multiple'] = 'multiple';
			}

			if ($dataType == 'string') {
				$restriction = baseRestriction::get($field->getRestrictionId());

				if ($restriction instanceof baseRestriction) {
					$result['@restriction'] = $restriction->getClassName();
				}
			}

			$guideId = $field->getGuideId();

			if ($guideId) {
				$result['@guide-id'] = $guideId;
				$guide = umiObjectTypesCollection::getInstance()
					->getType($guideId);
				$isPublic = ($guide instanceof iUmiObjectType) ? $guide->isPublicGuide() : false;
				$result['@public-guide'] = (int) $isPublic;
			}

			return $result;
		}

		/**
		 * Преобразует значение в массив с разметкой для последующей сериализации в xml
		 * @param iUmiField $field поле
		 * @param iUmiObjectProperty $property значение поля объекта
		 * @param mixed $value значение
		 * @return array
		 * @throws Exception
		 */
		private function translatePropertyValue(iUmiField $field, iUmiObjectProperty $property, $value) {

			switch ($field->getDataType()) {
				case 'swf_file':
				case 'file':
				case 'video_file':
				case 'img_file': {
					return $this->translateFileValue($value);
				}

				case 'symlink': {
					return $this->translateSymLinkValue($value);
				}

				case 'relation': {
					return $this->translateRelationValue($value);
				}

				case 'domain_id': {
					return $this->translateDomainIdValue($value);
				}

				case 'domain_id_list' : {
					return $this->translateDomainIdListValue($value);
				}

				case 'offer_id' : {
					return $this->translateOfferIdValue($value);
				}

				case 'offer_id_list' : {
					return $this->translateOfferIdListValue($value);
				}

				case 'date': {
					return $this->translateDateValue($value);
				}

				case 'optioned': {
					return $this->translateOptionedValue($value);
				}

				default: {
					return $this->translateDefaultValue($field, $property->getObjectId(), $value);
				}
			}
		}

		/**
		 * Преобразует значение поля типа "Изображение", "Файл", "SVF", "Video"
		 * в массив с разметкой для последующей сериализации в xml
		 * @param iUmiFile|iUmiImageFile $file значение поля типа "Изображение", "Файл", "SVF", "Video"
		 * @return array
		 */
		private function translateFileValue($file) {
			if (!$file instanceof umiFile) {
				return [];
			}

			$path = $file->getFilePath(true);
			$regexp = '|^' . CURRENT_WORKING_DIR . '|';
			$path = preg_replace($regexp, '', $path);

			if ($file->getIsBroken()) {
				return [
					'@path' => '.' . $path,
					'@is_broken' => 1,
					'#value' => $path,
				];
			}

			$result = [
				'@path' => '.' . $path,
				'@folder' => preg_replace($regexp, '', $file->getDirName()),
				'@name' => $file->getFileName(),
				'@ext' => $file->getExt(),
				'@is_broken' => '0',
				'#value' => $path,
			];

			if ($file instanceof iUmiImageFile) {
				$result['@width'] = $file->getWidth();
				$result['@height'] = $file->getHeight();
				$result['@alt'] = $file->getAlt();
				$result['@title'] = $file->getTitle();
			}

			return $result;
		}

		/**
		 * Преобразует значение поля типа "Ссылка на дерево" в массив с разметкой для последующей сериализации в xml
		 * @param iUmiHierarchyElement[] $pageList значение поля типа "Ссылка на дерево"
		 * @return array
		 */
		private function translateSymLinkValue($pageList) {
			$result = [
				'nodes:page' => []
			];

			foreach ($pageList as $page) {
				$result['nodes:page'][] = $page;
			}

			return $result;
		}

		/**
		 * Преобразует значение поля типа "Выпадающий список" в массив с разметкой для последующей сериализации в xml
		 * @param int|int[] $objectIdOrList значение поля типа "Выпадающий список"
		 * @return array
		 */
		private function translateRelationValue($objectIdOrList) {
			$objectCollection = umiObjectsCollection::getInstance();

			$result = [
				'nodes:item' => []
			];

			if (is_array($objectIdOrList)) {
				foreach ($objectIdOrList as $objectId) {
					$result['nodes:item'][] = $objectCollection->getObject($objectId);
				}
			} else {
				$result['item'] = $objectCollection->getObject($objectIdOrList);
			}

			return $result;
		}

		/**
		 * Преобразует значение поля типа "Ссылка на домен" в массив с разметкой для последующей сериализации в xml
		 * @param int $domainId значение поля типа "Ссылка на домен"
		 * @return array
		 * @throws Exception
		 */
		private function translateDomainIdValue($domainId) {
			return [
				'domain' => Service::DomainCollection()
					->getDomain($domainId)
			];
		}

		/**
		 * Преобразует значение поля типа "Ссылка на список доменов" в массив с разметкой
		 * для последующей сериализации в xml
		 * @param int[] $domainIdList значение поля типа "Ссылка на список доменов"
		 * @return array
		 * @throws Exception
		 */
		private function translateDomainIdListValue($domainIdList) {
			$domainIdList = (array) $domainIdList;

			$result = [
				'nodes:domain' => []
			];

			$domainCollection = Service::DomainCollection();

			foreach ($domainIdList as $domainId) {
				$result['nodes:domain'][] = $domainCollection->getDomain($domainId);
			}

			return $result;
		}

		/**
		 * Преобразует значение поля типа "Ссылка на торговое предложение" в массив с разметкой
		 * для последующей сериализации в xml
		 * @param int $offerId значение поля типа "Ссылка на торговое предложение"
		 * @return array
		 * @throws ErrorException
		 * @throws databaseException
		 */
		private function translateOfferIdValue($offerId) {
			return [
				'offer' => Service::TradeOfferFacade()
					->get($offerId)
			];
		}

		/**
		 * Преобразует значение поля типа "Ссылка на список торговых предложений" в массив с разметкой
		 * для последующей сериализации в xml
		 * @param int[] $offerIdList значение поля типа "Ссылка на список торговых предложений"
		 * @return array
		 * @throws ErrorException
		 * @throws ReflectionException
		 * @throws databaseException
		 * @throws Exception
		 */
		private function translateOfferIdListValue($offerIdList) {
			$offerIdList = (array) $offerIdList;
			$tradeOfferFacade = Service::TradeOfferFacade();
			$offerCollection = $tradeOfferFacade->mapCollection($tradeOfferFacade->getList($offerIdList));
			$tradeOfferFacade->loadRelations($offerCollection);
			$offerList = $offerCollection->getList();

			if (Service::Request()->isSite()) {
				$offerList = array_filter($offerList, function(iOffer $offer) {
					return $offer->isActive();
				});
			}

			$result = [
				'attribute:count' => count($offerList),
				'nodes:offer' => []
			];

			foreach ($offerList as $offer) {
				$result['nodes:offer'][] = $offer;
			}

			return $result;
		}

		/**
		 * Преобразует значение поля типа "Дата" в массив с разметкой для последующей сериализации в xml
		 * @param iUmiDate $date значение поля типа "Дата"
		 * @return array
		 */
		private function translateDateValue($date) {
			if (!$date instanceof iUmiDate) {
				return [];
			}

			$format = $this->getDateFormat();

			return [
				'@formatted-date' => $date->getFormattedDate('d.m.Y H:i'),
				'@unix-timestamp' => $date->getFormattedDate('U'),
				'#rfc' => ($date->getDateTimeStamp() > 0) ? $date->getFormattedDate($format) : ''
			];
		}

		/**
		 * Возвращает формат даты
		 * @return bool|string
		 */
		private function getDateFormat() {
			$magicMethods = ['get_editable_region', 'save_editable_region'];
			$cmsController = cmsController::getInstance();
			return in_array($cmsController->getCurrentMethod(), $magicMethods) ? false : 'r';
		}

		/**
		 * Преобразует значение поля типа "Составное" в массив с разметкой для последующей сериализации в xml
		 * @param array $value значение поля типа "Составное"
		 * @return array
		 */
		private function translateOptionedValue($value) {
			$value = (array) $value;
			$pageCollection = umiHierarchy::getInstance();
			$objectCollection = umiObjectsCollection::getInstance();
			$optionInfoList = [];

			foreach ($value as $option) {

				$optionInfo = [];

				foreach ($option as $optionType => $optionValue) {
					switch ($optionType) {
						case 'tree': {
							$page = $pageCollection->getElement($optionValue);

							if ($page instanceof iUmiHierarchyElement) {
								$optionInfo['page'] = $page;
							}

							break;
						}

						case 'rel': {
							$object = $objectCollection->getObject($optionValue);

							if ($object instanceof iUmiObject) {
								$optionInfo['object'] = $object;
							}

							break;
						}

						default: {
							$optionInfo['@' . $optionType] = $optionValue;
						}
					}
				}

				$optionInfoList[] = $optionInfo;
			}

			return [
				'nodes:option' => $optionInfoList
			];
		}

		/**
		 * Преобразует значение поля в массив с разметкой для последующей сериализации в xml
		 * @param iUmiField $field поле
		 * @param int $objectId идентификатор объекта
		 * @param mixed $value значение
		 * @return array
		 * @throws coreException
		 * @throws Exception
		 */
		private function translateDefaultValue(iUmiField $field, $objectId, $value) {
			$dataType = $field->getDataType();

			if (is_array($value)) {
				$result = [
					'nodes:value' => $value
				];

				if ($dataType == 'tags') {
					$result['combined'] = implode(', ', $value);
				}

				return $result;
			}

			$value = xmlTranslator::executeMacroses($value, false, $objectId);

			if (defined('XML_PROP_VALUE_MODE') && $dataType == 'wysiwyg' && Service::Request()->isNotAdmin()) {
				if (XML_PROP_VALUE_MODE == 'XML') {
					return [
						'xml:xvalue' => "<xvalue>{$value}</xvalue>"
					];
				}
			}

			return [
				'#value' => $value
			];
		}

		/**
		 * Определяет, считается ли свойство приватным
		 * @param string $name название
		 * @param string $dataType тип данных
		 * @return bool
		 */
		private function isPrivateProperty($name, $dataType) {
			if (xmlTranslator::$showUnsecureFields) {
				return false;
			}
			return $dataType == 'password' || in_array($name, $this->privateProperties);
		}

		/**
		 * Определяет, считается ли значение свойства пустым
		 * @param mixed $value значение свойства
		 * @return bool
		 */
		private function isEmptyValue($value) {
			if (self::$showEmptyFields || translatorWrapper::$showEmptyFields) {
				return false;
			}

			if (is_object($value)) {
				return false;
			}

			if (is_array($value)) {
				return empty($value);
			}

			return (string) $value === '';
		}
	}
