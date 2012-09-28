<?php
/*
* [[m:User:Hoo man]]; Last update: 2011-03-03
* Added/ removed Bytes
* Shows the added and removed bytes per User (out of the recentchanges table)
*/

/*
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

$time['start'] = microtime(true);
/*
ini_set("display_errors", 1);
error_reporting(-1);
*/
define('IN_HOO_TS', true);

require_once('./includes/config.php');
require_once($_CONFIG['include_path'] . 'functions.php');
require_once($_CONFIG['include_path'] . 'template.php');

$available_languages['en'] = 'English';
$available_languages['de'] = 'Deutsch';

$page_name = 'rcBytes';
$hoo = new hoofr();
$hoo->interface_lang();
require_once($_CONFIG['include_path'] . $page_name . 'Lang/' . $uselang . '.php');
$template = new html_output();
$hoo->view_count($page_name, $uselang);
$template->lang();

$user_name = $_GET['user_name'];
$wiki_db = $_GET['project'];
$namespace = $_GET['namespace'];

//formates integers as filesize
function format_size($size) {
	$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
	if($size == 0) {
		return 0;
	}elseif($size < 0) {
		$size = $size * (-1);
		return '-' . round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i];
	}else{
		return round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i];
	}
}

if($user_name && $wiki_db) {
	$do_it = true;	
}

$template->set('content', '(import:rcBytes.html);', true);
$template->resolve_imports();

if(!$do_it) {
	//input form
	$template->template_if('mode', 'input_form');
	//get all wikis
	$hoo->database_connect('sql-toolserver', true);
	$SQL_query = 'SELECT domain, dbname FROM toolserver.wiki WHERE is_closed = 0';
	$statement = $DB['sql-toolserver']->prepare($SQL_query);
	$statement->execute();
	$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);
	unset($DB['sql-toolserver']);
	$output .= '<tr><td>' . $_LANG['wiki'] . ': </td><td><select name="project">';
	foreach($tmp as $i) {
		$temp[] = array('dbname' => $i['dbname'], 'domain' => $i['domain']);
	}
	$template->parse_row('wikis', $temp, true);
	$temp = array();
	$temp[] = array('id' => 'ALL', 'name' => '{lang->all}');//all namespaces
	foreach($ns as $id => $name) {
		$temp[] = array('id' => $id, 'name' => $name);
	}
	$template->parse_row('namespaces', $temp, true);
}else{
	$template->template_if('mode', 'do_it');
	//on which server is the db we search in?
	$hoo->database_connect('sql-toolserver', true);
	$SQL_query = 'SELECT domain, dbname, server, script_path FROM toolserver.wiki WHERE dbname = :DB';	
	$statement = $DB['sql-toolserver']->prepare($SQL_query);
	$statement->bindValue(':DB', $wiki_db, PDO::PARAM_STR);
	$statement->execute();
	$wiki = $statement->fetch(PDO::FETCH_ASSOC);
	unset($DB['sql-toolserver']);
	
	$hoo->database_connect('sql-s' . $wiki['server'] . '-rr.toolserver.org', true);
	$SQL_query = 'SELECT COUNT(rc_new_len) as edits, SUM(rc_new_len - rc_old_len) as diff FROM ' . $wiki['dbname'] . '.recentchanges WHERE rc_user_text = :user_name';
	if($namespace != 'ALL') {
		$SQL_query .= ' AND rc_namespace = :namespace';
	}
	$statement = $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->prepare($SQL_query);
	$statement->bindValue(':user_name', $user_name, PDO::PARAM_STR);
	if($namespace != 'ALL') {
		$statement->bindValue(':namespace', $namespace, PDO::PARAM_INT);
	}
	$statement->execute();
	$result = $statement->fetch(PDO::FETCH_ASSOC);
	$_LANG['result'] = str_replace('$1', '<a href="http://' . $wiki['domain'] . $wiki['script_path'] . 'index.php?title=User:' . urlencode($user_name) . '">' . htmlentities($user_name, ENT_QUOTES | ENT_IGNORE, 'UTF-8') . '</a>', $_LANG['result']);
	$_LANG['result'] = str_replace('$2', number_format($result['edits'], 0 , '', $_LANG['thousands_sep']), $_LANG['result']);
	$_LANG['result'] = str_replace('$3', number_format($result['diff'], 0 , '', $_LANG['thousands_sep']), $_LANG['result']);
	$_LANG['result'] = str_replace('$4', format_size($result['diff']), $_LANG['result']);
}
$template->parse_page();

//resource monitoring
$time['end'] = microtime(true);
$time['total_needed'] = $time['end'] - $time['start'];
echo "
<!--
Time:
	Total Time:				" . $time['total_needed'] . "
Memory:
	Memory used:				" . round(memory_get_usage()/1048576,2) . " MB (" . memory_get_usage() . " Bytes)
	Peak Memory use:			" . round(memory_get_peak_usage()/1048576,2) . " MB (" . memory_get_peak_usage() . " Bytes)
-->
";
?>
