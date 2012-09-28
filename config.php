<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-07-26
* Config script
*/
if(!defined('IN_HOO_TS')) {
	exit();
}

$_CONFIG['include_path'] = '/home/hoo/public_html/includes/';
$_CONFIG['cookie_path'] = '/~hoo/';
$_CONFIG['document_root'] = '/~hoo';

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

//API
$_CONFIG['api_modules'] = array(
	// 'action' => 'file.php'
	'activeSysops' => 'ModuleActiveSysops.php',
	'pagesCreated' => 'ModulePagesCreated.php',
	'wikiSets' => 'ModuleWikiSets.php',
	'hitCount' => 'ModuleHitCount.php'
);
$_CONFIG['api_include_path'] = '/home/hoo/public_html/api/';
?>
