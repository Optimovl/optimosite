<?php

	/** Интерфейс класса, работающего с базой данных */
	interface iUmiDataBaseInjector {

		/**
		 * Устанавливает подключение к базе данных
		 * @param IConnection $connection подключение к базе данных
		 */
		public function setConnection(IConnection $connection);

		/**
		 * Возвращает подключение к базе данных
		 * @return IConnection
		 */
		public function getConnection();
	}

