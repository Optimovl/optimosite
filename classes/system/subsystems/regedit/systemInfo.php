<?php

	use UmiCms\Service;
	use UmiCms\System\Request\iFacade;

	/**
	 * Класс сборщика системной информации
	 */
	class systemInfo implements iSystemInfo {

		/** @var iRegedit $registry реестр */
		private $registry;

		/** @var IConnection $connection подключение к бд */
		private $connection;

		/** @var iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/** @var iFacade $request запрос */
		private $request;

		/** @inheritdoc */
		public function __construct(
			iRegedit $registry, IConnection $connection, iDomainsCollection $domainCollection, iFacade $request
		) {
			$this->setRegistry($registry)
				->setConnection($connection)
				->setDomainCollection($domainCollection)
				->setRequest($request);
		}

		/** @inheritdoc */
		public function getInfo($option = 1) {
			if (!is_numeric($option)) {
				throw new coreException(__METHOD__ . ': incorrect option given');
			}

			$out = [];

			if (($option & self::SYSTEM) === self::SYSTEM) {
				$out['system'] = $this->getSystemInfo();
			}

			if (($option & self::PHP) === self::PHP) {
				$out['php'] = $this->getPHPParameters();
			}

			if (($option & self::DATABASE) === self::DATABASE) {
				$out['database'] = $this->getDataBaseInfo();
			}

			if (($option & self::NETWORK) === self::NETWORK) {
				$out['network'] = $this->getNetworkInfo();
			}

			if (($option & self::STAT) === self::STAT) {
				$out['stat'] = $this->getStatInfo();
			}

			if (($option & self::MODULES) === self::MODULES) {
				$out['modules'] = $this->getModulesInfo();
			}

			if (($option & self::LICENSE) === self::LICENSE) {
				$out['license'] = $this->getLicenseInfo();
			}

			if (($option & self::PHP_INFO) === self::PHP_INFO) {
				$out['php_info'] = $this->getPhpInfo();
			}

			return $out;
		}

		/**
		 * Возвращает общую информацию о UMI.CMS
		 * @return array массив с общей информацией о системе
		 *        - 'version' - версия системы
		 *        - 'revision' - ревизия системы
		 *        - 'license' - редакция системы
		 */
		private function getSystemInfo() {
			$registrySettings = Service::RegistrySettings();
			return [
				'version' => $registrySettings->getVersion(),
				'revision' => $registrySettings->getRevision(),
				'license' => $registrySettings->getEdition(),
			];
		}

		/**
		 * Возвращает информацию о php
		 * @return array массив с информацией о php
		 *        - 'version' - версия php
		 *        - 'os' - информация об операционной системе, на которой PHP был собран
		 *        - 'info' - дополнительная информация
		 *            - 'modules' - имена всех скомпилированных и загруженных модулей
		 *            - 'configurations' - все зарегистрированные настройки конфигурации
		 * @throws coreException
		 */
		private function getPHPParameters() {
			return [
				'version' => $this->parseVersion(phpversion()),
				'os' => php_uname(),
				'info' => [
					'modules' => get_loaded_extensions(),
					'configurations' => ini_get_all()
				]
			];
		}

		/**
		 * Возвращает информацию о бд
		 * @return array массив с информацией о бд
		 *        - 'driver' - название СУБД
		 *        - 'version' - версия СУБД
		 * @throws coreException
		 */
		private function getDataBaseInfo() {
			return [
				'driver' => iConfiguration::MYSQL_DB_DRIVER,
				'version' => $this->getMySQLVersion()
			];
		}

		/**
		 * Возвращает информацию о доменах и ip адресах
		 * @return array массив с информацией о доменах и ip адресах
		 *        - 'hosts' - домены системы @see systemInfo::getHostList()
		 *        - 'ip' - ip адрес сервера, на котором выполняется текущий скрипт.
		 */
		private function getNetworkInfo() {
			return [
				'hosts' => $this->getHostList(),
				'ip' => $this->getRequest()->serverAddress()
			];
		}

		/**
		 * Возвращает статистическую информацию
		 * @return array массив со статистической информацией
		 *        - 'last_update_time' - timestamp даты посленего обновления системы
		 *        - 'trial_days_left' - сколько дней осталось жить системе, если она на триальном периоде
		 *        - 'web_server_id' - строка идентификации сервера
		 */
		private function getStatInfo() {
			$registry = $this->getRegistry();
			return [
				'last_update_time' => Service::RegistrySettings()->getUpdateTime(),
				'trial_days_left' => $registry->getDaysLeft(),
				'web_server_id' => $this->getRequest()->Server()->get('SERVER_SOFTWARE')
			];
		}

		/**
		 * Возвращает информацию об установленных модулях UMI.CMS
		 * @return array массив с информацией об установленных модулях UMI.CMS
		 *        - # - имя модуля
		 */
		private function getModulesInfo() {
			$moduleList = $this->getRegistry()
				->getList('//modules');
			$moduleNameList = [];

			foreach ($moduleList as $key => $value) {
				if (isset($value[0])) {
					$moduleNameList[] = $value[0];
				}
			}

			return $moduleNameList;
		}

		/**
		 * Возвращает информацию о задействованной лицензии UMI.CMS
		 * @return array массив с информацией о задействованной лицензии UMI.CMS
		 *        - 'key' - доменный ключ
		 */
		private function getLicenseInfo() {
			return [
				'key' => Service::RegistrySettings()->getLicense()
			];
		}

		/**
		 * Возвращает информацию о php в виде html, @see phpinfo()
		 * @return string
		 */
		private function getPhpInfo() {
			ob_start();
			phpinfo();
			$phpInfo = ob_get_contents();
			ob_end_clean();
			return $phpInfo;
		}

		/**
		 * Разбирает строку с информацией о версии php|MySQL и оставляет только номер версии
		 * @param string $version строка с информацией о версии php|MySQL
		 * @return string
		 * @throws coreException если операция не удалась
		 */
		private function parseVersion($version) {
			preg_match('/[0-9]+\.[0-9]+\.[0-9]+/', $version, $matches);

			if (!isset($matches[0])) {
				throw new coreException(__METHOD__ . ': can\' grab version');
			}

			return $matches[0];
		}

		/**
		 * Возвращает версию MySQL
		 * @return string
		 * @throws coreException
		 */
		private function getMySQLVersion() {
			$mySQLServerInfo = $this->getConnection()
				->getServerInfo();
			return $this->parseVersion($mySQLServerInfo);
		}

		/**
		 * Возвращает информацию о доменах в UMI.CMS
		 * @return array массив с информацией о доменах в UMI.CMS
		 *        - id - ид домена
		 *            - 'host' - хост домена
		 *            - 'mirror'
		 *                - # - хост зеркала домена
		 *            - 'is_default' - выводится если домен основной
		 */
		private function getHostList() {
			$hostList = [];

			/* @var iDomain $domain */
			foreach ($this->getDomainCollection()->getList() as $domain) {
				$hostList[$domain->getId()]['host'] = $domain->getHost();
				$mirrorList = $domain->getMirrorsList();
				/* @var iDomainMirror $mirror */
				foreach ($mirrorList as $mirror) {
					$hostList[$domain->getId()]['mirrors'][] = $mirror->getHost();
				}
				if ($domain->getIsDefault()) {
					$hostList[$domain->getId()]['is_default'] = '1';
				}
			}

			return $hostList;
		}

		/**
		 * Возвращает реестр
		 * @return iRegedit
		 */
		private function getRegistry() {
			return $this->registry;
		}

		/**
		 * Устанавливает реестр
		 * @param iRegedit $registry реестр
		 * @return $this
		 */
		private function setRegistry(iRegedit $registry) {
			$this->registry = $registry;
			return $this;
		}

		/**
		 * Возвращает подключение к бд
		 * @return IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Устанавливает подключение к бд
		 * @param IConnection $connection подключение к бд
		 * @return $this
		 */
		private function setConnection(IConnection $connection) {
			$this->connection = $connection;
			return $this;
		}

		/**
		 * Возвращает коллекцию доменов
		 * @return iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}

		/**
		 * Устанавливает коллекцию доменов
		 * @param iDomainsCollection $domainCollection коллекция доменов
		 * @return $this
		 */
		private function setDomainCollection(iDomainsCollection $domainCollection) {
			$this->domainCollection = $domainCollection;
			return $this;
		}

		/**
		 * Возвращает запрос
		 * @return iFacade
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Устанавливает запрос
		 * @param iFacade $request запрос
		 * @return $this
		 */
		private function setRequest(iFacade $request) {
			$this->request = $request;
			return $this;
		}

		/** @deprecated  */
		public static function getInstance($c = null) {
			return Service::SystemInfo();
		}
	}
