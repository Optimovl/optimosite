<?php

	class umiDirectoryIterator implements Iterator {

		private $arr_objs = [];

		public function __construct($arr_objs) {
			if (is_array($arr_objs)) {
				$this->arr_objs = $arr_objs;
			}
		}

		public function rewind() {
			reset($this->arr_objs);
		}

		public function current() {
			$oResult = null;
			$s_obj_path = current($this->arr_objs);
			if (is_file($s_obj_path)) {
				if (umiImageFile::getIsImage($s_obj_path)) {
					$oResult = new umiImageFile($s_obj_path);
				} else {
					$oResult = new umiFile($s_obj_path);
				}
			} elseif (is_dir($s_obj_path)) {
				$oResult = new umiDirectory($s_obj_path);
			}

			return $oResult;
		}

		public function key() {
			return current($this->arr_objs);
		}

		public function next() {
			$oResult = null;
			$s_obj_path = next($this->arr_objs);
			if (is_file($s_obj_path)) {
				if (umiImageFile::getIsImage($s_obj_path)) {
					$oResult = new umiImageFile($s_obj_path);
				} else {
					$oResult = new umiFile($s_obj_path);
				}
			} elseif (is_dir($s_obj_path)) {
				$oResult = new umiDirectory($s_obj_path);
			}

			return $oResult;
		}

		public function valid() {
			return $this->current() !== null;
		}
	}
