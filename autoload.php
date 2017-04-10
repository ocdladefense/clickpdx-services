<?php



/**
 * Clickpdx autoloader.
 *
 * Load classes in the Clickpdx namespace.
 */
$clickpdx=createAutoloader(array(
		'oauth/lib',
		'resource/lib',
		'salesforce/lib',
		'service/lib',
	),DRUPAL_ROOT .'/core/vendor/clickpdx');
	
	
spl_autoload_register($clickpdx);