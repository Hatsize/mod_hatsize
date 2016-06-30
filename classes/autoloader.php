<?php
class HatsizeAutoloader {
	public static function loadClass($class) {
		$filename = __DIR__ . "/" . str_replace('\\', DIRECTORY_SEPARATOR, $class) . ".php";
		
		if(file_exists($filename)) {
			includeFile($filename);
			return true;
		}
	}
}

/**
 * Scope isolated include.
 *
 * Prevents access to $this/self from included files.
 */
function includeFile($file) {
	include $file;
}

spl_autoload_register('HatsizeAutoloader::loadClass', true, false);
