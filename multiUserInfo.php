<?php
/*
* [[m:User:Hoo man]]; Last update: 2011-06-24
* Multi user info
* Gives informations about multiple (up to 75) users as csv
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

/*
ini_set("display_errors", 1);
error_reporting(-1);
//*/
define('IN_HOO_TS', true);

require_once('./includes/config.php');
require_once($_CONFIG['include_path'] . 'functions.php');
require_once($_CONFIG['include_path'] . 'template.php');

$page_name = 'Multi user info';
$hoo = new hoofr();

if(isset($_POST['users'])) {
	$users = explode("\r\n", $_POST['users']);
	if(isset($users[75])) {
		$users = array_slice($users, 0, 75);
	}
}
$wiki_db = $_POST['project'];
$last_edit_time = (int) $_POST['recent'];

if($users && $wiki_db && $last_edit_time) {
	$do_it = true;	
}
function csv_column($str, $multi_row_sep = False, $last = False) {
	if($multi_row_sep) {
		$str = str_replace($multi_row_sep, "\n", $str);
	}
	$str = '"' . str_replace('"', '\"', $str) . '"';
	if($last) {
		return $str . "\n";
	}else{
		return $str . ',';
	}
}

if(!$do_it) {
	$template = new html_output();
	$template->set('content', '(import:multiUserInfo.html);', true);
	$template->resolve_imports();
	$available_languages['en'] = 'English';
	$_LANG['title'] = 'Multi user info';
	$_LANG['wiki'] = 'Wiki';
	$_LANG['hours'] = 'hours';
	$_LANG['days'] = 'days';
	$_LANG['recentDropdown'] = 'Show article edits from the last';
	$_LANG['download'] = 'Download CSV';
	$_LANG['description'] = 'This tool will give informations about up to 75 users in a csv spreadsheet.';
	$_LANG['users'] = 'Users (one per line, without the User:)';
	$hoo->interface_lang();
	$hoo->view_count('multiUserInfo', $uselang);
	$template->lang();
	//get all wikis
	$hoo->database_connect('sql-toolserver', true);
	$SQL_query = 'SELECT domain, dbname FROM toolserver.wiki WHERE is_closed = 0';
	$statement = $DB['sql-toolserver']->prepare($SQL_query);
	$statement->execute();
	$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);
	unset($DB['sql-toolserver']);
	$temp = array();
	foreach($tmp as $i) {
		$temp[] = array('dbname' => $i['dbname'], 'domain' => $i['domain']);
	}
	$template->parse_row('wikis', $temp, true);
	$template->parse_page();
}else{
	//on which server is the db we search in?
	$hoo->database_connect('sql-toolserver', true);
	$SQL_query = 'SELECT domain, dbname, server, script_path FROM toolserver.wiki WHERE dbname = :DB';	
	$statement = $DB['sql-toolserver']->prepare($SQL_query);
	$statement->bindValue(':DB', $wiki_db, PDO::PARAM_STR);
	$statement->execute();
	$wiki = $statement->fetch(PDO::FETCH_ASSOC);
	unset($DB['sql-toolserver']);
	$hoo->database_connect('sql-s' . $wiki['server'] . '-rr.toolserver.org', true);
	//set the group_concat_max_len to an usuable size... I hope this is enough
	$DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->query('SET SESSION group_concat_max_len = 12288;');
	//last edit time
	$last_edit_time = time() - $last_edit_time;
	$date = date_create('@' . $last_edit_time);
	$last_edit_time = date_format($date, 'YmdHis');
	//the users we look for
	$i = 0;
	$in = 'IN(';
	foreach($users as $user) {
		if($i != 0) {
			$in .= ', ';
		}
		$in .= $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->quote(str_replace('_', ' ', $user));
		$i++;
	}
	$in .= ')';
	
	//actual query
	$SQL_query =
'SELECT DISTINCT /* LIMIT:90 */ user_name, user.user_editcount AS all_edit_nr, article_edit_count.number AS article_edit_nr, edited_pages.pages AS edited_pages, edited_pages.edit_time AS edit_time, uploads.images AS uploaded_files, uploads.time AS upload_time
FROM ' . $wiki['dbname'] . '.user LEFT JOIN
(SELECT COUNT(*) AS number, rev_user AS user FROM ' . $wiki['dbname'] . '.revision INNER JOIN ' . $wiki['dbname'] . '.page ON page_id = rev_page WHERE rev_user_text ' . $in . ' AND page_namespace = 0 GROUP BY rev_user) AS article_edit_count
ON article_edit_count.user = user.user_id
LEFT JOIN
(SELECT rev_user AS user, GROUP_CONCAT(page_title SEPARATOR "|") AS pages, GROUP_CONCAT(rev_timestamp SEPARATOR "|") AS edit_time FROM ' . $wiki['dbname'] . '.revision INNER JOIN ' . $wiki['dbname'] . '.page ON page_id = rev_page WHERE rev_user_text ' . $in . ' AND page_namespace = 0 AND rev_deleted = 0 AND rev_timestamp > :last_edit_time GROUP BY rev_user) AS edited_pages
ON edited_pages.user = user.user_id
LEFT JOIN
(SELECT img_user AS user, GROUP_CONCAT(img_name SEPARATOR "|") AS images, GROUP_CONCAT(img_timestamp SEPARATOR "|") AS time FROM ' . $wiki['dbname'] . '.image WHERE img_user_text ' . $in . ' GROUP BY img_user) AS uploads
ON uploads.user = user.user_id
WHERE user_name ' . $in;

	$statement = $DB['sql-s' . $wiki['server'] . '-rr.toolserver.org']->prepare($SQL_query);
	$statement->bindValue(':last_edit_time', $last_edit_time, PDO::PARAM_STR);
	$statement->execute();
	
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="users.csv"');
	#echo str_replace(':last_edit_time', '"' . $last_edit_time . '"', $SQL_query);
	#print_r($result);
	echo csv_column('User');
	echo csv_column('Total edit count');
	echo csv_column('Article edit count');
	echo csv_column('Recently edited pages');
	echo csv_column('Edit time');
	echo csv_column('Uploaded files');
	echo csv_column('Upload time', False, True);
	
	foreach($result as $row) {
		echo csv_column($row['user_name']);
		echo csv_column($row['all_edit_nr']);
		echo csv_column($row['article_edit_nr']);
		echo csv_column($row['edited_pages'], '|');
		echo csv_column($row['edit_time'], '|');
		echo csv_column($row['uploaded_files'], '|');
		echo csv_column($row['upload_time'], '|', True);
	}
}
?>
