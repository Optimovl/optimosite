<?php

	/** Фабрика источников манифестов */
	class ManifestSourceFactory implements iManifestSourceFactory {

		/** @inheritdoc */
		public function create($type = self::CORE, $name = null) {

			switch ($type) {
				case self::CORE : {
					return new CoreManifestSource();
				}
				case self::MODULE : {
					return new ModuleManifestSource($name);
				}
				case self::SOLUTION : {
					return new SolutionManifestSource($name);
				}
				default : {
					throw new Exception('Unknown type: ' . $type);
				}
			}
		}
	}
