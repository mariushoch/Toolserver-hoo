<?php
/*
 * API entry point script
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */

define('IN_HOO_TS', true);
define('MACHINE_READABLE', true);

/*
ini_set("display_errors", 1);
error_reporting(-1);
//*/

require_once('includes/web_start.php');

$hoo = new hoo_base();

// Output format
$format = $hoo->get_user_input('format');
if($format !== null && !in_array($format, machine_readable::$supported_formats)) {
	define('MACHINE_READABLE_FORMAT', 'xml');
	throw new exception('Unknown format');
}else{
	define('MACHINE_READABLE_FORMAT', $format);
}

// Known API module?
$action = $hoo->get_user_input('action');
if($action === null) {
	// action defaults to info
	$action = 'info';
}
if(!isset($_CONFIG['api_modules'][ $action ])) {
	throw new exception('Unknown action');
}

// Create the API object
$api_module = 'api' . ucfirst($action);
$api_handle = $api_module::main();
if(!$api_handle->is_public()) {
	throw new exception('This API module can\'t be used via the web interface');
}

// fully pass along _GET and _POST (post overwrites get)
// and let the api class handle any problems
$data = $api_handle->exec(array_merge($_GET, $_POST));

// Output, if the module doesn't do that at it's own
if(!$api_handle->has_own_output()) {
	// set headers
	$expires = $api_handle->get_expiry();
	if($hoo->get_user_input('expires', 'int') !== null) {
		$expires = $hoo->get_user_input('expires', 'int');
	}
	machine_readable::set_headers(MACHINE_READABLE_FORMAT, $expires);
	// send the data to the client
	$prepend = '';
	$append = '';
	if(MACHINE_READABLE_FORMAT === 'json') {
		//callback, assign to var or plain?
		$callback = $hoo->get_user_input( 'callback' );
		$var =  $hoo->get_user_input( 'js_var' );
		if($callback) {
			$prepend = $callback . '(';
			$append = ');';
		}elseif($var) {
			$prepend = 'var ' . $var . ' = ';
			$append = ';';
		}
	}
	echo $prepend . machine_readable::format_output($action, $data['data'], $data['replag'], $data['continue']) . $append;
}
// Count this hit, if enabled
if($api_handle->count_hits()) {
	$api_handle->view_count($action, 'api');
}
