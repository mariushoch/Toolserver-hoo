<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-03-24
* Global page history
* Shows the page history of all articles with the given name
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
error_reporting(E_ALL ^ E_NOTICE);
*/
define('IN_HOO_TS', true);

require_once('./includes/config.php');
require_once($_CONFIG['include_path'] . 'functions.php');
require_once($_CONFIG['include_path'] . 'template.php');

$available_languages['en'] = 'English';
$available_languages['de'] = 'Deutsch';

$page_name = 'globalPageHistory';
$hoo = new hoofr();
$hoo->interface_lang();
require_once($_CONFIG['include_path'] . $page_name . 'Lang/' . $uselang . '.php');
$template = new html_output();
$hoo->view_count($page_name, $uselang);
$template->lang();

//tool config
$_CONFIG['rev'] = 10;	//how many revisions do we per default load (per wiki)
$_CONFIG['max_rev'] = 50;	//how many revisions to max load (per wiki)
$_CONFIG['format'] = '%Y-%b-%d %H:%i';	//time format

$page_title = str_replace(' ', '_', trim($_GET['page_title']));
$page_namespace = (int) $_GET['page_namespace'];
$resolve_redirects = (bool) $_GET['resolve_redirects'];
$rev_limit = (int) $_GET['rev_count'];
if($rev_limit > $_CONFIG['max_rev']) {
	$rev_limit = $_CONFIG['max_rev'];
}
if(isset($ns[$page_namespace]) && isset($resolve_redirects) && $rev_limit && isset($_GET['project'])) {
	$do_it = true;	
}

$template->set('content', '(import:globalPageHistory.html);', true);
$template->resolve_imports();

