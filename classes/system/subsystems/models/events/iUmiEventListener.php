<?php

	interface iUmiEventListener {

		public function __construct($eventId, $callbackModule, $callbackMethod);

		public function setPriority($priority = 5);

		public function getPriority();

		public function setIsCritical($isCritical = false);

		public function getIsCritical();

		public function getEventId();

		public function getCallbackModule();

		public function getCallbackMethod();
	}
