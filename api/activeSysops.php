<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-02-11
* Gives the number of active sysops, no frontend
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

require_once('/home/hoo/public_html/includes/config.php');
require_once($_CONFIG['include_path'] . 'functions.php');

$hoo = new hoofr();
$hoo->view_count('activeSysops', 'api');

$var = strip_tags($_GET['var_name']);
if(!$var) {
	$var = 'activeSysops';
}
$last_action_time = (int) $_GET['last_action'];
$wiki_db = $_GET['wiki'];
$limit = (int) $_GET['limit'];
if(!$limit) {
	$limit = 2147483647;
}

if(!$wiki_db) {
	die("<b>Error</b>: Not enough arguments given!<br />
	Usage:<br />
	&wiki= the databasename of the wiki to search in<br />
	Optional:<br />
	&last_action= time in seconds since the last action to count the sysop as active (default: one week)<br />
	&javascript= set this, if you want the reply to be a valid javascript<br />
	&var_name= the name of the var the results will be stored in (useable with above)");
}else{
	if($last_action_time) {
		$last_action_time = time() - $last_action_time;	
	}else{
		//default: one week
		$last_action_time = time() - 604800;
	}
	$date = date_create('@' . $last_action_time);
	$last_action_time = date_format($date, 'YmdHis');
	$server = str_replace('_', '-', $wiki_db) . '.rrdb.toolserver.org';
	$hoo->database_connect($server, true);
	//get number of active sysops
	$SQL_query = 'SELECT COUNT(*) /* LIMIT: 15 */ AS active_sysops FROM (SELECT log_user AS user FROM ' . addslashes($wiki_db) . '.logging WHERE log_type IN ("block","delete","protect") AND log_timestamp > :last_action_time GROUP BY log_user) as active_users INNER JOIN ' . addslashes($wiki_db) . '.user_groups ON ug_user = active_users.user WHERE ug_group = "sysop"';
	$statement = $DB[$server]->prepare($SQL_query);
	$statement->bindValue(':last_action_time', $last_action_time, PDO::PARAM_STR);
	$statement->execute();
	$tmp = $statement->fetch(PDO::FETCH_ASSOC);
	if(is_array($tmp) && $tmp['active_sysops']) {
		$active_sysops = $tmp['active_sysops'];
	}
	if(!$active_sysops) {
		$active_sysops = 0;
	}
	//to aviod to frequent reloads, the page will expire after 24 hours
	header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
	header('Cache-Control: max-age=86400, must-revalidate');
	if(!$_GET['javascript']) {
		header('Content-type: text/plain');
		echo $active_sysops;
		exit;
	}else{
		header('Content-type: text/javascript');
		echo 'var ' . $var . ' = ' . $active_sysops . ';';
		exit;
	}
}
?>
