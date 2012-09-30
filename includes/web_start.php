<?php
/*
 * Script which provides functions etc. needed for all requests
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */
if(!defined('IN_HOO_TS')) {
	exit();
}
if(!defined('MACHINE_READABLE')) {
	define('MACHINE_READABLE', false);
}

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/hoo.php');

function autoloader($class) {
	global $_CONFIG;
	if(!isset($_CONFIG['classes'][ $class ])) {
		throw new exception('Unknown class ' . $class);
	}
	require_once($_CONFIG['include_path'] . $_CONFIG['classes'][ $class ]);
}

spl_autoload_register('autoloader');

function exception_handler($exception) {
	if($exception instanceof database_exception) {
		log::write_line($exception->getMessage(), $exception->getFile(), $exception->getLine());
	}
	if(!MACHINE_READABLE) {
		hoo_base::show_error($exception->getMessage());
	}else{
		if(!defined('MACHINE_READABLE_FORMAT')) {
			define('MACHINE_READABLE_FORMAT', 'xml');
		}
		machine_readable::show_error($exception->getMessage());
	}
}

set_exception_handler('exception_handler');
