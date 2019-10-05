<?php

	use UmiCms\Service;

	/** Класс для регистрации и управления вызовами событий */
	class umiEventsController implements iUmiEventsController {

		protected static $eventListeners = [];

		private static $oInstance;

		protected function __construct() {}

		/**
		 * Вернуть экземпляр коллекции
		 * @return umiEventsController
		 */
		public static function getInstance() {
			if (self::$oInstance == null) {
				self::$oInstance = new umiEventsController();
			}
			return self::$oInstance;
		}

		protected function loadEventListeners() {
			$modules_keys = Service::Registry()->getList('//modules');

			foreach ($modules_keys as $arr) {
				$module = $arr[0];
				$this->loadModuleEventListeners($module);
			}
		}

		protected function loadModuleEventListeners($module) {
			$path = SYS_MODULES_PATH . "{$module}/events.php";
			$path_custom = SYS_MODULES_PATH . "{$module}/custom_events.php";

			$resourcesDir = cmsController::getInstance()->getResourcesDirectory();
			if ($resourcesDir) {
				$this->tryLoadEvents($resourcesDir . "/classes/modules/{$module}/events.php");
			}

			$pathExtEvents = SYS_MODULES_PATH . "{$module}/ext/events_*.php";
			$extEvents = glob($pathExtEvents);
			if (is_array($extEvents)) {
				foreach (glob($pathExtEvents) as $filename) {
					if (file_exists($filename)) {
						$this->tryLoadEvents($filename);
					}
				}
			}
			$this->tryLoadEvents($path_custom);
			$this->tryLoadEvents($path);
		}

		protected function tryLoadEvents($path) {
			if (file_exists($path)) {
				require $path;
				return true;
			}

			return false;
		}

		protected function searchEventListeners($eventId) {
			static $cache = [];

			if (isset($cache[$eventId])) {
				return $cache[$eventId];
			}

			$result = [];

			if (count(self::$eventListeners) === 0) {
				$this->loadEventListeners();
			}

			foreach (self::$eventListeners as $eventListener) {
				if ($eventListener->getEventId() == $eventId) {
					$result[] = $eventListener;
				}
			}

			$temp = [];

			foreach ($result as $callback) {
				$temp[$callback->getPriority()][] = $callback;
			}

			$result = [];
			ksort($temp);

			foreach ($temp as $callbackArray) {
				foreach ($callbackArray as $callback) {
					$result[] = $callback;
				}
			}

			$cache[$eventId] = $result;

			return $cache[$eventId];
		}

		protected function executeCallback($callback, $eventPoint) {
			$module = $callback->getCallbackModule();
			$method = $callback->getCallbackMethod();
			$module_inst = cmsController::getInstance()->getModule($module);

			if ($module_inst) {
				$module_inst->$method($eventPoint);
			} else {
				throw new coreException("Cannot find module \"{$module}\"");
			}
		}

		/**
		 * Вызвать событие и выполнить все обработчики, которые его слушают
		 * @param iUmiEventPoint $eventPoint точка входа в событие
		 * @param array $allowed_modules
		 * @return array лог обработанных callback'ов
		 * @throws Exception
		 * @throws baseException
		 */
		public function callEvent(iUmiEventPoint $eventPoint, $allowed_modules = []) {
			$eventId = $eventPoint->getEventId();
			$callbacks = $this->searchEventListeners($eventId);
			$logs = ['executed' => [], 'failed' => [], 'breaked' => []];

			foreach ($callbacks as $callback) {

				if (!empty($allowed_modules) && !in_array($callback->getCallbackModule(), $allowed_modules)) {
					continue;
				}

				try {
					$this->executeCallback($callback, $eventPoint);
					$logs['executed'][] = $callback;
				} catch (baseException $e) {
					$logs['failed'][] = $callback;

					if ($callback->getIsCritical()) {
						throw $e;
					}

					continue;
				} catch (breakException $e) {
					$logs['breaked'][] = $callback;
					break;
				}
			}

			return $logs;
		}

		/**
		 * Зарегистрировать в коллекции обработчик события
		 * @param iUmiEventListener $eventListener обработчик события
		 */
		public static function registerEventListener(iUmiEventListener $eventListener) {
			self::$eventListeners[] = $eventListener;
		}
	}

