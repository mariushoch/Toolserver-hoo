<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-09-30
* Config script
*/
if(!defined('IN_HOO_TS')) {
	exit();
}

$_CONFIG['include_path'] = __DIR__ . '/';
$_CONFIG['cookie_path'] = '/~hoo/';
$_CONFIG['document_root'] = '/~hoo';
$_CONFIG['log_file'] = '/home/hoo/archive/web_log.txt';

//Canonical namespaces 
$ns = array(
	0 => '',
	1 => 'Talk:',
	2 => 'User:',
	3 => 'User_talk:',
	4 => 'Project:',
	5 => 'Project_talk:',
	6 => 'Image:',
	7 => 'Image_talk:',
	8 => 'MediaWiki:',
	9 => 'MediaWiki_talk:',
	10 => 'Template:',
	11 => 'Template_talk:',
	12 => 'Help:',
	13 => 'Help_talk:',
	14 => 'Category:',
	15 => 'Category_talk:'
);

//user databases
$_SQL['misc_db']['server'] = 'sql';
$_SQL['misc_db']['db_name'] = 'u_hoo_p';

//classes

$_CONFIG['classes'] = array(
	'hoo_base' => 'hoo.php',
	'hoo_api' => 'api_base.php',
	'machine_readable' => 'machine_readable.php',
	'log' => 'log.php',
	// API actions ('action' => 'api/file.php')
	'apiActiveSysops' => 'api/ModuleActiveSysops.php',
	'apiPagesCreated' => 'api/ModulePagesCreated.php',
	'apiWikiSets' => 'api/ModuleWikiSets.php',
	'apiHitCount' => 'api/ModuleHitCount.php',
	'apiInfo' => 'api/ModuleInfo.php',
	// exceptions
	'database_exception' => 'exceptions.php',
);

//API
$_CONFIG['api_modules'] = array(
	// known api modules ('action' => 'className')
	'activeSysops' => 'apiActiveSysops',
	'pagesCreated' => 'apiPagesCreated',
	'wikiSets' => 'apiWikiSets',
	'hitCount' => 'apiHitCount',
	'info' => 'apiInfo'
);
