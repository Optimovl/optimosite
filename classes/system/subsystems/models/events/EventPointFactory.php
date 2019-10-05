<?php

	namespace UmiCms\System\Events;

	/** Фабрика событий */
	class EventPointFactory implements iEventPointFactory {

		/** @inheritdoc */
		public function create($id, $mode = 'process', array $moduleList = []) {
			$eventPoint = new \umiEventPoint($id);
			return $eventPoint->setMode($mode)
				->setModules($moduleList);
		}
	}
