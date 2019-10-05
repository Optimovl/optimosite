<?php

	namespace UmiCms\Classes\Components\Seo;

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Seo\Settings\Custom;

	/**
	 * Класс для управлением настройками SEO в административной панели
	 * @package UmiCms\Classes\Components\Seo
	 */
	class AdminSettingsManager implements iAdminSettingsManager {

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getParams() {
			return array_merge($this->getCommonParams(), $this->getCustomParams());
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setParams(array $params) {
			return $this->setCommonParams($params)
				->setCustomParams($params);
		}

		/**
		 * Сохраняет общие настройки
		 * @param array $params новые значения настроек
		 * @return $this
		 * @throws \Exception
		 */
		public function setCommonParams(array $params) {
			$emptyMetaTags = $params['empty-meta-tags'];

			Service::SeoSettingsFactory()
				->createCommon()
				->setAllowEmptyH1($emptyMetaTags['boolean:seo-empty-h1'])
				->setAllowEmptyTitle($emptyMetaTags['boolean:seo-empty-title'])
				->setAllowEmptyDescription($emptyMetaTags['boolean:seo-empty-description'])
				->setAllowEmptyKeywords($emptyMetaTags['boolean:seo-empty-keywords']);

			return $this;
		}

		/**
		 * Сохраняет настройки (общие + специфические для каждого сайта)
		 * @param array $params новые значения настроек
		 * @return $this
		 * @throws \Exception
		 */
		private function setCustomParams(array $params) {
			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$customParams = $params[$domain->getDecodedHost()];

				$this->createCustomSettings($domainId)
					->setTitlePrefix($customParams["string:seo-title-$domainId"])
					->setDefaultTitle($customParams["string:seo-default-title-$domainId"])
					->setDefaultKeywords($customParams["string:seo-keywords-$domainId"])
					->setDefaultDescription($customParams["string:seo-description-$domainId"])
					->setCaseSensitive($customParams["boolean:seo-is-case-sensitive-$domainId"])
					->setCaseSensitiveStatus($customParams["select:seo-case-sensitive-status-$domainId"])
					->setProcessRepeatedSlashes($customParams["boolean:seo-is-process-slashes-$domainId"])
					->setProcessRepeatedSlashesStatus($customParams["select:seo-process-slashes-status-$domainId"])
					->setAddIdToDuplicateAltName($customParams["boolean:seo-add-id-to-alt-name-$domainId"]);
			}
			return $this;
		}

		/**
		 * Возвращает настройки, специфические для каждого сайта на текущей языковой версии
		 * @return array
		 * @throws \Exception
		 */
		private function getCustomParams() {
			$params = [];

			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$settings = $this->createCustomSettings($domainId);
				$host = $domain->getDecodedHost();
				$params[$host] = [
					'status:domain' => $host,
					"string:seo-title-$domainId" => $settings->getTitlePrefix(),
					"string:seo-default-title-$domainId" => $settings->getDefaultTitle(),
					"string:seo-keywords-$domainId" => $settings->getDefaultKeywords(),
					"string:seo-description-$domainId" => $settings->getDefaultDescription(),
					"boolean:seo-is-case-sensitive-$domainId" => $settings->isCaseSensitive(),
					"select:seo-case-sensitive-status-$domainId" => $this->getSensitiveUrlOptions($domainId),
					"boolean:seo-is-process-slashes-$domainId" => $settings->isProcessRepeatedSlashes(),
					"select:seo-process-slashes-status-$domainId" => $this->getProcessSlashesOptions($domainId),
					"boolean:seo-add-id-to-alt-name-$domainId" => $settings->isAddIdToDuplicateAltName(),
				];
			}

			return $params;
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки
		 * "Способ обработки URL с повторяющимися слешами"
		 * @param int $domainId
		 * @return array
		 * @throws \Exception
		 */
		private function getProcessSlashesOptions($domainId) {
			$settings = $this->createCustomSettings($domainId);
			return array_merge(
				$this->getSlashesStatusList(),
				['value' => $settings->getProcessRepeatedSlashesStatus()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки
		 * "Способ обработки регистрозависимого URL"
		 * @param int $domainId
		 * @return array
		 * @throws \Exception
		 */
		private function getSensitiveUrlOptions($domainId) {
			$settings = $this->createCustomSettings($domainId);
			return array_merge(
				$this->getSensitiveUrlStatusList(),
				['value' => $settings->getCaseSensitiveStatus()]
			);
		}

		/**
		 * Возвращает список статусов настройки
		 * "Способ обработки URL с повторяющимися слешами"
		 * @return array
		 */
		private function getSlashesStatusList() {
			return [
				'redirect' => getLabel('option-delete-slashes-and-redirect'),
				'not-found' => getLabel('option-redirect-to-not-found-page'),
			];
		}

		/**
		 * Возвращает список статусов настройки
		 * "Способ обработки URL с повторяющимися слешами"
		 * @return array
		 */
		private function getSensitiveUrlStatusList() {
			return [
				'redirect' => getLabel('option-redirect-to-similar-url'),
				'not-found' => getLabel('option-redirect-to-not-found-page'),
			];
		}


		/**
		 * Создает SEO настройки для домена
		 * @param int $domainId идентификатор домена
		 * @return Custom
		 */
		private function createCustomSettings($domainId) {
			return Service::SeoSettingsFactory()->createCustom($domainId);
		}

		/**
		 * Возвращает общие настройки
		 * @return array
		 * @throws \Exception
		 */
		private function getCommonParams() {
			$settings = Service::SeoSettingsFactory()->createCommon();
			return [
				'empty-meta-tags' => [
					'boolean:seo-empty-h1' => $settings->isAllowEmptyH1(),
					'boolean:seo-empty-title' => $settings->isAllowEmptyTitle(),
					'boolean:seo-empty-description' => $settings->isAllowEmptyDescription(),
					'boolean:seo-empty-keywords' => $settings->isAllowEmptyKeywords()
				]
			];
		}
	}