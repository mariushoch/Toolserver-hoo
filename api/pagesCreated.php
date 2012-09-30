<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-07-27
* Gives a list of created pages
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
define('IN_HOO_TS', true);

require_once('/home/hoo/public_html/includes/config.php');
require_once($_CONFIG['include_path'] . 'functions.php');

$hoo = new hoofr();
$hoo->view_count('pagesCreated', 'api');

$var = strip_tags($_GET['var_name']);
if(!$var) {
	$var = 'pagesCreated';
}
$user_name = str_replace('_', ' ', strip_tags($_GET['user']));
$wiki_db = $_GET['wiki'];
$wiki_db = preg_replace('/[^A-Za-z0-9_]/', '', $wiki_db);
$from = false;
if(isset($_GET['from'])) {
	$from = ($_GET['from']=='now' ? false : (int) $_GET['from']);
	#$from = strftime('%Y%m%d%H%M%S', $from);
	$date = date_create('@' . $from);
	$from = date_format($date, 'YmdHis');
}
$to = false;
if(isset($_GET['to'])) {
	$to = ($_GET['to']=='infinite' ? false : (int) $_GET['to']);
	#$to = strftime('%Y%m%d%H%M%S', $to);
	$date = date_create('@' . $to);
	$to = date_format($date, 'YmdHis');
}
if(isset($_GET['namespaces']) AND stripos($_GET['namespaces'], '|') !== false) {
	$namespaces = explode('|', $_GET['namespaces']);
	$namespaces_list = '';
	$y = false;
	foreach($namespaces as $i) {
		$i = (int) $i; //typecast to int
		if($y) {
			$namespaces_list .= ',' . $i;
		}else{
			$namespaces_list .= $i;
			$y = true;
		}
	}
}elseif(isset($_GET['namespaces'])) {
	$namespaces[0] = (int) $_GET['namespaces'];
	$namespaces_list = $namespaces[0];
}
if($wiki_db AND $user_name) {
	$do_it = true;
}

if(!$do_it) {
	die("<b>Error</b>: Not enough arguments given!<br />
	Usage:<br />
	&wiki= the databasename of the wiki to search in<br />
	&user= the user to get created pages from<br />
	Optional:<br />
	&namespaces= namespaces from which pages are taken (default: all, seperate them with a |)<br />
	&from= unix timestamp from which page creations are getting counted (default: now)<br />
	&to= unix timestamp to which page creations are getting counted (default: infinite)<br />
	&separator= char(s) that seperate namespaces from page titles in the output (default: Tab, has no effect with javascript)<br />
	&javascript= set this, if you want the reply to be a valid javascript<br />
	&var_name= the name of the object the results will be stored in (default: pagesCreated, useable with above only)");
}else{
	$server = str_replace('_', '-', $wiki_db) . '.rrdb.toolserver.org';
	$hoo->database_connect($server, true, $wiki_db);
	//get all created pages
	/* The following SQL query is a /bit/ complicated but fast (at least I hope so)
	It basicly gets a list of all pages the user edited (in the given NS and time span),
	afterwards it gets all pages the user edited but his edit wasn't the first one on the page (that's way faster than looking the first edit for every page up)
	Finally it just takes the first list and removes all entries from it, which are on the second list */
	$SQL_query = 'SELECT /* LIMIT:60 NM*/ page.page_namespace, page.page_title FROM page INNER JOIN (SELECT DISTINCT revision.rev_page FROM revision';
	if(is_array($namespaces)) {
		$SQL_query .= ' INNER JOIN page ON revision.rev_page = page.page_id';
	}
	$SQL_query .= ' WHERE revision.rev_user_text = :user_name';
	if(is_array($namespaces)) {
		$SQL_query .= ' AND page.page_namespace IN(' . $namespaces_list . ')';
	}
	if($to) {
		$SQL_query .= ' AND revision.rev_timestamp > :to';
	}
	if($from) {
		$SQL_query .= ' AND revision.rev_timestamp < :from';
	}
	$SQL_query .= ' ) as all_pages ON page.page_id = all_pages.rev_page WHERE page.page_id NOT IN (SELECT revision.rev_page FROM revision INNER JOIN (SELECT min(rev_timestamp) as edit_time, rev_page FROM revision';
	if(is_array($namespaces)) {
		$SQL_query .= ' INNER JOIN page ON revision.rev_page = page.page_id';
	}
	$SQL_query .= ' WHERE revision.rev_user_text = :user_name';
	if(is_array($namespaces)) {
		$SQL_query .= ' AND page.page_namespace IN(' . $namespaces_list . ')';
	}
	if($to) {
		$SQL_query .= ' AND revision.rev_timestamp > :to';
	}
	if($from) {
		$SQL_query .= ' AND revision.rev_timestamp < :from';
	}
	$SQL_query .= ' GROUP BY rev_page) as edited_pages ON edited_pages.rev_page = revision.rev_page WHERE revision.rev_timestamp < edited_pages.edit_time)';
	$statement = $DB[$server]->prepare($SQL_query);
	$statement->bindValue(':user_name', $user_name, PDO::PARAM_STR);
	if($to) {
		$statement->bindValue(':to', $to, PDO::PARAM_INT);
	}
	if($from) {
		$statement->bindValue(':from', $from, PDO::PARAM_INT);
	}
	$statement->execute();
	$pages_created = $statement->fetchALL(PDO::FETCH_ASSOC);
	if($statement->errorCode() != 00000) {
		header("Status: 500 Server error");
		exit;
	}
	if(is_array($pages_created)) {
		if(!$_GET['javascript']) {
			header('Content-type: text/plain');
			if($_GET['separator']) {
				$separator = strip_tags($_GET['separator']);
			}else{
				$separator = "\t";
			}
			foreach($pages_created as $i) {
				echo $i['page_namespace'] . $separator . $i['page_title'] . "\n";
			}
		}else{
			header('Content-type: text/javascript');
			echo 'var ' . $var . " = {\n";
			$y = 0;
			foreach($pages_created as $i) {
				if($y == 0) {
					echo $y . ' : { page_namespace : ' . $i['page_namespace'] . ",\n" . 'page_title : "' . str_replace('"', '\"', $i['page_title']) . '"}';
				}else{
					echo ",\n" . $y . ' : { page_namespace : ' . $i['page_namespace'] . ",\n" . 'page_title : "' . str_replace('"', '\"', $i['page_title']) . '"}';
				}
				$y++;
			}
			echo '};';
		}
	}
}
?>
