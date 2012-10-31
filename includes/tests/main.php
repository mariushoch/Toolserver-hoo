<?php
/*
 * (bad) Unit test base file
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */
if(isset($_SERVER['SERVER_PROTOCOL'])) {
	echo 'Must not be called from the web';
	exit( 1 );
}

$_STATS = array('failed' => 0, 'passed' => 0, 'start' => microtime(true));

//if smth. went wrong
function went_wrong($test) {
	global $_STATS;
	echo "\n" . $test . " - failed!!!" . PHP_EOL . PHP_EOL;
	$_STATS['failed']++;
}
//if smth. went right
function went_right($test) {
	global $_STATS;
	echo $test . " - passed" . PHP_EOL;
	$_STATS['passed']++;
}
//these values must equal:
function m_equal($test, $a, $b) {
	if($a != $b) {
		went_wrong($test);
	}else{
		went_right($test);
	}
}
//these values must equal stric:
function m_equal_strict($test, $a, $b) {
	if($a !== $b) {
		went_wrong($test);
	}else{
		went_right($test);
	}
}
//these values mustn't equal
function m_n_equal($test, $a, $b) {
	if($a == $b) {
		went_wrong($test);
	}else{
		went_right($test);
	}
}
//these values mustn't equal
function m_n_equal_strict($test, $a, $b) {
	if($a === $b) {
		went_wrong($test);
	}else{
		went_right($test);
	}
}

define('IN_HOO_TS', true);
try {
	//load web_start.php just like a usual site request would
	require_once(__DIR__ . '/../web_start.php');

	//
	// Core classes
	//

	// hoo_base::
	include_once('hoo.php');

	// machine_readable::
	include_once('machine_readable.php');


	//
	// API modules
	//
	include_once('ModuleActiveSysops.php');
	include_once('ModulePagesCreated.php');
	include_once('ModuleWikiSets.php');
	
	
}catch(Exception $e){
	went_wrong('Uncaught exception: ' . $e->getMessage());
}

echo PHP_EOL . PHP_EOL . PHP_EOL;
echo 'Ran for: ' . (microtime(true) - $_STATS['start']) . ' seconds';
echo ', used ' . round(memory_get_usage()/1048576,2) . ' MiB memory (' . round(memory_get_peak_usage()/1048576,2) . ' MiB Peak)' . PHP_EOL;
echo 'Total tests: ' . ($_STATS['passed'] + $_STATS['failed']) . "        Passed: " . $_STATS['passed'] . "        Failed: " . $_STATS['failed'];
echo PHP_EOL;
