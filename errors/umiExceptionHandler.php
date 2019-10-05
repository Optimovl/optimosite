<?php

	use UmiCms\Service;

	class umiExceptionHandler {

		/** @var string $templateFile Шаблон для вывода исключений */
		private static $templateFile;

		/**
		 * Возвращает путь до шаблона для вывода исключений.
		 * Устанавливает default шаблон, при он еще не был задан.
		 * @return string
		 */
		protected static function getExceptionTemplate() {
			if (!file_exists(self::$templateFile)) {
				self::setExceptionTemplate(SYS_ERRORS_PATH . 'exception.html.php');
			}

			return self::$templateFile;
		}

		/**
		 * Устанавливает шаблон вывода для исключения
		 * @param string $file путь до файла шаблона
		 * @throws Exception если шаблон не существует
		 */
		protected static function setExceptionTemplate($file) {
			if (!file_exists($file)) {
				throw new Exception('Exception template not exists');
			}

			self::$templateFile = $file;
		}

		/**
		 * Выводит сообщение об исключении
		 * @param Exception $e Исключение
		 */
		protected static function printTemplate($e) {
			$exception = new stdClass();
			$exception->code = $e->getCode();
			$exception->message = $e->getMessage();
			$exception->type = get_class($e);
			if (DEBUG_SHOW_BACKTRACE) {
				$exception->trace = $e->getTrace();
				$exception->traceAsString = $e->getTraceAsString();
			}

			require self::getExceptionTemplate();
		}

		/**
		 * Устанавливает обработчик исключений и шаблон вывода.
		 * Обработчик должен быть статическим методом этого класса
		 * @param string $exceptionHandler Имя обработчика
		 * @param string $template Шаблон вывода
		 * @return callable Прошлый обработчик
		 * @throws Exception если обработчика не существует
		 */
		public static function set($exceptionHandler = 'base', $template = '') {
			$exceptionHandler = $exceptionHandler . 'Handler';

			if (method_exists(__CLASS__, $exceptionHandler)) {
				self::setExceptionTemplate($template);
				return set_exception_handler([
					__CLASS__,
					$exceptionHandler
				]);
			}

			throw new Exception('Error handler not exist');
		}

		/**
		 * Устанавливает прошлый обработчик исключений
		 * @link http://php.net/manual/en/function.restore-exception-handler.php
		 * @return bool Всегда возвращает TRUE
		 */
		public static function restore() {
			return restore_exception_handler();
		}

		/**
		 * Записывает информацию об ошибке в лог
		 * @param string $message Сообщение об ошибке
		 * @param string $trace Trace ошибки
		 * @return bool
		 */
		public static function createCrashReport($message, $trace) {
			$logExceptions = mainConfiguration::getInstance()->get('debug', 'log-exceptions');

			if (!$logExceptions) {
				return false;
			}

			$logsDirectory = ERRORS_LOGS_PATH . '/exceptions/';

			if (!is_dir($logsDirectory)) {
				mkdir($logsDirectory, 0777, true);
			}

			try {
				$logger = new umiLogger($logsDirectory);
				$logger->pushGlobalEnvironment();
				$logger->push($message);
				$logger->push($trace);
				$logger->save();
				return true;
			} catch (Exception $e) {
				return false;
			}
		}

		/**
		 * Записывает исключение в лог
		 * @param Exception $exception исключение
		 * @return bool
		 */
		public static function report(Exception $exception) {
			return self::createCrashReport($exception->getMessage(), $exception->getTraceAsString());
		}

		/**
		 * Стандартный обработчик исключений
		 * @param Exception $e Брошенное исключение
		 * @throws Exception
		 */
		public static function baseHandler($e) {
			if (!DEBUG_SHOW_BACKTRACE && $e instanceof databaseException) {
				$e = new Exception(
					'Произошла критическая ошибка. Скорее всего, потребуется участие разработчиков.  Подробности по ссылке <a title="" target="_blank" href="https://errors.umi-cms.ru/17000/">17000</a>',
					17000,
					$e
				);
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->status(500);
			self::printTemplate($e);
			self::createCrashReport($e->getMessage(), $e->getTraceAsString());
			$buffer->stop();
		}

		/**
		 * Обрабатывает аварийное завершение запроса
		 * @return bool
		 */
		public static function handleShutdown() {
			if (!mainConfiguration::getInstance()->get('debug', 'handle-shutdown')) {
				return false;
			}
			
			register_shutdown_function(function () {
				if (libxml_get_last_error()) {
					var_export(libxml_get_last_error());
					echo '<br />';
				}

				if (error_get_last()) {
					var_export(error_get_last());
				}
			});
		}
	}
