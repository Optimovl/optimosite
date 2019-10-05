<?php

	interface iUmiEventsController {

		public function callEvent(iUmiEventPoint $eventPoint);

		public static function registerEventListener(iUmiEventListener $eventListener);
	}
