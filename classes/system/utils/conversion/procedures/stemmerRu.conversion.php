<?php

	require SYS_KERNEL_PATH . 'utils/conversion/dkStemmer.php';

	class stemmerRu implements IGenericConversion {

		public function convert($args) {
			if (isset($args[0])) {
				$stem = new Lingua_Stem_Ru;
				return $stem->stem_word($args[0]);
			}

			return $args;
		}
	}

