<?php

	/** Interface IUmiZipArchive */
	interface IUmiZipArchive {

		public function __construct($archiveName, $zipArchiveClass);

		public function create($files, $removeFromPath = null, $addToPath = null);

		public function add($files, $pathToRemove = null, $pathToAdd = null);

		public function extract($path = '.', $ignoreFolders, $beforeCallback = null, $afterCallback = null);

		public function listContent();

		public function errorInfo();
	}
