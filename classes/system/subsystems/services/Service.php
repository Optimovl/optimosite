<?php

	namespace UmiCms;

	/**
	 * Класс фасада для получения сервисов
	 * @package UmiCms\Service
	 */
	class Service implements iService {

		/** @inheritdoc */
		public static function get($serviceName) {
			return self::getServiceContainer()->get($serviceName);
		}

		/** @inheritdoc */
		public static function getNew($serviceName) {
			return self::getServiceContainer()->getNew($serviceName);
		}

		/** @inheritdoc */
		public static function Redirects() {
			return self::getServiceContainer()->get('Redirects');
		}

		/** @inheritdoc */
		public static function UrlFactory() {
			return self::getServiceContainer()->get('UrlFactory');
		}

		/** @inheritdoc */
		public static function MailVariables() {
			return self::getServiceContainer()->get('MailVariables');
		}

		/** @inheritdoc */
		public static function MailTemplates() {
			return self::getServiceContainer()->get('MailTemplates');
		}

		/** @inheritdoc */
		public static function MailNotifications() {
			return self::getServiceContainer()->get('MailNotifications');
		}

		/** @inheritdoc */
		public static function Auth() {
			return self::getServiceContainer()->get('Auth');
		}

		/** @inheritdoc */
		public static function CsrfProtection() {
			return self::getServiceContainer()->get('CsrfProtection');
		}

		/** @inheritdoc */
		public static function SystemUsersPermissions() {
			return self::getServiceContainer()->get('SystemUsersPermissions');
		}

		/** @inheritdoc */
		public static function PasswordHashAlgorithm() {
			return self::getServiceContainer()->get('PasswordHashAlgorithm');
		}

		/** @inheritdoc */
		public static function Request() {
			return self::getServiceContainer()->get('Request');
		}

		/** @inheritdoc */
		public static function CookieJar() {
			return self::getServiceContainer()->get('CookieJar');
		}

		/** @inheritdoc */
		public static function Session() {
			return self::getServiceContainer()->get('Session');
		}

		/** @inheritdoc */
		public static function ActionFactory() {
			return self::getServiceContainer()->get('ActionFactory');
		}

		/** @inheritdoc */
		public static function ManifestFactory() {
			return self::getServiceContainer()->get('ManifestFactory');
		}

		/** @inheritdoc */
		public static function TransactionFactory() {
			return self::getServiceContainer()->get('TransactionFactory');
		}

		/** @inheritdoc */
		public static function EventPointFactory() {
			return self::getServiceContainer()->get('EventPointFactory');
		}

		/** @inheritdoc */
		public static function SiteMapUpdater() {
			return self::getServiceContainer()->get('SiteMapUpdater');
		}

		/** @inheritdoc */
		public static function ObjectsCollection() {
			return self::getServiceContainer()->get('objects');
		}

		/** @inheritdoc */
		public static function ObjectsTypesCollection() {
			return self::getServiceContainer()->get('objectTypes');
		}

		/** @inheritdoc */
		public static function ConnectionPool() {
			return self::getServiceContainer()->get('connectionPool');
		}

		/** @inheritdoc */
		public static function TypesHelper() {
			return self::getServiceContainer()->get('typesHelper');
		}

		/** @inheritdoc */
		public static function HierarchyTypesCollection() {
			return self::getServiceContainer()->get('hierarchyTypes');
		}

		/** @inheritdoc */
		public static function ObjectPropertyFactory() {
			return self::getServiceContainer()->get('objectPropertyFactory');
		}

		/** @inheritdoc */
		public static function CacheKeyGenerator() {
			return self::getServiceContainer()->get('CacheKeyGenerator');
		}

		/** @inheritdoc */
		public static function CacheEngineFactory() {
			return self::getServiceContainer()->get('CacheEngineFactory');
		}

		/** @inheritdoc */
		public static function CountryFactory() {
			return self::getServiceContainer()->get('CountriesFactory');
		}

		/** @inheritdoc */
		public static function CityFactory() {
			return self::getServiceContainer()->get('CitiesFactory');
		}

		/** @inheritdoc */
		public static function RestrictionCollection() {
			return self::getServiceContainer()->get('RestrictionCollection');
		}

		/** @inheritdoc */
		public static function DirectoryFactory() {
			return self::getServiceContainer()->get('DirectoryFactory');
		}

		/** @inheritdoc */
		public static function FileFactory() {
			return self::getServiceContainer()->get('FileFactory');
		}

		/** @inheritdoc */
		public static function ImageFactory() {
			return self::getServiceContainer()->get('ImageFactory');
		}

		/** @inheritdoc */
		public static function ImportEntitySourceIdBinderFactory() {
			return self::getServiceContainer()->get('ImportEntitySourceIdBinderFactory');
		}

		/** @inheritdoc */
		public static function UmiDumpDemolisherExecutor() {
			return self::getServiceContainer()->get('UmiDumpDemolisherExecutor');
		}

		/** @inheritdoc */
		public static function ExtensionRegistry() {
			return self::getServiceContainer()->get('ExtensionRegistry');
		}

		/** @inheritdoc */
		public static function ExtensionLoader() {
			return self::getServiceContainer()->get('ExtensionLoader');
		}

		/** @inheritdoc */
		public static function ModulePermissionLoader() {
			return self::getServiceContainer()->get('ModulePermissionLoader');
		}

		/** @inheritdoc */
		public static function CacheFrontend() {
			return self::getServiceContainer()->get('CacheFrontend');
		}

		/** @inheritdoc */
		public static function CacheKeyValidatorFactory() {
			return self::getServiceContainer()->get('CacheKeyValidatorFactory');
		}

		/** @inheritdoc */
		public static function BrowserDetector() {
			return self::getServiceContainer()->get('BrowserDetector');
		}

		/** @inheritdoc */
		public static function StaticCache() {
			return self::getServiceContainer()->get('StaticCache');
		}

		/** @inheritdoc */
		public static function Response() {
			return self::getServiceContainer()->get('Response');
		}

		/** @inheritdoc */
		public static function Configuration() {
			return self::getServiceContainer()->get('Configuration');
		}

		/** @inheritdoc */
		public static function BrowserCache() {
			return self::getServiceContainer()->get('BrowserCache');
		}

		/** @inheritdoc */
		public static function QuickExchange() {
			return self::getServiceContainer()->get('QuickExchange');
		}

		/** @inheritdoc */
		public static function SelectorFactory() {
			return self::getServiceContainer()->get('SelectorFactory');
		}

		/** @inheritdoc */
		public static function DataObjectFactory() {
			return self::getServiceContainer()->get('DataObjectFactory');
		}

		/** @inheritdoc */
		public static function HierarchyElementFactory() {
			return self::getServiceContainer()->get('HierarchyElementFactory');
		}

		/** @inheritdoc */
		public static function Registry() {
			return self::getServiceContainer()->get('Registry');
		}

		/** @inheritdoc */
		public static function RegistrySettings() {
			return self::getServiceContainer()->get('RegistrySettings');
		}

		/** @inheritdoc */
		public static function DateFactory() {
			return self::getServiceContainer()->get('DateFactory');
		}

		/** @inheritdoc */
		public static function IdnConverter() {
			return self::getServiceContainer()->get('IdnConverter');
		}

		/** @inheritdoc */
		public static function DomainCollection() {
			return self::getServiceContainer()->get('DomainCollection');
		}

		/** @inheritdoc */
		public static function DomainDetector() {
			return self::getServiceContainer()->get('DomainDetector');
		}

		/** @inheritdoc */
		public static function CaptchaSettingsFactory() {
			return self::getServiceContainer()->get('CaptchaSettingsFactory');
		}

		/** @inheritdoc */
		public static function WatermarkSettingsFactory() {
			return self::getServiceContainer()->get('WatermarkSettingsFactory');
		}

		/** @inheritdoc */
		public static function StubSettingsFactory() {
			return self::getServiceContainer()->get('StubSettingsFactory');
		}

		/** @inheritdoc */
		public static function SeoSettingsFactory() {
			return self::getServiceContainer()->get('SeoSettingsFactory');
		}

		/** @inheritdoc */
		public static function MailSettingsFactory() {
			return self::getServiceContainer()->get('MailSettingsFactory');
		}

		/** @inheritdoc */
		public static function LanguageCollection() {
			return self::getServiceContainer()->get('LanguageCollection');
		}

		/** @inheritdoc */
		public static function LanguageDetector() {
			return self::getServiceContainer()->get('LanguageDetector');
		}

		/** @inheritdoc */
		public static function YandexOAuthClient() {
			return self::getServiceContainer()->get('YandexOAuthClient');
		}

		/** @inheritdoc */
		public static function ObjectPropertyValueTableSchema() {
			return self::getServiceContainer()->get('ObjectPropertyValueTableSchema');
		}

		/** @inheritdoc */
		public static function SolutionRegistry() {
			return self::getServiceContainer()->get('SolutionRegistry');
		}

		/** @inheritdoc */
		public static function UmiDumpSolutionPostfixBuilder() {
			return self::getServiceContainer()->get('UmiDumpSolutionPostfixBuilder');
		}

		/** @inheritdoc */
		public static function UmiDumpFilePathConverter() {
			return self::getServiceContainer()->get('UmiDumpFilePathConverter');
		}

		/** @inheritdoc */
		public static function UmiDumpSolutionPostfixFilter() {
			return self::getServiceContainer()->get('UmiDumpSolutionPostfixFilter');
		}

		/** @inheritdoc */
		public static function Protection() {
			return self::getServiceContainer()->get('Protection');
		}

		/** @inheritdoc */
		public static function SystemInfo() {
			return self::getServiceContainer()->get('SystemInfo');
		}

		/** @inheritdoc */
		public static function TradeOfferFacade() {
			return self::getServiceContainer()->get('TradeOfferFacade');
		}

		/** @inheritdoc */
		public static function TradeOfferPriceFacade() {
			return self::getServiceContainer()->get('TradeOfferPriceFacade');
		}

		/** @inheritdoc */
		public static function TradeOfferPriceTypeFacade() {
			return self::getServiceContainer()->get('TradeOfferPriceTypeFacade');
		}

		/** @inheritdoc */
		public static function TradeStockFacade() {
			return self::getServiceContainer()->get('TradeStockFacade');
		}

		/** @inheritdoc */
		public static function TradeStockBalanceFacade() {
			return self::getServiceContainer()->get('TradeStockBalanceFacade');
		}

		/** @inheritdoc */
		public static function Hierarchy() {
			return self::getServiceContainer()->get('pages');
		}

		/** @inheritdoc */
		public static function FieldsFacade() {
			return self::getServiceContainer()->get('fields');
		}

		/** @inheritdoc */
		public static function TradeOfferCharacteristicFacade() {
			return self::getServiceContainer()->get('TradeOfferCharacteristicFacade');
		}

		/** @inheritdoc */
		public static function CurrencyFacade() {
			return self::getServiceContainer()->get('Currencies');
		}

		/** @inheritdoc */
		public static function UmiDumpEntityBaseImporterFactory() {
			return self::getServiceContainer()->get('UmiDumpEntityBaseImporterFactory');
		}

		/** @inheritdoc */
		public static function EmojiTranslator() {
			return self::getServiceContainer()->get('EmojiTranslator');
		}

		/** @inheritdoc */
		public static function PageChildrenIdGetter() {
			return self::getServiceContainer()->get('HierarchyElementChildrenIdGetter');
		}

		/**
		 * Возвращает контейнер сервисов
		 * @return \iServiceContainer
		 */
		private static function getServiceContainer() {
			return \ServiceContainerFactory::create();
		}
	}
