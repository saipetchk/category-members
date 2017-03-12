<?php

use phpFastCache\CacheManager;

class FileCache {

	const CONFIG_FILE_PATH = '/tmp/';

	private static $instance = NULL;

	public static function getInstance()
	{
		if ( is_null( self::$instance ) ) {
			// Set path for config files
			CacheManager::setDefaultConfig( [ 'path' => self::CONFIG_FILE_PATH ] );

			self::$instance = CacheManager::getInstance( 'files' );
		}

		return self::$instance;
	}

}

?>

