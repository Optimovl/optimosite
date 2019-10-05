<?php

	/** Обработчик события, которые опеределяет выполняемый метод в случае вызова события */
	class umiEventListener implements iUmiEventListener {

		protected $eventId, $callbackModule, $callbackMethod,
			$isCritical,
			$priority;

		/**
		 * Конструктор обработчика события, где событие определяется строковым id, а обработчик исполняемым модулем/методом.
		 * @param int $eventId строковой id события
		 * @param string $callbackModule название модуля, котороый будет выполнять обработку
		 * @param string $callbackMethod название метода, котороый будет выполнять обработку
		 */
		public function __construct($eventId, $callbackModule, $callbackMethod) {
			$this->eventId = $eventId;
			$this->callbackModule = (string) $callbackModule;
			$this->callbackMethod = (string) $callbackMethod;

			$this->setPriority();
			$this->setIsCritical();

			umiEventsController::registerEventListener($this);
		}

		/**
		 * Установить приоритет обработчика события.
		 * @param int $priority = 5 приоритет от 0 до 9
		 */
		public function setPriority($priority = 5) {
			$priority = (int) $priority;

			if ($priority < 0 || $priority > 9) {
				throw new coreException('EventListener priority can only be between 0 ... 9');
			}
			$this->priority = $priority;
		}

		/**
		 * Узнать текущий приоритет
		 * @return int приоритет обработчика событий
		 */
		public function getPriority() {
			return $this->priority;
		}

		/**
		 * Установить критичность обработчика события.
		 * Если событие критично, то при возникновении любого исключения в этом обработчике,
		 * цепочка вызова обработчиков событий будет прервана.
		 * @param bool $isCritical = false критичность обработчика
		 */
		public function setIsCritical($isCritical = false) {
			$this->isCritical = (bool) $isCritical;
		}

		/**
		 * Получить критичность обработчика события
		 * @return bool критичность обработчика события
		 */
		public function getIsCritical() {
			return $this->isCritical;
		}

		/**
		 * Узнать строковой id события, который прослушивает этот обработчик события
		 * @return string строковой id события
		 */
		public function getEventId() {
			return $this->eventId;
		}

		/**
		 * Узнать, какой модуль будет выполнять обработку события
		 * @return string название модуля-обработчика
		 */
		public function getCallbackModule() {
			return $this->callbackModule;
		}

		/**
		 * Узнать, какой метод будет выполнять обработку события
		 * @return string название метода-обработчика
		 */
		public function getCallbackMethod() {
			return $this->callbackMethod;
		}
	}

