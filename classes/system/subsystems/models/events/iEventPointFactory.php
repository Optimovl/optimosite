<?php

	namespace UmiCms\System\Events;

	/** Интерфейс фабрики событий */
	interface iEventPointFactory {

		/**
		 * Создает событие
		 * @param string $id идентификатор события
		 * @param string $mode режим вызова события
		 * @param array $moduleList список модулей, обработчики которых поддерживаются событием.
		 * Если список пуст - значит событие поддерживает обработчики всех модулей.
		 * @return \iUmiEventPoint
		 */
		public function create($id, $mode = 'process', array $moduleList = []);
	}
