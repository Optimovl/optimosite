<?php

	interface iPagenum {

		public static function generateNumPage($total, $perPage, $template = 'default', $pageParam = 'p');

		public static function generateOrderBy($fieldName, $type_id, $template = 'default');
	}

