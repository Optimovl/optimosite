<?php

	/** @deprecated */
	class umiDumpSplitter extends umiImportSplitter {

		protected function readDataBlock() {
			// TODO: split umiDump
			secure_load_dom_document(file_get_contents($this->file_path), $doc);
			$this->offset = 0;
			$this->complete = true;
			return $doc;
		}

		public function translate(DomDocument $doc) {
			return $doc->saveXML();
		}
	}
