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
$hoo->view_count('wikiSets', 'api');

$var = strip_tags($_GET['var_name']);
if(!$var) {
	$var = 'wikiSets';
}
$wikiset = (int) $_GET['wikiset'];
if(isset($_GET['return']) AND stripos($_GET['return'], '|') !== false) {
	$return = explode('|', $_GET['return']);
	$return_list = '';
	$y = false;
	foreach($return as $i) {
		$i = trim($i);
		if($i !== 'ws_wikis' and $i !== 'ws_type' and $i !== 'ws_name') {
			die('Unkown &return value');
		}
		if($y) {
			$return_list .= ',' . $i;
		}else{
			$return_list .= $i;
			$y = true;
		}
	}
}elseif(isset($_GET['return'])) {
	$i = trim($_GET['return']);
	if($i !== 'ws_wikis' and $i !== 'ws_type' and $i !== 'ws_name') {
		die('Unkown &return value');
	}
	$return_list = $_GET['return'];
}else{
	$return_list = 'ws_wikis';
}

if(!$wikiset) {
	die("<b>Error</b>: Not enough arguments given!<br />
	Usage:<br />
	&wikiset= the id of the wikiset to look for<br />
	Optional:<br />
	&return= the information to return (ws_wikis, ws_type, ws_name; default: wikis; separate with |)<br />
	&javascript= set this, if you want the reply to be a valid javascript<br />
	&var_name= the name of the var the results will be stored in (useable with above)");
}else{
	$server = 'metawiki-p.rrdb.toolserver.org';
	$hoo->database_connect($server, true);
	//get data
	$SQL_query = 'SELECT ' . $return_list . ' FROM centralauth_p.wikiset WHERE ws_id = :id';
	$statement = $DB[$server]->prepare($SQL_query);
	$statement->bindValue(':id', $wikiset, PDO::PARAM_INT);
	$statement->execute();
	$data = $statement->fetch(PDO::FETCH_ASSOC);
	//to aviod to frequent reloads, the page will expire after 24 hours
	header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
	header('Cache-Control: max-age=86400, must-revalidate');
	if(!$_GET['javascript']) {
		header('Content-type: text/xml');
		if(!is_array($data)) {
			echo '<api success="False"></api>';
			exit;
		}
		echo '<api success="True">';
		echo '<wikiset ';
		foreach($data as $key => $value) {
			echo $key . ' = "' . $value . '" ';
		}
		echo '/>';
		echo '</api>';
		exit;
	}else{
		header('Content-type: text/javascript');
		if(!is_array($data)) {
			die('Error');
			exit;
		}
		echo 'var ' . $var . ' = {};';
		foreach($data as $key => $value) {
			echo $var . '.' . $key . ' = "' . $value . '"';
		}
		exit;
	}
}
?>