if(!$do_it) {
	//input form
	$template->template_if('mode', 'input_form');
	foreach($ns as $nr => $name) {
		$tmp[] = array($nr, $name);
	}	
	$template->parse_row('namespaces', $tmp, true);
	$template->set('revision_limit_text', str_replace('$1', $_CONFIG['max_rev'], $_LANG['revision_limit']));
	//get all wiki familys with number of wikis
	$hoo->database_connect('sql-toolserver', true);
	$SQL_query = 'SELECT family, count(*) FROM toolserver.wiki WHERE is_closed = 0 GROUP BY family';
	$statement = $DB['sql-toolserver']->prepare($SQL_query);
	$statement->execute();
	$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);
	unset($DB['sql-toolserver']);
	$output .= '<br />';
	foreach($tmp as $i) {
		$temp[] = array($i['family'], $i['count(*)']);
	}
	$template->parse_row('select_projects', $temp, true);
}else{
	$template->template_if('mode', 'do_it');
	$hoo->database_connect('sql-toolserver', true);
	//first we get all existing wikis that match the user given arguments
	$SQL_query = 'SELECT dbname, domain, server, script_path FROM toolserver.wiki WHERE family IN(';
	$i = 0;
	foreach($_GET['project'] as $project) {
		if($i != 0) {
			$SQL_query .= ', ';
		}
		$SQL_query .= $DB['sql-toolserver']->quote($project);
		$i++;
	}
	$SQL_query .= ')';
	$statement = $DB['sql-toolserver']->prepare($SQL_query);
	$statement->execute();
	$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);

	unset($DB['sql-toolserver']);

	//sort $data (by server)
	foreach($tmp as $i) {
		$data[$i['server']][] = $i;
	}

	//now we check whether the given page exists or not
	foreach($data as $server => &$wikis) {
		//connect to the database server (if not already)
		$db_server = 'sql-s' . $server . '-rr.toolserver.org';
		if($hoo->database_connect($db_server) !== true) {
			unset($data[$server]);
			$template->template_if('error_occurred', 'true');
			$template->set('error', 'Error:</b> Couldn\'t establish a connection to Database Server ' . $server . "\n<br />Due to this the following list might be incomplete.<br /><br />\n\n", false, 'append');
		}else{
			foreach($wikis as &$i) {
				//look the page up
				$SQL_query = 'SELECT page_id, page_namespace, page_title, page_is_redirect FROM ' . $i['dbname'] . '.page WHERE page_title = :page_title AND page_namespace = :page_namespace';	
				$statement = $DB[$db_server]->prepare($SQL_query);
				$statement->bindValue(':page_title', $page_title, PDO::PARAM_STR);
				$statement->bindValue(':page_namespace', $page_namespace, PDO::PARAM_INT);
				$statement->execute();
				//save in var $pages (sorted by database server)
				$tmp = $statement->fetch(PDO::FETCH_ASSOC);
				if($tmp) {
					$continue = true;
					$tmp['redirect_resolved'] = false;
					$i['page'][] = $tmp;
					if($tmp['page_is_redirect'] == 1 && $resolve_redirects) {
						//resolve redirects
						$SQL_query = 'SELECT rd_namespace, rd_title FROM ' . $i['dbname'] . '.redirect WHERE rd_from = :page_id';
						$statement = $DB[$db_server]->prepare($SQL_query);
						$statement->bindValue(':page_id', $tmp['page_id'], PDO::PARAM_INT);
						$statement->execute();
						$tmp = $statement->fetch(PDO::FETCH_ASSOC);
						if($tmp) {
							//look the page up
							$SQL_query = 'SELECT page_id, page_namespace, page_title, page_is_redirect FROM ' . $i['dbname'] . '.page WHERE page_title = :page_title AND page_namespace = :page_namespace';	
							$statement = $DB[$db_server]->prepare($SQL_query);
							$statement->bindValue(':page_title', $tmp['rd_title'], PDO::PARAM_STR);
							$statement->bindValue(':page_namespace', $tmp['rd_namespace'], PDO::PARAM_INT);
							$statement->execute();
							//save in $data
							$tmp = $statement->fetch(PDO::FETCH_ASSOC);
							if($tmp) {
								$tmp['redirect_resolved'] = true;
								$i['page'][] = $tmp;
								$tmp['dbname'] = $wiki['dbname'];
							}
						}
					}
				}
			}
		}
	}

	if(!isset($continue)) {
		$template->template_if('error_occurred', 'true');
		$template->template_if('result', 'false');
		$template->set('error', '{lang->no_page}', true);
		$template->parse_page();
		exit;
	}


	//get the page history
	foreach($data as $server => &$i) {
		foreach($i as &$wiki) {
		$db_server = 'sql-s' . $server . '-rr.toolserver.org';
		$SQL_query = 'SELECT rev_id, rev_timestamp, DATE_FORMAT(rev_timestamp, "' . $_CONFIG['format'] . '") AS formatted_time, rev_comment, rev_user_text, rev_minor_edit, rev_len FROM ' . $wiki['dbname'] . '.revision WHERE rev_page = :page_id AND rev_deleted = 0 ORDER BY rev_timestamp DESC LIMIT ' . $rev_limit;
		$statement = $DB[$db_server]->prepare($SQL_query);
		if(isset($wiki['page'])) {
			foreach($wiki['page'] as &$page) {
				$statement->bindValue(':page_id', $page['page_id'], PDO::PARAM_INT);
				$statement->execute();
				$tmp = $statement->fetchALL(PDO::FETCH_ASSOC);
				if($tmp) {
					$page['history'] = $tmp;
				}
			}
		}
		}
		unset($DB[$db_server]);
	}

	//output
	$count = 1;
	foreach($data as &$i) {
		foreach($i as &$wiki) {
			if(isset($wiki['page'])) {
				$template->template_if('result', 'true');
				$base_url = '//' . $wiki['domain'] . $wiki['script_path'];
				foreach($wiki['page'] as &$page) {
					/*
						$0 = $base_url
						$1 = $ns[$page_namespace] . $page['page_title']
						$2 = $wiki['domain']
						$3 = name for the sub row
					*/
					$count++;
					$sub_row_name = $wiki['dbname'] . '_' . $count;
					$template->parse_row('wiki_row', array($base_url, $ns[$page_namespace] . $page['page_title'], $wiki['domain'], $sub_row_name), false, false, false);
					if($page['redirect_resolved']) {
						$template->template_if('redirect_resolved', 'true');
						$template->template_if('page_is_redirect', 'false');
					}elseif($page['page_is_redirect'] == 1) {
						$template->template_if('page_is_redirect', 'true');
						$template->template_if('redirect_resolved', 'false');
					}else{
						$template->template_if('page_is_redirect', 'false');
						$template->template_if('redirect_resolved', 'false');
					}
					$row['base_url'] = $base_url;
					$row['page_title'] = $ns[$page_namespace] . $page['page_title'];
					foreach($page['history'] as $nr => $history) {
						$row['rev_id'] = $history['rev_id'];
						$row['formatted_time'] = $history['formatted_time'];
						$row['user'] = $history['rev_user_text'];
						$row['comment'] = htmlspecialchars($history['rev_comment'], ENT_COMPAT, 'UTF-8');
						$row['size'] = number_format($history['rev_len'], 0 , '', $_LANG['thousands_sep']);
						$template->parse_row($sub_row_name, $row, false, 'history_row');
						//cur
						if($nr == 0) {
							$template->last_row_if('NR', '0');
						}else{
							$template->last_row_if('NR', '1');
						}
						//user link
						if($hoo->is_ip($history['rev_user_text'])) {
							$template->last_row_if('is_ip', 'true');
						}else{
							$template->last_row_if('is_ip', 'false');
						}
						//minor edit?
						if($history['rev_minor_edit']) {
							$template->last_row_if('minor_edit', 'true');
						}else{
							$template->last_row_if('minor_edit', 'false');
						}
						//edit summary
						if($history['rev_comment']) {
							$template->last_row_if('rev_comment', 'true');
						}else{
							$template->last_row_if('rev_comment', 'false');
						}
					}
				}
			}
		}
	}
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
