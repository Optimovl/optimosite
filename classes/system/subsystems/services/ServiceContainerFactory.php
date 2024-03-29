<?php

	/**
	 * Фабрика контейнеров сервисов
	 * Через него следует получать желаемый контейнер,
	 * @example $ServiceContainer = ServiceContainerFactory::create();
	 */
	class ServiceContainerFactory implements iServiceContainerFactory {

		/** @var ServiceContainer[] $serviceContainerList список контейнеров сервисов */
		private static $serviceContainerList = [];

		/** @inheritdoc */
		public static function create($type = self::DEFAULT_CONTAINER_TYPE, array $rules = [], array $parameters = []) {
			if (isset(self::$serviceContainerList[$type])) {
				return self::$serviceContainerList[$type];
			}

			$defaultRules = self::getDefaultRules();
			$defaultParameters = self::getDefaultParameters();

			if ($type !== self::DEFAULT_CONTAINER_TYPE) {
				$rules = array_merge($defaultRules, $rules);
				$parameters = array_merge($defaultParameters, $parameters);
			} else {
				$rules = $defaultRules;
				$parameters = $defaultParameters;
			}

			return self::$serviceContainerList[$type] = new ServiceContainer($rules, $parameters);
		}

		/**
		 * Возвращает список параметров по умолчанию для контейнера сервисов
		 * @return array
		 * @throws Exception
		 * @throws coreException
		 */
		protected static function getDefaultParameters() {
			return [
				'connection' => ConnectionPool::getInstance()->getConnection(),
				'imageFileHandler' => new umiImageFile(__FILE__),
				'baseUmiCollectionConstantMap' => new baseUmiCollectionConstantMap(),
				'directoriesHandler' => new umiDirectory(__FILE__),
				'umiRedirectsCollection' => 'Redirects',
				'MailTemplatesCollection' => 'MailTemplates',
				'MailNotificationsCollection' => 'MailNotifications',
				'MailVariablesCollection' => 'MailVariables'
			];
		}

		/**
		 * Возвращает список правил инстанцирования сервисов по умолчанию для контейнера сервисов
		 * @return array
		 */
		protected static function getDefaultRules() {
			return [
				'Redirects' => [
					'class' => 'umiRedirectsCollection',
					'arguments' => [
						new ParameterReference('umiRedirectsCollection'),
					],
					'calls' => [
						[
							'method' => 'setConnection',
							'arguments' => [
								new ParameterReference('connection')
							]
						],
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						],
						[
							'method' => 'setMap',
							'arguments' => [
								new InstantiableReference('umiRedirectsConstantMap')
							]
						],
						[
							'method' => 'setResponse',
							'arguments' => [
								new \ServiceReference('Response')
							]
						],
						[
							'method' => 'setDomainDetector',
							'arguments' => [
								new \ServiceReference('DomainDetector')
							]
						],
						[
							'method' => 'setDomainCollection',
							'arguments' => [
								new \ServiceReference('DomainCollection')
							]
						],
						[
							'method' => 'setLanguageCollection',
							'arguments' => [
								new \ServiceReference('LanguageCollection')
							]
						]
					]
				],

				'UrlFactory' => [
					'class' => 'UmiCms\System\Utils\Url\Factory',
				],

				'MailVariables' => [
					'class' => 'MailVariablesCollection',
					'arguments' => [
						new ParameterReference('MailVariablesCollection'),
					],
					'calls' => [
						[
							'method' => 'setConnection',
							'arguments' => [
								new ParameterReference('connection')
							]
						],
						[
							'method' => 'setMap',
							'arguments' => [
								new InstantiableReference('mailVariablesConstantMap')
							]
						],
						[
							'method' => 'setSourceIdBinderFactory',
							'arguments' => [
								new ServiceReference('ImportEntitySourceIdBinderFactory')
							]
						]
					]
				],

				'MailTemplates' => [
					'class' => 'MailTemplatesCollection',
					'arguments' => [
						new ParameterReference('MailTemplatesCollection'),
					],
					'calls' => [
						[
							'method' => 'setConnection',
							'arguments' => [
								new ParameterReference('connection')
							]
						],
						[
							'method' => 'setMap',
							'arguments' => [
								new InstantiableReference('mailTemplatesConstantMap')
							]
						],
						[
							'method' => 'setSourceIdBinderFactory',
							'arguments' => [
								new ServiceReference('ImportEntitySourceIdBinderFactory')
							]
						]
					]
				],

				'MailNotifications' => [
					'class' => 'MailNotificationsCollection',
					'arguments' => [
						new ParameterReference('MailNotificationsCollection'),
					],
					'calls' => [
						[
							'method' => 'setConnection',
							'arguments' => [
								new ParameterReference('connection')
							]
						],
						[
							'method' => 'setMap',
							'arguments' => [
								new InstantiableReference('mailNotificationsConstantMap')
							]
						],
						[
							'method' => 'setDomainCollection',
							'arguments' => [
								new \ServiceReference('DomainCollection')
							]
						],
						[
							'method' => 'setLanguageCollection',
							'arguments' => [
								new \ServiceReference('LanguageCollection')
							]
						],
						[
							'method' => 'setLanguageDetector',
							'arguments' => [
								new \ServiceReference('LanguageDetector')
							]
						],
						[
							'method' => 'setDomainDetector',
							'arguments' => [
								new \ServiceReference('DomainDetector')
							]
						],
					]
				],

				'AuthenticationRulesFactory' => [
					'class' => 'UmiCms\System\Auth\AuthenticationRules\Factory',
					'arguments' => [
						new ServiceReference('PasswordHashAlgorithm'),
						new ServiceReference('SelectorFactory'),
						new ServiceReference('HashComparator')
					]
				],

				'PasswordHashAlgorithm' => [
					'class' => 'UmiCms\System\Auth\PasswordHash\Algorithm'
				],

				'Authentication' => [
					'class' => 'UmiCms\System\Auth\Authentication',
					'arguments' => [
						new ServiceReference('AuthenticationRulesFactory'),
						new ServiceReference('Session')
					]
				],

				'Authorization' => [
					'class' => 'UmiCms\System\Auth\Authorization',
					'arguments' => [
						new ServiceReference('Session'),
						new ServiceReference('CsrfProtection'),
						new ServiceReference('permissionsCollection'),
						new ServiceReference('CookieJar'),
						new ServiceReference('objects'),
						new ServiceReference('Configuration')
					]
				],

				'SystemUsersPermissions' => [
					'class' => 'UmiCms\System\Permissions\SystemUsersPermissions',
					'arguments' => [
						new ServiceReference('objects')
					]
				],

				'Auth' => [
					'class' => 'UmiCms\System\Auth\Auth',
					'arguments' => [
						new ServiceReference('Authentication'),
						new ServiceReference('Authorization'),
						new ServiceReference('SystemUsersPermissions')
					]
				],

				'CsrfProtection' => [
					'class' => '\UmiCms\System\Protection\CsrfProtection',
					'arguments' => [
						new ServiceReference('Session'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('IdnConverter'),
						new ServiceReference('DomainCollection'),
						new ServiceReference('EventPointFactory'),
						new ServiceReference('HashComparator')
					],
				],

				'Request' => [
					'class' => '\UmiCms\System\Request\Facade',
					'arguments' => [
						new ServiceReference('RequestHttp'),
						new ServiceReference('BrowserDetector'),
						new ServiceReference('RequestModeDetector'),
						new ServiceReference('RequestPathResolver')
					]
				],

				'CookieJar' => [
					'class' => 'UmiCms\System\Cookies\CookieJar',
					'arguments' => [
						new ServiceReference('CookiesFactory'),
						new ServiceReference('CookiesResponsePool'),
						new ServiceReference('RequestHttpCookies'),
						new ServiceReference('Encrypter')
					]
				],

				'Session' => [
					'class' => 'UmiCms\System\Session\Session',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('CookieJar')
					]
				],

				'templates' => [
					'class' => 'templatesCollection',
				],

				'pages' => [
					'class' => 'umiHierarchy',
				],

				'cmsController' => [
					'class' => 'cmsController',
				],

				'objects' => [
					'class' => 'umiObjectsCollection',
				],

				'permissionsCollection' => [
					'class' => 'permissionsCollection',
				],

				'connectionPool' => [
					'class' => 'ConnectionPool',
				],

				'objectTypes' => [
					'class' => 'umiObjectTypesCollection'
				],

				'hierarchyTypes' => [
					'class' => 'umiHierarchyTypesCollection'
				],

				'typesHelper' => [
					'class' => 'umiTypesHelper'
				],

				'fields' => [
					'class' => 'umiFieldsCollection'
				],

				'objectPropertyFactory' => [
					'class' => 'UmiCms\System\Data\Object\Property\Factory',
					'arguments' => [
						new ServiceReference('fields'),
						new ServiceReference('objects')
					],
				],

				'ActionFactory' => [
					'class' => 'ActionFactory',
					'calls' => [
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						]
					]
				],

				'BaseXmlConfigFactory' => [
					'class' => 'BaseXmlConfigFactory'
				],

				'AtomicOperationCallbackFactory' => [
					'class' => 'AtomicOperationCallbackFactory'
				],

				'TransactionFactory' => [
					'class' => 'TransactionFactory',
					'calls' => [
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						]
					]
				],

				'ManifestSourceFactory' => [
					'class' => 'ManifestSourceFactory'
				],

				'ManifestFactory' => [
					'class' => 'ManifestFactory',
					'arguments' => [
						new ServiceReference('BaseXmlConfigFactory'),
						new ServiceReference('AtomicOperationCallbackFactory'),
						new ServiceReference('ManifestSourceFactory')
					],
					'calls' => [
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						]
					]
				],

				'EventPointFactory' => [
					'class' => '\UmiCms\System\Events\EventPointFactory'
				],

				'SiteMapUpdater' => [
					'class' => '\UmiCms\Utils\SiteMap\Updater',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('pages'),
						new ServiceReference('EventPointFactory')
					]
				],

				'CacheKeyGenerator' => [
					'class' => '\UmiCms\System\Cache\Key\Generator',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'CacheEngineFactory' => [
					'class' => '\UmiCms\System\Cache\EngineFactory'
				],

				'CountriesFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\Country\CountriesFactory'
				],

				'CitiesFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\City\CitiesFactory'
				],

				'DirectoryFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\Directory\Factory'
				],

				'FileFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\File\Factory'
				],

				'ImageFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\Image\Factory'
				],

				'UmiDumpDirectoryDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Directory',
					'arguments' => [
						new ServiceReference('DirectoryFactory')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						],
						[
							'method' => 'setFilePathConverter',
							'arguments' => [
								new ServiceReference('UmiDumpFilePathConverter')
							]
						]
					]
				],

				'UmiDumpFileDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\File',
					'arguments' => [
						new ServiceReference('FileFactory'),
						new ServiceReference('DirectoryFactory')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						],
						[
							'method' => 'setFilePathConverter',
							'arguments' => [
								new ServiceReference('UmiDumpFilePathConverter')
							]
						]
					]
				],

				'Registry' => [
					'class' => 'regedit',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('CacheEngineFactory')
					]
				],

				'UmiDumpRegistryDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Registry',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],

				'ImportSourceIdBinder' => [
					'class' => 'umiImportRelations'
				],

				'EntitySourceIdBinder' => [
					'class' => 'entityImportRelations',
					'arguments' => [
						new ServiceReference('ImportSourceIdBinder'),
						new ParameterReference('connection')
					]
				],

				'UmiDumpDomainDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Domain',
					'arguments' => [
						new ServiceReference('DomainCollection')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpLanguageDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Language',
					'arguments' => [
						new ServiceReference('LanguageCollection')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpObjectDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Objects',
					'arguments' => [
						new ServiceReference('objects')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpTemplateDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Template',
					'arguments' => [
						new ServiceReference('templates')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpObjectTypeDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\ObjectType',
					'arguments' => [
						new ServiceReference('objectTypes')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpPageDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Page',
					'arguments' => [
						new ServiceReference('pages')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'RestrictionCollection' => [
					'class' => '\UmiCms\System\Data\Field\Restriction\Collection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],

				'UmiDumpRestrictionDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Restriction',
					'arguments' => [
						new ServiceReference('RestrictionCollection'),
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpFieldGroupDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\FieldGroup',
					'arguments' => [
						new ServiceReference('objectTypes')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpFieldDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Field',
					'arguments' => [
						new ServiceReference('fields'),
						new ServiceReference('objectTypes')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpPermissionDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Permission',
					'arguments' => [
						new ServiceReference('permissionsCollection'),
						new ServiceReference('SystemUsersPermissions'),
						new ServiceReference('objects'),
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'ImportEntitySourceIdBinderFactory' => [
					'class' => '\UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory',
					'arguments' => [
						new ServiceReference('EntitySourceIdBinder')
					],
				],

				'UmiDumpEntityDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Entity',
					'arguments' => [
						new ServiceReference('ImportEntitySourceIdBinderFactory'),
						new ServiceContainerReference(),
						new ServiceReference('cmsController'),
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpDemolisherTypeFactory' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Factory',
					'arguments' => [
						new ServiceContainerReference()
					]
				],

				'UmiDumpDemolisherExecutor' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Executor',
					'arguments' => [
						new ServiceReference('UmiDumpDemolisherTypeFactory')
					]
				],

				'RegistryPart' => [
					'class' => '\UmiCms\System\Registry\Part',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],

				'ExtensionRegistry' => [
					'class' => '\UmiCms\System\Extension\Registry',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],

				'ExtensionLoader' => [
					'class' => '\UmiCms\System\Extension\Loader',
					'arguments' => [
						new ServiceReference('DirectoryFactory'),
						new ServiceReference('FileFactory')
					]
				],

				'ModulePermissionLoader' => [
					'class' => '\UmiCms\System\Module\Permissions\Loader',
					'arguments' => [
						new ServiceReference('cmsController'),
						new ServiceReference('DirectoryFactory'),
						new ServiceReference('FileFactory')
					]
				],

				'CacheFrontend' => [
					'class' => 'cacheFrontend',
					'arguments' => [
						new ServiceReference('CacheEngineFactory'),
						new ServiceReference('CacheKeyGenerator'),
						new ServiceReference('Configuration'),
						new ServiceReference('CacheKeyValidatorFactory'),
						new ServiceReference('RequestModeDetector')
					]
				],

				'CacheKeyValidatorFactory' => [
					'class' => '\UmiCms\System\Cache\Key\Validator\Factory',
					'arguments' => [
						new ServiceReference('Configuration')
					]
				],

				'BrowserDetector' => [
					'class' => 'BrowserDetect',
					'arguments' => [
						new ServiceReference('CacheEngineFactory')
					]
				],

				'StaticCacheStorage' => [
					'class' => '\UmiCms\System\Cache\Statical\Storage',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('FileFactory'),
						new ServiceReference('DirectoryFactory')
					]
				],

				'StaticCacheKeyGenerator' => [
					'class' => '\UmiCms\System\Cache\Statical\Key\Generator',
					'arguments' => [
						new ServiceReference('Request'),
						new ServiceReference('pages'),
						new ServiceReference('Configuration'),
						new ServiceReference('DomainCollection')
					]
				],

				'CacheStateValidator' => [
					'class' => 'UmiCms\System\Cache\State\Validator',
					'arguments' => [
						new ServiceReference('Auth'),
						new ServiceReference('Request'),
						new ServiceReference('cmsController'),
						new ServiceReference('Response'),
					]
				],

				'StaticCacheKeyValidatorFactory' => [
					'class' => '\UmiCms\System\Cache\Statical\Key\Validator\Factory',
					'arguments' => [
						new ServiceReference('Configuration')
					]
				],

				'StaticCache' => [
					'class' => 'UmiCms\System\Cache\Statical\Facade',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('CacheStateValidator'),
						new ServiceReference('StaticCacheKeyValidatorFactory'),
						new ServiceReference('StaticCacheKeyGenerator'),
						new ServiceReference('StaticCacheStorage')
					]
				],

				'ResponseBufferDetector' => [
					'class' => 'UmiCms\System\Response\Buffer\Detector',
					'arguments' => [
						new ServiceReference('RequestModeDetector')
					]
				],

				'ResponseBufferFactory' => [
					'class' => 'UmiCms\System\Response\Buffer\Factory'
				],

				'ResponseBufferCollection' => [
					'class' => 'UmiCms\System\Response\Buffer\Collection'
				],

				'Response' => [
					'class' => 'UmiCms\System\Response\Facade',
					'arguments' => [
						new ServiceReference('ResponseBufferFactory'),
						new ServiceReference('ResponseBufferDetector'),
						new ServiceReference('ResponseBufferCollection'),
						new ServiceReference('ResponseUpdateTimeCalculator'),
					]
				],

				'ResponseUpdateTimeCalculator' => [
					'class' => 'UmiCms\System\Response\UpdateTime\Calculator',
					'arguments' => [
						new ServiceReference('pages'),
						new ServiceReference('objects')
					]
				],

				'Configuration' => [
					'class' => 'mainConfiguration',
				],

				'BrowserCacheEngineFactory' => [
					'class' => 'UmiCms\System\Cache\Browser\Engine\Factory',
					'arguments' => [
						new ServiceContainerReference()
					]
				],

				'BrowserCache' => [
					'class' => 'UmiCms\System\Cache\Browser\Facade',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('BrowserCacheEngineFactory'),
						new ServiceReference('CacheStateValidator')
					]
				],

				'LoggerFactory' => [
					'class' => 'UmiCms\Utils\Logger\Factory',
					'arguments' => [
						new ServiceReference('DirectoryFactory')
					]
				],

				'SelectorFactory' => [
					'class' => 'UmiCms\System\Selector\Factory'
				],

				'QuickExchangeSourceDetector' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Source\Detector',
					'arguments' => [
						new ServiceReference('cmsController')
					]
				],

				'QuickExchangeFileDownloader' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\File\Downloader',
					'arguments' => [
						new ServiceReference('QuickExchangeSourceDetector'),
						new ServiceReference('FileFactory'),
						new ServiceReference('Response'),
						new ServiceReference('Configuration')
					]
				],

				'QuickExchangeFileUploader' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\File\Uploader',
					'arguments' => [
						new ServiceReference('Request'),
						new ServiceReference('Configuration')
					]
				],

				'QuickExchangeCsvExporter' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Csv\Exporter',
					'arguments' => [
						new ServiceReference('QuickExchangeSourceDetector'),
						new ServiceReference('Request')
					]
				],

				'QuickExchangeCsvImporter' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Csv\Importer',
					'arguments' => [
						new ServiceReference('QuickExchangeSourceDetector'),
						new ServiceReference('Request'),
						new ServiceReference('FileFactory'),
						new ServiceReference('Configuration'),
						new ServiceReference('Session')
					]
				],

				'QuickExchange' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Facade',
					'arguments' => [
						new ServiceReference('QuickExchangeCsvExporter'),
						new ServiceReference('QuickExchangeCsvImporter'),
						new ServiceReference('QuickExchangeFileDownloader'),
						new ServiceReference('QuickExchangeFileUploader'),
						new ServiceReference('Configuration'),
						new ServiceReference('Response')
					]
				],

				'DataObjectFactory' => [
					'class' => 'UmiCms\System\Data\Object\Factory'
				],

				'HierarchyElementFactory' => [
					'class' => 'UmiCms\System\Hierarchy\Element\Factory'
				],

				'CookiesFactory' => [
					'class' => 'UmiCms\System\Cookies\Factory'
				],

				'CookiesResponsePool' => [
					'class' => 'UmiCms\System\Cookies\ResponsePool'
				],

				'RequestHttpCookies' => [
					'class' => 'UmiCms\System\Request\Http\Cookies'
				],

				'RequestHttpFiles' => [
					'class' => 'UmiCms\System\Request\Http\Files'
				],

				'RequestHttpGet' => [
					'class' => 'UmiCms\System\Request\Http\Get'
				],

				'RequestHttpPost' => [
					'class' => 'UmiCms\System\Request\Http\Post'
				],

				'RequestHttpServer' => [
					'class' => 'UmiCms\System\Request\Http\Server'
				],

				'RequestHttp' => [
					'class' => 'UmiCms\System\Request\Http\Request',
					'arguments' => [
						new ServiceReference('RequestHttpCookies'),
						new ServiceReference('RequestHttpServer'),
						new ServiceReference('RequestHttpPost'),
						new ServiceReference('RequestHttpGet'),
						new ServiceReference('RequestHttpFiles')
					]
				],

				'RequestModeDetector' => [
					'class' => 'UmiCms\System\Request\Mode\Detector',
					'arguments' => [
						new ServiceReference('RequestPathResolver')
					]
				],

				'RequestPathResolver' => [
					'class' => 'UmiCms\System\Request\Path\Resolver',
					'arguments' => [
						new ServiceReference('RequestHttpGet'),
						new ServiceReference('Configuration')
					]
				],

				'RegistrySettings' => [
					'class' => 'UmiCms\System\Registry\Settings',
					'arguments' => [
						new ServiceReference('Registry'),
					]
				],

				'DateFactory' => [
					'class' => 'UmiCms\Classes\System\Entities\Date\Factory'
				],

				'IdnConverter' => [
					'class' => 'idna_convert'
				],

				'DomainCollection' => [
					'class' => 'domainsCollection',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('IdnConverter')
					]
				],

				'DomainDetector' => [
					'class' => 'UmiCms\System\Hierarchy\Domain\Detector',
					'arguments' => [
						new ServiceReference('DomainCollection'),
						new ServiceReference('RequestHttp')
					]
				],

				'CaptchaSettingsFactory' => [
					'class' => 'UmiCms\Classes\System\Utils\Captcha\Settings\Factory',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'WatermarkSettingsFactory' => [
					'class' => 'UmiCms\Classes\System\Utils\Watermark\Settings\Factory',
					'arguments' => [
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'StubSettingsFactory' => [
					'class' => 'UmiCms\Classes\System\Utils\Stub\Settings\Factory',
					'arguments' => [
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'SeoSettingsFactory' => [
					'class' => 'UmiCms\Classes\System\Utils\Seo\Settings\Factory',
					'arguments' => [
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'MailSettings' => [
					'class' => 'UmiCms\Classes\System\Utils\Mail\Settings',
					'arguments' => [
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'MailSettingsFactory' => [
					'class' => 'UmiCms\Classes\System\Utils\Mail\Settings\Factory',
					'arguments' => [
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'SmtpMailSettingsFactory' => [
					'class' => 'UmiCms\Classes\System\Utils\Mail\Settings\Smtp\Factory',
					'arguments' => [
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'LanguageCollection' => [
					'class' => 'langsCollection',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('DomainCollection')
					]
				],

				'LanguageDetector' => [
					'class' => 'UmiCms\System\Hierarchy\Language\Detector',
					'arguments' => [
						new ServiceReference('LanguageCollection'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('Request'),
						new ServiceReference('pages')
					]
				],

				'YandexOAuthClient' => [
					'class' => 'UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Client\OAuth'
				],

				'ObjectTypeHierarchyRelationFactory' => [
					'class' => 'UmiCms\System\Data\Object\Type\Hierarchy\Relation\Factory'
				],

				'ObjectTypeHierarchyRelationRepository' => [
					'class' => 'UmiCms\System\Data\Object\Type\Hierarchy\Relation\Repository',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('ObjectTypeHierarchyRelationFactory')
					]
				],

				'ObjectTypeHierarchyRelationMigration' => [
					'class' => 'UmiCms\System\Data\Object\Type\Hierarchy\Relation\Migration',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('ObjectTypeHierarchyRelationRepository')
					]
				],

				'FieldTypeCollection' => [
					'class' => 'umiFieldTypesCollection'
				],

				'FieldTypeMigration' => [
					'class' => 'UmiCms\System\Data\Field\Type\Migration',
					'arguments' => [
						new ServiceReference('objectTypes'),
						new ServiceReference('fields'),
						new ServiceReference('FieldTypeCollection')
					]
				],

				'ObjectPropertyValueTableSchema' => [
					'class' => 'UmiCms\System\Data\Object\Property\Value\Table\Schema'
				],

				'ObjectPropertyRepository' => [
					'class' => 'UmiCms\System\Data\Object\Property\Repository',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('objectPropertyFactory'),
						new ServiceReference('fields'),
						new ServiceReference('ObjectPropertyValueTableSchema')
					],
				],

				'ObjectPropertyValueDomainIdMigration' => [
					'class' => 'UmiCms\System\Data\Object\Property\Value\DomainId\Migration',
					'arguments' => [
						new ServiceReference('ObjectPropertyValueTableSchema'),
						new ParameterReference('connection')
					]
				],

				'ObjectPropertyValueImgFileMigration' => [
					'class' => 'UmiCms\System\Data\Object\Property\Value\ImgFile\Migration',
					'arguments' => [
						new ServiceReference('ObjectPropertyValueTableSchema'),
						new ParameterReference('connection')
					]
				],

				'SolutionRegistry' => [
					'class' => '\UmiCms\System\Solution\Registry',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],

				'UmiDumpSolutionPostfixBuilder' => [
					'class' => '\UmiCms\System\Import\UmiDump\Helper\Solution\Postfix\Builder',
				],

				'UmiDumpFilePathConverter' => [
					'class' => '\UmiCms\System\Import\UmiDump\Helper\File\Path\Converter',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('UmiDumpSolutionPostfixFilter')
					]
				],

				'UmiDumpSolutionPostfixFilter' => [
					'class' => '\UmiCms\System\Import\UmiDump\Helper\Solution\Postfix\Filter',
				],

				'Protection' => [
					'class' => '\UmiCms\System\Protection\Security',
					'arguments' => [
						new ServiceReference('Request'),
						new ServiceReference('Configuration'),
						new ServiceReference('CsrfProtection'),
						new ServiceReference('HashComparator')
					]
				],

				'Encrypter' => [
					'class' => '\UmiCms\System\Protection\Encrypter',
					'arguments' => [
						new ServiceReference('Configuration')
					]
				],

				'HashComparator' => [
					'class' => '\UmiCms\System\Protection\HashComparator',
				],

				'SystemInfo' => [
					'class' => 'systemInfo',
					'arguments' => [
						new ServiceReference('Registry'),
						new ParameterReference('connection'),
						new ServiceReference('DomainCollection'),
						new ServiceReference('Request')
					]
				],

				'TradeOfferFactory' => [
					'class' => '\UmiCms\System\Trade\Offer\Factory',
					'arguments' => [
						new ServiceReference('TradeOffer')
					]
				],

				'TradeOfferVendorCodeGenerator' => [
					'class' => '\UmiCms\System\Trade\Offer\Vendor\Code\Generator'
				],

				'TradeOfferMapper' => [
					'class' => '\UmiCms\System\Trade\Offer\Mapper'
				],

				'TradeOfferRepository' => [
					'class' => '\UmiCms\System\Trade\Offer\Repository',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('OrmEntityRepositoryHistory'),
						new ServiceReference('TradeOfferSchema'),
						new ServiceReference('TradeOfferAttributeAccessor'),
						new ServiceReference('TradeOfferFactory'),
						new ServiceReference('TradeOfferBuilder')
					]
				],

				'TradeOfferCollection' => [
					'class' => '\UmiCms\System\Trade\Offer\Collection',
					'arguments' => [
						new ServiceReference('TradeOfferAttributeAccessor')
					]
				],

				'TradeOfferFacade' => [
					'class' => '\UmiCms\System\Trade\Offer\Facade',
					'arguments' => [
						new ServiceReference('TradeOfferCollection'),
						new ServiceReference('TradeOfferRepository'),
						new ServiceReference('TradeOfferFactory'),
						new ServiceReference('TradeOfferAttributeAccessor'),
						new ServiceReference('TradeOfferRelationAccessor'),
						new ServiceReference('TradeOfferBuilder')
					],
					'calls' => [
						[
							'method' => 'setDataObjectFacade',
							'arguments' => [
								new ServiceReference('TradeOfferDataObjectFacade')
							]
						],
						[
							'method' => 'setOfferPriceFacade',
							'arguments' => [
								new ServiceReference('TradeOfferPriceFacade')
							]
						],
						[
							'method' => 'setVendorCoderGenerator',
							'arguments' => [
								new ServiceReference('TradeOfferVendorCodeGenerator')
							]
						],
						[
							'method' => 'setTypeFacade',
							'arguments' => [
								new ServiceReference('TradeOfferDataObjectTypeFacade')
							]
						],
						[
							'method' => 'setStockBalanceFacade',
							'arguments' => [
								new ServiceReference('TradeStockBalanceFacade')
							]
						]
					]
				],

				'TradeOfferDataObjectTypeFacade' => [
					'class' => '\UmiCms\System\Trade\Offer\Data\Object\Type\Facade',
					'arguments' => [
						new ServiceReference('objectTypes'),
					]
				],

				'TradeOfferDataObjectFacade' => [
					'class' => '\UmiCms\System\Trade\Offer\Data\Object\Facade',
					'arguments' => [
						new ServiceReference('objects'),
						new ServiceReference('TradeOfferDataObjectTypeFacade')
					]
				],

				'TradeStockBalanceCollection' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Collection',
					'arguments' => [
						new ServiceReference('TradeStockBalanceAttributeAccessor')
					]
				],

				'TradeStockBalanceFactory' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Factory',
					'arguments' => [
						new ServiceReference('TradeStockBalance')
					]
				],

				'TradeStockBalanceRepository' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Repository',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('OrmEntityRepositoryHistory'),
						new ServiceReference('TradeStockBalanceSchema'),
						new ServiceReference('TradeStockBalanceAttributeAccessor'),
						new ServiceReference('TradeStockBalanceFactory'),
						new ServiceReference('TradeStockBalanceBuilder')
					],
				],

				'TradeStockBalanceFacade' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Facade',
					'arguments' => [
						new ServiceReference('TradeStockBalanceCollection'),
						new ServiceReference('TradeStockBalanceRepository'),
						new ServiceReference('TradeStockBalanceFactory'),
						new ServiceReference('TradeStockBalanceAttributeAccessor'),
						new ServiceReference('TradeStockBalanceRelationAccessor'),
						new ServiceReference('TradeStockBalanceBuilder'),
					],
				],

				'TradeStockBalanceMapper' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Mapper'
				],

				'OrmEntityRepositoryHistory' => [
					'class' => '\UmiCms\System\Orm\Entity\Repository\History'
				],

				'TradeStockFactory' => [
					'class' => '\UmiCms\System\Trade\Stock\Factory'
				],

				'TradeStockFacade' => [
					'class' => '\UmiCms\System\Trade\Stock\Facade',
					'arguments' => [
						new ServiceReference('TradeStockFactory'),
						new ServiceReference('objects'),
						new ServiceReference('objectTypes'),
					]
				],

				'TradeOfferPriceFactory' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Factory',
					'arguments' => [
						new ServiceReference('TradeOfferPrice')
					]
				],

				'TradeOfferPriceMapper' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Mapper'
				],

				'TradeOfferPriceCollection' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Collection',
					'arguments' => [
						new ServiceReference('TradeOfferPriceAttributeAccessor')
					]
				],

				'TradeOfferPriceRepository' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Repository',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('OrmEntityRepositoryHistory'),
						new ServiceReference('TradeOfferPriceSchema'),
						new ServiceReference('TradeOfferPriceAttributeAccessor'),
						new ServiceReference('TradeOfferPriceFactory'),
						new ServiceReference('TradeOfferPriceBuilder')
					],
				],

				'TradeOfferPriceFacade' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Facade',
					'arguments' => [
						new ServiceReference('TradeOfferPriceCollection'),
						new ServiceReference('TradeOfferPriceRepository'),
						new ServiceReference('TradeOfferPriceFactory'),
						new ServiceReference('TradeOfferPriceAttributeAccessor'),
						new ServiceReference('TradeOfferPriceRelationAccessor'),
						new ServiceReference('TradeOfferPriceBuilder')
					],
					'calls' => [
						[
							'method' => 'setCurrencyFacade',
							'arguments' => [
								new ServiceReference('Currencies')
							]
						],
						[
							'method' => 'setTypeFacade',
							'arguments' => [
								new ServiceReference('TradeOfferPriceTypeFacade')
							],
						]
					]
				],

				'CurrencyCollection' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Currency\Collection'
				],

				'CurrencyFactory' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Currency\Factory'
				],

				'CurrencyRepository' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Currency\Repository',
					'arguments' => [
						new ServiceReference('CurrencyFactory'),
						new ServiceReference('SelectorFactory'),
					]
				],

				'Currencies' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Currency\Facade',
					'arguments' => [
						new ServiceReference('CurrencyRepository'),
						new ServiceReference('CurrencyCollection'),
						new ServiceReference('Configuration'),
						new ServiceReference('CurrencyCalculator'),
						new ServiceReference('FavoriteCurrencyFacade')
					]
				],

				'CurrencyCalculator' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Currency\Calculator'
				],

				'TradeOfferPriceTypeCollection' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Collection',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeAttributeAccessor')
					]
				],

				'TradeOfferPriceTypeMapper' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Mapper'
				],

				'TradeOfferPriceTypeFactory' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Factory',
					'arguments' => [
						new ServiceReference('TradeOfferPriceType')
					]
				],

				'TradeOfferPriceTypeRepository' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Repository',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('OrmEntityRepositoryHistory'),
						new ServiceReference('TradeOfferPriceTypeSchema'),
						new ServiceReference('TradeOfferPriceTypeAttributeAccessor'),
						new ServiceReference('TradeOfferPriceTypeFactory'),
						new ServiceReference('TradeOfferPriceTypeBuilder')
					],
				],

				'TradeOfferPriceTypeFacade' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Facade',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeCollection'),
						new ServiceReference('TradeOfferPriceTypeRepository'),
						new ServiceReference('TradeOfferPriceTypeFactory'),
						new ServiceReference('TradeOfferPriceTypeAttributeAccessor'),
						new ServiceReference('TradeOfferPriceTypeRelationAccessor'),
						new ServiceReference('TradeOfferPriceTypeBuilder')
					]
				],

				'TradeOfferPriceTypeExporter' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Exporter',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeFacade'),
						new ServiceReference('TradeOfferPriceTypeSchema'),
					]
				],

				'TradeOfferPriceTypeImporter' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Importer',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeFacade'),
						new ServiceReference('TradeOfferPriceTypeSchema'),
					]
				],

				'TradeOfferPriceExporter' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Exporter',
					'arguments' => [
						new ServiceReference('TradeOfferPriceFacade'),
						new ServiceReference('TradeOfferPriceSchema'),
					]
				],

				'TradeOfferPriceImporter' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Importer',
					'arguments' => [
						new ServiceReference('TradeOfferPriceFacade'),
						new ServiceReference('TradeOfferPriceSchema'),
					]
				],

				'TradeStockBalanceExporter' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Exporter',
					'arguments' => [
						new ServiceReference('TradeStockBalanceFacade'),
						new ServiceReference('TradeStockBalanceSchema'),
					]
				],

				'TradeStockBalanceImporter' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Importer',
					'arguments' => [
						new ServiceReference('TradeStockBalanceFacade'),
						new ServiceReference('TradeStockBalanceSchema'),
					]
				],

				'TradeOfferExporter' => [
					'class' => '\UmiCms\System\Trade\Offer\Exporter',
					'arguments' => [
						new ServiceReference('TradeOfferFacade'),
						new ServiceReference('TradeOfferSchema'),
					]
				],

				'TradeOfferImporter' => [
					'class' => '\UmiCms\System\Trade\Offer\Importer',
					'arguments' => [
						new ServiceReference('TradeOfferFacade'),
						new ServiceReference('TradeOfferSchema'),
					]
				],

				'TradeOfferCharacteristicFactory' => [
					'class' => '\UmiCms\System\Trade\Offer\Characteristic\Factory',
					'arguments' => [
						new ServiceReference('objects')
					]
				],

				'TradeOfferCharacteristicMapper' => [
					'class' => '\UmiCms\System\Trade\Offer\Characteristic\Mapper'
				],

				'TradeOfferCharacteristicCollection' => [
					'class' => '\UmiCms\System\Trade\Offer\Characteristic\Collection',
					'arguments' => [
						new ServiceReference('TradeOfferCharacteristicAttributeAccessor')
					]
				],

				'TradeOfferCharacteristicFacade' => [
					'class' => '\UmiCms\System\Trade\Offer\Characteristic\Facade',
					'arguments' => [
						new ServiceReference('TradeOfferCharacteristicMapper'),
						new ServiceReference('TradeOfferCharacteristicFactory'),
						new ServiceReference('TradeOfferCharacteristicCollection'),
						new ServiceReference('TradeOfferDataObjectFacade'),
						new ServiceReference('TradeOfferDataObjectTypeFacade'),
						new ServiceReference('fields')
					]
				],

				'FavoriteCurrencyUser' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Currency\Favorite\User',
					'arguments' => [
						new ServiceReference('Auth'),
						new ServiceReference('objects')
					]
				],

				'FavoriteCurrencyCustomer' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Currency\Favorite\Customer',
					'arguments' => [
						new ServiceReference('CookieJar')
					]
				],

				'FavoriteCurrencyFacade' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Currency\Favorite\Facade',
					'arguments' => [
						new ServiceReference('FavoriteCurrencyUser'),
						new ServiceReference('FavoriteCurrencyCustomer')
					]
				],

				'UmiDumpEntityBaseImporterFactory' => [
					'class' => '\UmiCms\System\Import\UmiDump\Entity\BaseImporter\Factory',
				],

				'TradeOfferSchema' => [
					'class' => '\UmiCms\System\Trade\Offer\Schema',
					'arguments' => [
						new ServiceReference('TradeOfferRelationAccessor')
					]
				],

				'TradeOfferPriceSchema' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Schema',
					'arguments' => [
						new ServiceReference('TradeOfferPriceRelationAccessor')
					]
				],

				'TradeOfferPriceTypeSchema' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Schema',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeRelationAccessor')
					]
				],

				'TradeStockBalanceSchema' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Schema',
					'arguments' => [
						new ServiceReference('TradeStockBalanceRelationAccessor')
					]
				],

				'TradeOfferBuilder' => [
					'class' => '\UmiCms\System\Trade\Offer\Builder',
					'arguments' => [
						new ServiceReference('TradeOfferMapper'),
						new ServiceContainerReference(),
						new ServiceReference('TradeOfferRelationMutator'),
						new ServiceReference('TradeOfferAttributeAccessor'),
						new ServiceReference('TradeOfferAttributeMutator')
					]
				],

				'TradeOfferPriceBuilder' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Builder',
					'arguments' => [
						new ServiceReference('TradeOfferPriceMapper'),
						new ServiceContainerReference(),
						new ServiceReference('TradeOfferPriceRelationMutator'),
						new ServiceReference('TradeOfferPriceAttributeAccessor'),
						new ServiceReference('TradeOfferPriceAttributeMutator')
					]
				],

				'TradeOfferPriceTypeBuilder' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Builder',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeMapper'),
						new ServiceContainerReference(),
						new ServiceReference('TradeOfferPriceTypeRelationMutator'),
						new ServiceReference('TradeOfferPriceTypeAttributeAccessor'),
						new ServiceReference('TradeOfferPriceTypeAttributeMutator')
					]
				],

				'TradeStockBalanceBuilder' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Builder',
					'arguments' => [
						new ServiceReference('TradeStockBalanceMapper'),
						new ServiceContainerReference(),
						new ServiceReference('TradeStockBalanceRelationMutator'),
						new ServiceReference('TradeStockBalanceAttributeAccessor'),
						new ServiceReference('TradeStockBalanceAttributeMutator'),
					]
				],

				'TradeOfferDemolisher' => [
					'class' => '\UmiCms\System\Trade\Offer\Demolisher',
					'arguments' => [
						new ServiceReference('TradeOfferFacade'),
						new ServiceReference('TradeOfferSchema')
					]
				],

				'TradeOfferExchange' => [
					'class' => '\UmiCms\System\Trade\Offer\Exchange',
					'arguments' => [
						new ServiceReference('TradeOfferImporter'),
						new ServiceReference('TradeOfferExporter'),
						new ServiceReference('TradeOfferFacade'),
						new ServiceReference('TradeOfferBuilder'),
						new ServiceReference('TradeOfferRelationAccessor'),
						new ServiceReference('TradeOfferDemolisher'),
					]
				],

				'TradeOfferPriceDemolisher' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Demolisher',
					'arguments' => [
						new ServiceReference('TradeOfferPriceFacade'),
						new ServiceReference('TradeOfferPriceSchema')
					]
				],

				'TradeOfferPriceExchange' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Exchange',
					'arguments' => [
						new ServiceReference('TradeOfferPriceImporter'),
						new ServiceReference('TradeOfferPriceExporter'),
						new ServiceReference('TradeOfferPriceFacade'),
						new ServiceReference('TradeOfferPriceBuilder'),
						new ServiceReference('TradeOfferPriceRelationAccessor'),
						new ServiceReference('TradeOfferPriceDemolisher')
					]
				],

				'TradeOfferPriceTypeDemolisher' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Demolisher',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeFacade'),
						new ServiceReference('TradeOfferPriceTypeSchema')
					]
				],

				'TradeOfferPriceTypeExchange' => [
					'class' => '\UmiCms\System\Trade\Offer\Price\Type\Exchange',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeImporter'),
						new ServiceReference('TradeOfferPriceTypeExporter'),
						new ServiceReference('TradeOfferPriceTypeFacade'),
						new ServiceReference('TradeOfferPriceTypeBuilder'),
						new ServiceReference('TradeOfferPriceTypeRelationAccessor'),
						new ServiceReference('TradeOfferPriceTypeDemolisher'),
					]
				],

				'TradeStockBalanceDemolisher' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Demolisher',
					'arguments' => [
						new ServiceReference('TradeStockBalanceFacade'),
						new ServiceReference('TradeStockBalanceSchema')
					]
				],

				'TradeStockBalanceExchange' => [
					'class' => '\UmiCms\System\Trade\Stock\Balance\Exchange',
					'arguments' => [
						new ServiceReference('TradeStockBalanceImporter'),
						new ServiceReference('TradeStockBalanceExporter'),
						new ServiceReference('TradeStockBalanceFacade'),
						new ServiceReference('TradeStockBalanceBuilder'),
						new ServiceReference('TradeStockBalanceRelationAccessor'),
						new ServiceReference('TradeStockBalanceDemolisher')
					]
				],

				'TradeOfferAttributeAccessor' => [
					'class' => 'UmiCms\System\Trade\Offer\Attribute\Accessor',
					'arguments' => [
						new ServiceReference('TradeOfferMapper'),
					]
				],

				'TradeOfferAttributeMutator' => [
					'class' => 'UmiCms\System\Trade\Offer\Attribute\Mutator',
					'arguments' => [
						new ServiceReference('TradeOfferMapper'),
					]
				],

				'TradeOfferRelationAccessor' => [
					'class' => 'UmiCms\System\Trade\Offer\Relation\Accessor',
					'arguments' => [
						new ServiceReference('TradeOfferMapper'),
					]
				],

				'TradeOfferRelationMutator' => [
					'class' => 'UmiCms\System\Trade\Offer\Relation\Mutator',
					'arguments' => [
						new ServiceReference('TradeOfferMapper'),
					]
				],

				'TradeOfferPriceAttributeAccessor' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Attribute\Accessor',
					'arguments' => [
						new ServiceReference('TradeOfferPriceMapper'),
					]
				],

				'TradeOfferPriceAttributeMutator' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Attribute\Mutator',
					'arguments' => [
						new ServiceReference('TradeOfferPriceMapper'),
					]
				],

				'TradeOfferPriceRelationAccessor' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Relation\Accessor',
					'arguments' => [
						new ServiceReference('TradeOfferPriceMapper'),
					]
				],

				'TradeOfferPriceRelationMutator' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Relation\Mutator',
					'arguments' => [
						new ServiceReference('TradeOfferPriceMapper'),
					]
				],

				'TradeOfferPriceTypeAttributeAccessor' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Type\Attribute\Accessor',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeMapper'),
					]
				],

				'TradeOfferPriceTypeAttributeMutator' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Type\Attribute\Mutator',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeMapper'),
					]
				],

				'TradeOfferPriceTypeRelationAccessor' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Type\Relation\Accessor',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeMapper'),
					]
				],

				'TradeOfferPriceTypeRelationMutator' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Type\Relation\Mutator',
					'arguments' => [
						new ServiceReference('TradeOfferPriceTypeMapper'),
					]
				],

				'TradeStockBalanceAttributeAccessor' => [
					'class' => 'UmiCms\System\Trade\Stock\Balance\Attribute\Accessor',
					'arguments' => [
						new ServiceReference('TradeStockBalanceMapper'),
					]
				],

				'TradeStockBalanceAttributeMutator' => [
					'class' => 'UmiCms\System\Trade\Stock\Balance\Attribute\Mutator',
					'arguments' => [
						new ServiceReference('TradeStockBalanceMapper'),
					]
				],

				'TradeStockBalanceRelationAccessor' => [
					'class' => 'UmiCms\System\Trade\Stock\Balance\Relation\Accessor',
					'arguments' => [
						new ServiceReference('TradeStockBalanceMapper'),
					]
				],

				'TradeStockBalanceRelationMutator' => [
					'class' => 'UmiCms\System\Trade\Stock\Balance\Relation\Mutator',
					'arguments' => [
						new ServiceReference('TradeStockBalanceMapper'),
					]
				],

				'TradeOfferCharacteristicAttributeAccessor' => [
					'class' => 'UmiCms\System\Trade\Offer\Characteristic\Attribute\Accessor',
					'arguments' => [
						new ServiceReference('TradeOfferCharacteristicMapper'),
					]
				],

				'TradeOffer' => [
					'class' => 'UmiCms\System\Trade\Offer',
					'arguments' => [
						new ServiceReference('TradeOfferBuilder')
					]
				],

				'TradeOfferPrice' => [
					'class' => 'UmiCms\System\Trade\Offer\Price',
					'arguments' => [
						new ServiceReference('TradeOfferPriceBuilder')
					],
					'calls' => [
						[
							'method' => 'setCurrencyFacade',
							'arguments' => [
								new ServiceReference('Currencies')
							]
						]
					]
				],

				'TradeOfferPriceType' => [
					'class' => 'UmiCms\System\Trade\Offer\Price\Type'
				],

				'TradeStockBalance' => [
					'class' => 'UmiCms\System\Trade\Stock\Balance',
					'arguments' => [
						new ServiceReference('TradeStockBalanceBuilder')
					]
				],

				'EmojiTranslator' => [
					'class' => 'UmiCms\System\Utils\Emoji\Translator',
				],

				'DataSetConfigXmlTranslator' => [
					'class' => 'UmiCms\Classes\System\Utils\DataSetConfig\XmlTranslator',
					'arguments' => [
						new ServiceReference('objectTypes'),
						new ServiceReference('cmsController'),
					]
				],

				'HierarchyElementChildrenIdGetter' => [
					'class' => 'UmiCms\System\Hierarchy\Element\ChildrenId\Getter',
					'arguments' => [
						new ParameterReference('connection')
					]
				]
			];
		}
	}
