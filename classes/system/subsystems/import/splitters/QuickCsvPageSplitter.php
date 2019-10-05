<?php

	/** Быстрый csv импортер страниц */
	class QuickCsvPageSplitter extends csvSplitter {

		/** @inheritdoc */
		protected function resetState() {
			$sourceName = $this->sourceName;
			parent::resetState();
			$this->setSourceName($sourceName);
		}
	}
