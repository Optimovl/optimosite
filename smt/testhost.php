<?php

	/**
	 * Внимание: в рамках этого файла используются возможности PHP <= 5.3 для обратной совместимости.
	 *
	 * При обновлении этого файла не забывайте обновлять аналогичный функционал
	 * на сайте umi-cms.ru в функции check_phpinfo в файле classes/modules/updatesrv/class.php.
	 * @link https://www.umi-cms.ru/support/umi_cms_php_hosting/proverka_hostinga/
	 *
	 * Проверяет системные требования umi.cms на хостинге.
	 */
	class testHost {

		/**
		 * @var array Проваленные тесты в формате:
		 * [
		 *   [
		 *     0 => string <Код ошибки>,
		 *     1 => bool <Критичность>,
		 *     2 => string <Дополнительные параметры ошибки>,
		 *   ]
		 * ]
		 *
		 * Публичное свойство оставлено для обратной совместимости
		 * со старыми версиями установщика.
		 */
		public $listErrors;

		/** @var bool Работает ли система в консольном режиме */
		private $cliMode;

		/** @var string Адрес домена, с которого запускаются тесты */
		private $domain;

		/** @var string Хост базы данных */
		private $host;

		/** @var string Пользователь базы данных */
		private $user;

		/** @var string Пароль базы данных */
		private $password;

		/** @var string Название базы данных */
		private $database;

		/** @var mysqli Ссылка на ресурс БД */
		private $link;

		/**
		 * @param mixed $phpInfo не используется
		 * @param string $domain Адрес домена, с которого запускаются тесты
		 */
		public function __construct($phpInfo = array(), $domain = '') {
			$this->listErrors = array();
			$this->cliMode = defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE;
			$this->domain = $domain;
		}

		/**
		 * Устанавливает параметры базы данных
		 * @param string $host хост
		 * @param string $user пользователь
		 * @param string $password пароль
		 * @param string $database название БД
		 */
		public function setConnect($host, $user, $password, $database) {
			$this->user = $user;
			$this->host = $host;
			$this->password = $password;
			$this->database = $database;
		}

		/**
		 * Запускает тесты и возвращает информацию о проваленных тестах
		 * @return array
		 */
		public function getResults() {
			$this->runAllTests();
			return $this->listErrors;
		}

		/**
		 * Выполняет все тесты.
		 * Тест - это любой метод, который начинается со слова 'test'.
		 */
		private function runAllTests() {
			foreach (get_class_methods($this) as $methodName) {
				if (preg_match('/^test/', $methodName)) {
					$this->$methodName();
				}
			}
		}

		/** Проверка IIS */
		private function testIIS() {
			$serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? mb_strtolower($_SERVER['SERVER_SOFTWARE']) : '';
			$this->assert(mb_strpos($serverSoftware, 'microsoft-iis') === false, 13090, false);
		}

		/**
		 * Добавляет сообщение в случае ошибки
		 * @param bool $isCorrect Есть ошибка/нет ошибки
		 * @param string $errorCode Код ошибки
		 * @param bool $critical Критичность
		 * @param string $errorParams Дополнительные параметры ошибки
		 */
		private function assert($isCorrect, $errorCode, $critical = true, $errorParams = '') {
			if (!$isCorrect) {
				$this->listErrors[] = array($errorCode, $critical, $errorParams);
			}
		}

		/** Проверка версии PHP */
		private function testPhpVersion() {
			$check = version_compare(phpversion(), '5.6.0', '>') && version_compare(phpversion(), '7.4', '<');
			$this->assert($check, 13000);
		}

		/** Проверка отсутствия Suhosin Patch */
		private function testSuhosin() {
			$this->assert(!extension_loaded('suhosin'), 13001, false);
		}

		/** Проверка параметра memory_limit - 32m минимум */
		private function testMemoryLimit() {
			$memoryLimit = ini_get('memory_limit');

			if (!$memoryLimit) {
				$this->assert(false, 13002, false);
				return;
			}

			if ($memoryLimit < 0) {
				return;
			}

			$limitMeasure = $memoryLimit[mb_strlen($memoryLimit) - 1];
			$lowerCaseLimitMeasure = mb_strtolower($limitMeasure);

			if (in_array($lowerCaseLimitMeasure, array('g', 'm', 'k'))) {
				$limitValue = (int) str_replace($limitMeasure, '', $memoryLimit);

				switch ($lowerCaseLimitMeasure) {
					case 'g':
						$limitValue *= 1024 * 1024 * 1024;
						break;
					case 'm':
						$limitValue *= 1024 * 1024;
						break;
					case 'k':
						$limitValue *= 1024;
						break;
				}

			} else {
				$limitValue = (int) $memoryLimit;
			}

			$this->assert($limitValue >= 32 * 1024 * 1024, 13003);
		}

		/** Проверка наличия модуля mod_rewrite в Apache */
		private function testModRewrite() {
			if (!$this->cliMode && $this->isApacheServer()) {
				$this->assert(in_array('mod_rewrite', apache_get_modules()), 13007);
			}
		}

		/** Метод проверяет, запущен ли php под Apache с помощью mod_php */
		private function isApacheServer() {
			return extension_loaded('apache2handler');
		}

		/** Проверка наличия модуля mod_auth в Apache */
		private function testModAuth() {
			if (!$this->cliMode && $this->isApacheServer()) {
				$this->assert(in_array('mod_auth_basic', apache_get_modules()), 13009);
			}
		}

		/** Проверка наличия библиотек */
		private function testExtensions() {
			$extensionList = array(
				'zlib',
				'gd',
				'libxml',
				'iconv',
				'xsl',
				'simplexml',
				'xmlreader',
				'mbstring',
				'json',
				'mysqli',
				'curl',
				'phar'
			);
			$errorCounter = 0;

			foreach ($extensionList as $extension) {
				$this->assert(extension_loaded($extension), 13030 + $errorCounter);
				$errorCounter += 1;
			}
		}

		/** Проверка текущей директории на запись */
		private function testPermissions() {
			$this->assert(is_writable(__DIR__), 13010);
		}

		/** Проверка работы сессии */
		private function testSession() {
			if (!$this->domain) {
				return;
			}

			if (defined('INSTALLER_CURRENT_WORKING_DIR')) {
				$rootDirectoryPath = INSTALLER_CURRENT_WORKING_DIR;
			} else {
				$rootDirectoryPath = CURRENT_WORKING_DIR;
			}

			file_put_contents($rootDirectoryPath . '/umi_smt.php', '<?php
				@session_start();
				$_SESSION["test"] = "test";
				$sessionId = session_id();
				@session_write_close();
				unset($_SESSION["test"]);
				@session_id($sessionId);
				@session_start();
				echo($_SESSION["test"]);'
			);

			if (defined('PHP_FILES_ACCESS_MODE')) {
				chmod($rootDirectoryPath . '/umi_smt.php', PHP_FILES_ACCESS_MODE);
			} else {
				$mode = mb_substr(decoct(fileperms(__FILE__)), -4, 4);
				chmod($rootDirectoryPath . '/umi_smt.php', octdec($mode));
			}

			$checkUrl = $this->getProtocol() . '://' . $this->domain . '/umi_smt.php';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $checkUrl);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($ch);

			if ($result !== false) {
				$this->assert($result == 'test', 13083);
			}

			unlink($rootDirectoryPath . '/umi_smt.php');
		}

		/**
		 * Возвращает протокол работы сервера
		 * @return string
		 */
		private function getProtocol() {
			if (function_exists('getServerProtocol')) {
				return getServerProtocol();
			}

			if ($this->isHttps()) {
				return 'https';
			}

			return 'http';
		}

		/**
		 * Определяет, работает ли сервер по протоколу https
		 * @return bool
		 */
		private function isHttps() {
			if (isset($_SERVER['HTTPS']) && in_array($_SERVER['HTTPS'], array('ON', 'On', 'on', 1))) {
				return true;
			}

			if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
				return true;
			}

			if (isset($_SERVER['SERVER_PROTOCOL']) && mb_strtolower(mb_substr($_SERVER['SERVER_PROTOCOL'], 0, 5)) == 'https') {
				return true;
			}

			return false;
		}

		/** Проверка подключения к базе данных, определение кодировки, разрешений на изменения */
		private function testConnect() {
			if (!extension_loaded('mysqli')) {
				return;
			}

			$this->link = mysqli_init();
			mysqli_real_connect($this->link, $this->host, $this->user, $this->password, $this->database);
			$this->assert((bool) $this->link, 13011);

			if (!$this->link) {
				return;
			}

			$this->checkMysqlVersion();
			$this->checkBasicDatabaseQueries();
			$this->checkInnoDbSupport();
		}

		/** Проверяет версию MyQSL */
		private function checkMysqlVersion() {
			$mysqlVersion = mysqli_get_server_version($this->link);
			if ($mysqlVersion) {
				$this->assert(version_compare($mysqlVersion, '40100', '>='), 13071);
			} else {
				$this->assert(false, 13070);
			}
		}

		/** Проверяет работу базовых SQL-запросов на тестовой таблице */
		private function checkBasicDatabaseQueries() {
			$time = time();
			$this->assertQuery("create table `test{$time}` (a int not null auto_increment, primary key (a))", 13013);
			$this->assertQuery("create temporary table `temporary_table{$time}` like `test{$time}`", 13048);
			$this->query("drop temporary table `temporary_table{$time}`");

			$this->assertQuery("alter table `test{$time}` ADD b int(7) NULL", 13014);
			$this->assertQuery("insert into `test{$time}` (b) values (11)", 13043);
			$this->assertQuery("select * from `test{$time}`", 13044);
			$this->assertQuery("update `test{$time}` set b=12 where b=11", 13045);
			$this->assertQuery("delete from `test{$time}`", 13046);
			$this->assertQuery('SET foreign_key_checks = 1', 13047);
			$this->assertQuery("drop table `test{$time}`", 13015);
		}

		/**
		 * Проверяет, что SQL-запрос выполняется успешно.
		 * В противном случае добавляет сообщение об ошибке.
		 * @param string $sql SQL-запрос
		 * @param string $errorCode Код ошибки
		 */
		private function assertQuery($sql, $errorCode) {
			return $this->assert($this->query($sql), $errorCode);
		}

		/**
		 * Возвращает результат запроса к базе данных
		 * @param string $sql SQL-запрос
		 * @return bool|mysqli_result
		 */
		private function query($sql) {
			return mysqli_query($this->link, $sql);
		}

		/** Определяет, поддерживает ли база данных движок InnoDB */
		private function checkInnoDbSupport() {
			$isSupported = false;
			$result = $this->query("SHOW VARIABLES LIKE 'have_innodb'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_array($result);
				if (mb_strtolower($row['Value']) == 'yes') {
					$isSupported = true;
				}
			} else {
				$result = $this->query('SHOW ENGINES');

				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_assoc($result)) {
						if (mb_strtolower($row['Engine']) == 'innodb' &&
							in_array(mb_strtolower($row['Support']), array('yes', 'default'))
						) {
							$isSupported = true;
							break;
						}
					}
				}
			}

			$this->assert($isSupported, 13016);
		}
	}
