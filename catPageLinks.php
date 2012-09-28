<?php
/*
* [[m:User:Hoo man]]; Last update: 2011-09-28
* Category Page links
* Give the count of page links for all pages within a specific category
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

$page_name = 'catPageLinks';
$hoo = new hoofr();
$hoo->interface_lang();
require_once($_CONFIG['include_path'] . $page_name . 'Lang/' . $uselang . '.php');
$template = new html_output();
$hoo->view_count($page_name, $uselang);
$template->lang();

$max_depth = 7;						//max depth (subcategories)

//recursive function which gets $depth subactegories of $cat_name
//crappy and complicated code :/
function getSubCategories($cat_name, $depth) {
	global $hoo, $DB, $wiki, $calls;
	$categories[] = $cat_name;
	$calls++;
	if($depth > 0) {
		$SQL_query = 'SELECT page.page_title as categories FROM ' . $wiki['dbname'] . '.categorylinks INNER JOIN ' . $wiki['dbname'] . '.page ON page.page_id = categorylinks.cl_from WHERE categorylinks.cl_to = :cat_name AND page.page_namespace = 14';
		$statement = $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->prepare($SQL_query);
		$statement->bindValue(':cat_name', $cat_name, PDO::PARAM_STR);
		$statement->execute();
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		$depth--;
		if(is_array($result)) {
			foreach($result as $y) {
				foreach($y as $o) {
					if(!in_array($o, $categories)) {
						$categories[] = $o;
						if($depth > 0) {
							$tmp = getSubCategories($o, $depth);
							if(is_array($tmp)) {
								foreach($tmp as $x) {
									$categories[] = $x;
								}
							}
						}
					}
				}
			}
		}
	}
	if(isset($categories)) {
		return array_unique($categories);
	}
}

$cat_name = str_replace(' ', '_', trim($_GET['cat_name']));
$namespace = (int) $_GET['page_namespace'];
$depth = (int) $_GET['depth'];
$links_from = ($_GET['links_from']=='on'? true : false);
if($depth > $max_depth) {
	$depth = $max_depth;
}
if(!$_GET['depth']) {
	$depth = 0;
}
if(!$_GET['page_namespace']) {
	$namespace = 0;
}
$wiki_db = $_GET['project'];

if($cat_name && $wiki_db && $depth >= 0 && $namespace >= 0) {
	$do_it = true;
}

$template->set('content', '(import:catPageLinks.html);', true);
$template->resolve_imports();

if(!$do_it) {
	//input form
	$template->template_if('mode', 'input_form');
	$_LANG['depth'] = str_replace('$1', $max_depth, $_LANG['depth']);
	foreach($ns as $id => $name) {
		$temp[] = array('id' => $id, 'name' => $name);
	}
	$template->parse_row('namespaces', $temp, true);
	//get all wikis
	$hoo->database_connect('sql-toolserver', true);
	$SQL_query = 'SELECT domain, dbname FROM toolserver.wiki WHERE is_closed = 0';
	$statement = $DB['sql-toolserver']->prepare($SQL_query);
	$statement->execute();
	$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);
	unset($DB['sql-toolserver']);
	$output .= '<tr><td>' . $_LANG['wiki'] . ': </td><td><select name="project">';
	$temp = array();
	foreach($tmp as $i) {
		$temp[] = array('dbname' => $i['dbname'], 'domain' => $i['domain']);
	}
	$template->parse_row('wikis', $temp, true);
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
	//get X subcategories of the given categorie
	$categories = getSubCategories($cat_name, $depth);
	//get the page + page links from the categories
	$SQL_query = 'SELECT /* LIMIT:20 */ page.page_title as page, page.page_is_redirect FROM ' . $wiki['dbname'] . '.categorylinks INNER JOIN ' . $wiki['dbname'] . '.page ON categorylinks.cl_from = page.page_id WHERE categorylinks.cl_to IN(';
	$i = 0;
	foreach($categories as $category) {
		if($i != 0) {
			$SQL_query .= ', ';
		}
		$SQL_query .= $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->quote($category);
		$i++;
	}
	$SQL_query .= ') AND page.page_namespace = :namespace GROUP BY page.page_title';
	$statement = $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->prepare($SQL_query);
	$statement->bindValue(':namespace', $namespace, PDO::PARAM_INT);
	$statement->execute();
	$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);
	if(is_array($tmp)) {
		foreach($tmp as $i) {
			$pages[] = $i['page'];
			if($i['page_is_redirect']) {
				$is_redirect[$i['page']] = true;
			}
		}
	}
	$SQL_query = 'SELECT /* LIMIT:60 */ pagelinks.pl_title as page, COUNT(pagelinks.pl_title) as links FROM ' . $wiki['dbname'] . '.pagelinks ';
	if($links_from) {
		$SQL_query .= 'INNER JOIN ' . $wiki['dbname'] . '.page ON page.page_id = pagelinks.pl_from WHERE page.page_namespace= :namespace AND ';
	}else{
		$SQL_query .= 'WHERE ';
	}
	$SQL_query .= 'pagelinks.pl_title IN(';
	$i = 0;
	foreach($pages as $page) {
		if($i != 0) {
			$SQL_query .= ', ';
		}
		$SQL_query .= $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->quote($page);
		$i++;
	}
	$SQL_query .= ') AND pagelinks.pl_namespace= :namespace GROUP BY pagelinks.pl_title ORDER BY links DESC';
	$statement = $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->prepare($SQL_query);
	$statement->bindValue(':namespace', $namespace, PDO::PARAM_INT);
	$statement->execute();
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	if($_GET['type'] == 'raw') {
		header('Content-type: text/plain');
		if($_GET['separator']) {
			$separator = strip_tags($_GET['separator']);
		}else{
			$separator = "\t";
		}
		foreach($result as $i) {
			echo $i['page'] . $separator . $i['links'] . "\n";
		}
		exit;
	}
	if(!$result) {
		$template->template_if('error_occurred', 'true');
		$template->template_if('result', 'false');
		$template->set('error', '{lang->no_page}', true);
		$template->parse_page();
		exit;
	}
	$template->template_if('result', 'true');
	$ns[0] = '';

	//the following is just a bad workaround to be able to display pages with 0 links to

	foreach($result as $i) {
		foreach($pages as $y => &$page) {
			if($page == $i['page']) {
				unset($pages[$y]); //we get rid of all $pages entry which are included in the result set as well
			}
		}
	}
	$i = 2000000000; $tmp = array();
	foreach($pages as $y) {		//now we have to rebuild the pages array (as $tmp) to fit the format of $result
		$tmp[$i] = array('page' => $y);
		$i++;
	}
	$result = $result + $tmp;
	foreach($result as $i) {
		$template->parse_row('result', array('wiki' => $wiki['domain'] . $wiki['script_path'], 'page' => $ns[$namespace] . $i['page'], 'links' => number_format($i['links'], 0 , '', $_LANG['thousands_sep'])));
		 if($is_redirect[$i['page']]) {
			$template->last_row_if('is_redirect', 'true');
		}else{
			$template->last_row_if('is_redirect', 'false');
		}
	}
	$template->set('raw_url', $_SERVER['REQUEST_URI'] . '&type=raw&separator=%09');
}
$template->parse_page();

//resource monitoring
$time['end'] = microtime(true);
$time['total_needed'] = $time['end'] - $time['start'];
echo "
<!--
getSubCategories() calls:			" . $calls . "
Time:
	Total Time:				" . $time['total_needed'] . "
Memory:
	Memory used:				" . round(memory_get_usage()/1048576,2) . " MB (" . memory_get_usage() . " Bytes)
	Peak Memory use:			" . round(memory_get_peak_usage()/1048576,2) . " MB (" . memory_get_peak_usage() . " Bytes)
-->
";
?>
