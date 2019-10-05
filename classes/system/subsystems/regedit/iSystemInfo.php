<?php

	use UmiCms\System\Request\iFacade;

	/**
	 * Интерфейс сборщика системной информации
	 */
	interface iSystemInfo {

		/* @const int ALL Вся доступная информация */
		const ALL = -1;

		/* @const int SYSTEM Общая информация о системе */
		const SYSTEM = 1;

		/* @const int PHP Информация о PHP и его модулях */
		const PHP = 2;

		/* @const int DATABASE Информация о базе данных */
		const DATABASE = 4;

		/* @const int NETWORK Информация о доменах и ip адресах */
		const NETWORK = 8;

		/* @const int STAT Статистическая информация о системе */
		const STAT = 16;

		/* @const int MODULES Информация об установленных модулях UMI.CMS */
		const MODULES = 32;

		/* @const int LICENSE Информация о лицензионном ключе */
		const LICENSE = 64;

		/* @const int PHP_INFO php info */
		const PHP_INFO = 128;

		/**
		 * Констуктор
		 * @param iRegedit $registry реестр
		 * @param IConnection $connection подключение к бд
		 * @param iDomainsCollection $domainCollection коллекция доменов
		 * @param iFacade $request запрос
		 */
		public function __construct(
			iRegedit $registry, IConnection $connection, iDomainsCollection $domainCollection, iFacade $request
		);

		/**
		 * Возвращает системную информацию.
		 * @param int $option модификатор для вывода различных блоков (см. константы класса, -1 = вывести все)
		 * @return array многомерный массив с различной информацией о системе и окружении
		 *        - 'system'   - информация о системе @see systemInfo::getSystemInfo()
		 *        - 'php'      - параметры php @see systemInfo::getPHPParameters()
		 *        - 'database' - информация о бд @see systemInfo::getDataBaseInfo()
		 *        - 'network'  - информация о доменах и ip адресах @see systemInfo::getNetworkInfo()
		 *        - 'stat'     - статистическая информация о системе @see systemInfo::getStatInfo()
		 *        - 'modules'  - информация об установленных модулях UMI.CMS @see systemInfo::getModulesInfo()
		 *        - 'license'  - информация о задействованной лицензии @see systemInfo::getLicenseInfo()
		 *        - 'php_info'  - информация о php в виде html @see systemInfo::getPhpInfo()
		 * @example systemInfo::getInstance()->getInfo(systemInfo::SYSTEM);
		 * @throws coreException если $option не является числом
		 */
		public function getInfo($option = 1);
	}